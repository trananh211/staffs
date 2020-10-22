<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class NameStory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scan:website';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape all stores';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        logfile_system('========================= [ Bắt đầu cào website ] ===========================');
        try {
            $check = $this->checkWebsite();
            //Nếu tồn tại website đang cần được scrap
            if (is_array($check)) {
                $website_id = $check[0]->website_id;
                $template_id = $check[0]->template_id;
                $store_id = $check[0]->store_id;
                $woo_template_id = $check[0]->id;
                if ($website_id > 1000)
                {
                    $info = \DB::table('websites as ws')
                        ->leftjoin('woo_templates as wtp','ws.id', '=', 'wtp.website_id')
                        ->leftjoin('woo_categories as wc','ws.woo_category_id', '=', 'wc.id')
                        ->select(
                            'ws.id as website_id', 'ws.platform_id','ws.url', 'ws.exclude_text', 'ws.first_title',
                            'wc.woo_category_id', 'wc.name as category_name', 'wc.slug as category_slug',
                            'wtp.id as woo_template_id', 'wtp.product_name', 'wtp.template_id', 'wtp.template_path',
                            'wtp.t_status','wtp.sku_auto_id', 'wtp.custom_template_id'
                        )
                        ->where('ws.id',$website_id)->first();
                    if($info != NULL)
                    {
                        $exclude_text = $info->exclude_text;
                        $category_name = ucwords($info->category_name);
                        $domain = $info->url;
                        $first_title = $info->first_title;
                        $pre_data = [
                            't_status' => $info->t_status,
                            'sku_auto_id' => $info->sku_auto_id,
                            'custom_template_id' => $info->custom_template_id
                        ];
                        switch ($info->platform_id) {
                            case 1:
                                $this->autoScanCustomPlatform($pre_data ,$website_id, $template_id, $store_id, $woo_template_id, $category_name ,$exclude_text, $domain);
                                break;
                            case 2:
                                $this->autoScanMerchKing($pre_data ,$website_id, $template_id, $store_id, $woo_template_id, $category_name ,$exclude_text, $domain);
                                break;
                            case 3:
                                $this->autoScanEsty($pre_data ,$website_id, $template_id, $store_id, $woo_template_id, $category_name ,$exclude_text, $domain, $first_title);
                                break;
                            default:
                                $str = "-- Không tồn tại platform nào cần được cào.";
                                logfile_system($str);
                        }
                    } else {
                        logfile_system('-- Không tồn tại thông tin của website id: '.$website_id);
                    }
                } else {
                    switch ($website_id) {
                        case 1:
                            $this->scanNamestories($website_id, $template_id, $store_id, $woo_template_id);
                            break;
                        case 6:
                            $this->scanMerchKing($website_id, $template_id, $store_id, $woo_template_id);
                            break;
                        case 7:
                            $this->scanMerchKing_Zolagifts($website_id, $template_id, $store_id, $woo_template_id,'13198');
                            break;
                        case 8:
                            $this->scanMerchKing_Zolagifts($website_id, $template_id, $store_id, $woo_template_id,'15198');
                            break;
                        case 9:
                        case 17:
                            $this->scanMerchKing_getTag($website_id, $template_id, $store_id, $woo_template_id);
                            break;
                        case 15:
                            $this->scanCreationsLaunch_getTag($website_id, $template_id, $store_id, $woo_template_id,'High Top');
                            break;
                        case 16:
                            $this->scanCreationsLaunch_getTag($website_id, $template_id, $store_id, $woo_template_id,'Low Top');
                            break;
                        case 18:
                            $text_exclude = 'B6L2AF01';
                            $this->scanMerchKing_getTag_excludeText($website_id, $template_id, $store_id, $woo_template_id, $text_exclude);
                            break;
                        case 19:
                            $text_exclude = 'B450';
                            $this->scanMerchKing_getTag_excludeText($website_id, $template_id, $store_id, $woo_template_id, $text_exclude);
                            break;
                        case 20:
                            $text_exclude = '- B750';
                            $this->scanMerchKing_getTag_excludeText($website_id, $template_id, $store_id, $woo_template_id, $text_exclude);
                            break;
                        default:
                            $str = "-- Không có website nào cần được cào.";
                            logfile_system($str);
                    }
                }

            } else {
                $str = '-- Không tồn tại website nào cần được scrap dữ liệu.';
                logfile_system($str);
            }
        } catch (\Exception $e) {
            logfile_system($e->getMessage());
        }
        logfile_system('========================= [ Kết thúc cào website ] ===========================');
    }

    //Kiểm tra xem có website nào chưa được scan hay không
    private function checkWebsite()
    {
        $result = false;
        $webs = \DB::table('woo_templates')
            ->select('id', 'template_id', 'website_id', 'store_id')
            ->where('website_id', '<>', '')
            ->where('status', 0)
            ->limit(1)->get()->toArray();
        if (sizeof($webs) > 0) {
            $result = $webs;
        }
        return $result;
    }

    /*Save database*/
    private function saveTemplate($data, $woo_template_id, $domain)
    {
        if (sizeof($data) > 0) {
            $insert = \DB::table('scrap_products')->insert($data);
            if ($insert) {
                \DB::table('woo_templates')->where('id', $woo_template_id)->update([
                    'status' => 1,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                logfile_system('Insert thành công dữ liệu ' . sizeof($data) . ' link sản phẩm website '.$domain);
            }
        } else {
            \DB::table('woo_templates')->where('id', $woo_template_id)->update([
                'status' => 1,
                'updated_at' => date("Y-m-d H:i:s")
            ]);
            logfile_system('Không có dữ liệu cào từ website '.$domain);
        }
    }

    /*website namestories.com*/
    private function scanNamestories($website_id, $template_id, $store_id, $woo_template_id)
    {
        $website = website();
        $domain = 'https://namestories.com';
        $link = $domain . '/collections/all?page=';
        $page = 1;
        $data = array();
        do {
            echo $page . '-page' . "\n";
            $url = $link . $page;
            $curent_page = $page;
            $client = new \Goutte\Client();
            $response = $client->request('GET', $url);
            $crawler = $response;

            // kiem tra xem co ton tai product nao ở page hiện tại hay không
            $products = ($crawler->filter('div.collection-grid div.grid__item')->count() > 0) ?
                $crawler->filter('div.collection-grid div.grid__item')->count() : 0;
            if ($products > 0) {
                $crawler->filter('div.collection-grid div.grid__item')
                    ->each(function ($node) use (&$data, &$domain, &$website_id, &$template_id, &$store_id, &$website) {
                        $link = $domain . trim($node->filter('p a')->attr('href'));
                        $name = trim($node->filter('p')->text());
                        $category_name = trim(explode("|", $name)[0]);
                        $data[] = [
                            'category_name' => preg_replace('/[^a-z\d]/i', '', sanitizer($category_name)),
                            'link' => $link,
                            'website_id' => $website_id,
                            'website' => $website[$website_id],
                            'template_id' => $template_id,
                            'store_id' => $store_id,
                            'status' => 0,
                            'created_at' => date("Y-m-d H:i:s"),
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                    });
            }

            //Phần cuối cùng. Không được chèn thêm ở đây nữa
            // kiểm tra xem đây có phải là trang cuối cùng hay không
            $next_page_link = $crawler->filter('.pagination-links a:nth-last-child(1)')->attr('href');
            $next_page = preg_replace("/[^0-9\.]/", '', $next_page_link);
            $page = $next_page;
            $number_link = $crawler->filter('.pagination-links a')->count();
        } while ($next_page > $curent_page);
        if (sizeof($data) > 0) {
            $insert = \DB::table('scrap_products')->insert($data);
            if ($insert) {
                \DB::table('woo_templates')->where('id', $woo_template_id)->update([
                    'status' => 1,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                logfile_system('Insert thành công dữ liệu ' . sizeof($data) . ' link sản phẩm website namestories.com');
            }
        }
    }
    /* End website namestories.com */

    /*website merch king*/
    private function scanMerchKing($website_id, $template_id, $store_id, $woo_template_id)
    {
        $website = website();
        $domain = $website[$website_id];
        $domain_origin = explode('/search', $domain)[0];
        $link = $domain.'&page=';
        $page = 1;
        $data = array();
        do {
            echo $page . '-page' . "\n";
            $url = $link . $page;
            $curent_page = $page;

            $client = new \Goutte\Client();
            $response = $client->request('GET', $url);
            $crawler = $response;

            // kiem tra xem co ton tai product nao ở page hiện tại hay không
            $products = ($crawler->filter('section.site-content div.container div.col-md-3')->count() > 0) ?
                $crawler->filter('section.site-content div.container div.col-md-3')->count() : 0;
            if ($products > 0) {
                $crawler->filter('section.site-content div.container div.col-md-3')
                    ->each(function ($node) use (&$data, &$website_id, &$template_id, &$store_id, &$url, &$domain_origin) {
                        $link = $domain_origin.trim($node->filter('a')->attr('href'));
                        $name = trim($node->filter('a')->text());
                        $category_name = 'Boots';
                        $data[] = [
                            'category_name' => $category_name,
                            'link' => $link,
                            'website_id' => $website_id,
                            'website' => $url,
                            'template_id' => $template_id,
                            'store_id' => $store_id,
                            'status' => 0,
                            'created_at' => date("Y-m-d H:i:s"),
                            'updated_at' => date("Y-m-d H:i:s")
                        ];
                    });
            }

            //Phần cuối cùng. Không được chèn thêm ở đây nữa
            // kiểm tra xem đây có phải là trang cuối cùng hay không
            $check = $crawler->filter('ul.pager li:nth-last-child(1) .disabled')->count();
            if ($check == 0)
            {
                $next_page_link = $crawler->filter('ul.pager li:nth-last-child(1) a')->attr('href');
                $next_page = preg_replace("/[^0-9]/", '', $next_page_link);
                $page = $next_page;
            } else {
                $next_page = 0;
            }
        } while ($next_page > $curent_page);
        // Lưu dữ liệu vào database
        $this->saveTemplate($data, $woo_template_id, $domain);
    }

    private function scanMerchKing_Zolagifts($website_id, $template_id, $store_id, $woo_template_id, $price_shoes)
    {
        $website = website();
        $domain = $website[$website_id];
        $domain_origin = rtrim($domain, "/");
        $link = $domain.'?page=';
        $page = 1;
        $data = array();
        do {
            echo $page . '-page' . "\n";
            $url = $link . $page;
            $curent_page = $page;

            $client = new \Goutte\Client();
            $response = $client->request('GET', $url);
            $crawler = $response;

            // kiem tra xem co ton tai product nao ở page hiện tại hay không
            $products = ($crawler->filter('section.site-content div.container div.col-md-3')->count() > 0) ?
                $crawler->filter('section.site-content div.container div.col-md-3')->count() : 0;
            if ($products > 0) {
                $crawler->filter('section.site-content div.container div.col-md-3')
                    ->each(function ($node) use (&$data, &$website_id, &$template_id, &$store_id, &$url, &$domain_origin, &$price_shoes) {
                        $link = $domain_origin.trim($node->filter('a')->attr('href'));
                        $name = trim($node->filter('a')->text());
                        $price = preg_replace("/[^0-9]/", '', trim($node->filter('.slash-price')->text()));
                        if (($price == $price_shoes) && (strpos(strtolower($name), 'boot') !== false))
                        {
                            $tag = explode('leather boot', strtolower($name))[0];
                            $tag_name = preg_replace('/[^a-z\d]/i', '-', sanitizer($tag));
                            $tag_name = rtrim($tag_name,'-');
                            $category_name = 'Boots';
                            $data[] = [
                                'category_name' => $category_name,
                                'tag_name' => $tag_name,
                                'link' => $link,
                                'website_id' => $website_id,
                                'website' => $url,
                                'template_id' => $template_id,
                                'store_id' => $store_id,
                                'status' => 0,
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                        }
                    });
            }

            //Phần cuối cùng. Không được chèn thêm ở đây nữa
            // kiểm tra xem đây có phải là trang cuối cùng hay không
            $check = $crawler->filter('ul.pager li:nth-last-child(1) .disabled')->count();
            if ($check == 0)
            {
                $next_page_link = $crawler->filter('ul.pager li:nth-last-child(1) a')->attr('href');
                $next_page = preg_replace("/[^0-9]/", '', $next_page_link);
                $page = $next_page;
            } else {
                $next_page = 0;
            }
        } while ($next_page > $curent_page);
        // Lưu dữ liệu vào database
        $this->saveTemplate($data, $woo_template_id, $domain);
    }

    private function checkProductExist($template_id, $store_id)
    {
        $products = \DB::table('scrap_products')
            ->where('template_id',$template_id)
            ->where('store_id',$store_id)
            ->pluck('link')
            ->toArray();
        return $products;
    }

    private function scanMerchKing_getTag($website_id, $template_id, $store_id, $woo_template_id)
    {
        echo "<pre>";
        // so sanh product cu. trung thi se k lay nua
        $products_old = $this->checkProductExist($template_id, $store_id);
        $website = website();
        $domain = $website[$website_id];
        $domain_origin = explode('/search', $domain)[0];
        $link = $domain.'&page=';
        $page = 1;
        $data = array();
        do {
            echo $page . '-page' . "\n";
            $url = $link . $page;
            $curent_page = $page;

            $client = new \Goutte\Client();
            $response = $client->request('GET', $url);
            $crawler = $response;

            // kiem tra xem co ton tai product nao ở page hiện tại hay không
            $products = ($crawler->filter('section.site-content div.container div.col-md-3')->count() > 0) ?
                $crawler->filter('section.site-content div.container div.col-md-3')->count() : 0;
            if ($products > 0) {
                $crawler->filter('section.site-content div.container div.col-md-3')
                    ->each(function ($node) use (&$data, &$website_id, &$template_id, &$store_id, &$url, &$domain_origin,
                        &$products_old) {
                        $link = $domain_origin . trim($node->filter('a')->attr('href'));
                        if (!in_array($link, $products_old))
                        {
                            $name = trim($node->filter('a')->text());
                            $tag = explode(' ', strtolower($name))[0];
                            $tag_name = preg_replace('/[^a-z\d]/i', '-', sanitizer($tag));
                            $tag_name = rtrim($tag_name, '-');
                            $category_name = 'Fleece Blanket';
                            $data[] = [
                                'category_name' => $category_name,
                                'tag_name' => $tag_name,
                                'link' => $link,
                                'website_id' => $website_id,
                                'website' => $url,
                                'template_id' => $template_id,
                                'store_id' => $store_id,
                                'status' => 0,
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                        }
                    });
            }

            //Phần cuối cùng. Không được chèn thêm ở đây nữa
            // kiểm tra xem đây có phải là trang cuối cùng hay không
            $check = $crawler->filter('ul.pager li:nth-last-child(1) .disabled')->count();
            if ($check == 0)
            {
                $next_page_link = $crawler->filter('ul.pager li:nth-last-child(1) a')->attr('href');
                $next_page = preg_replace("/[^0-9]/", '', $next_page_link);
                $page = $next_page;
            } else {
                $next_page = 0;
            }
        } while ($next_page > $curent_page);
        // Lưu dữ liệu vào database
        $this->saveTemplate($data, $woo_template_id, $domain);
    }

    private function scanMerchKing_getTag_excludeText($website_id, $template_id, $store_id, $woo_template_id, $text_exclude)
    {
        echo "<pre>";
        // so sanh product cu. trung thi se k lay nua
        $products_old = $this->checkProductExist($template_id, $store_id);
        $website = website();
        $domain = $website[$website_id];
        $domain_origin = explode('/search', $domain)[0];
        $link = $domain.'&page=';
        echo $link."\n";
        $page = 1;
        $data = array();
        $text_exclude = ucwords($text_exclude);
        $links = array();
        do {
            echo $page . '-page' . "\n";
            $url = $link . $page;
            $curent_page = $page;
            $client = new \Goutte\Client();
            $response = $client->request('GET', $url);
            $crawler = $response;

            // kiem tra xem co ton tai product nao ở page hiện tại hay không
            $products = ($crawler->filter('section.site-content div.container div.col-md-3')->count() > 0) ?
                $crawler->filter('section.site-content div.container div.col-md-3')->count() : 0;
            if ($products > 0) {
                $crawler->filter('section.site-content div.container div.col-md-3')
                    ->each(function ($node) use (&$data, &$website_id, &$template_id, &$store_id, &$url, &$domain_origin,
                        &$products_old, &$links, &$text_exclude) {
                        $link = $domain_origin . trim($node->filter('a')->attr('href'));
                        if (!in_array($link, $products_old))
                        {
                            if (!in_array($link, $links))
                            {
                                $links[] = $link;
                                $name = ucwords(trim($node->filter('a')->text()));
                                $name = str_replace($text_exclude, '', $name);
                                $tmp_tag = explode(' ', strtolower($name));
                                if (sizeof($tmp_tag) > 0 && $tmp_tag[0] != '')
                                {
                                    $tag = explode(' ', strtolower($name))[0];
                                } else {
                                    $tag = $tmp_tag[1];
                                }
                                $tag_name = preg_replace('/[^a-z\d]/i', '-', sanitizer($tag));
                                $tag_name = rtrim($tag_name, '-');
                                $category_name = 'Fleece Blanket';
                                $data[] = [
                                    'category_name' => $category_name,
                                    'tag_name' => $tag_name,
                                    'link' => $link,
                                    'website_id' => $website_id,
                                    'website' => $url,
                                    'template_id' => $template_id,
                                    'store_id' => $store_id,
                                    'status' => 0,
                                    'created_at' => date("Y-m-d H:i:s"),
                                    'updated_at' => date("Y-m-d H:i:s")
                                ];
                            }
                        }
                    });
            }

            //Phần cuối cùng. Không được chèn thêm ở đây nữa
            // kiểm tra xem đây có phải là trang cuối cùng hay không
            $check = $crawler->filter('ul.pager li:nth-last-child(1) .disabled')->count();
            if ($check == 0)
            {
                $next_page_link = $crawler->filter('ul.pager li:nth-last-child(1) a')->attr('href');
                $text_split = 'page';
                // nếu tồn tại text split
                if (strpos($next_page_link, $text_split) !== false) {
                    $tmp_page = explode($text_split, $next_page_link)[1];
                    $next_page = preg_replace("/[^0-9]/", '', $tmp_page);
                    $page = $next_page;
                }
            } else {
                $next_page = 0;
            }
        } while ($next_page > $curent_page);
        // Lưu dữ liệu vào database
        $this->saveTemplate($data, $woo_template_id, $domain);
    }

    private function preDataBeforScrap($pre_data) {
        $info = array();
        if ($pre_data['sku_auto_id'] > 0)
        {
            $info = getInfoSkuName($pre_data['sku_auto_id']);
        }
        return $info;
    }

    private function autoScanCustomPlatform($pre_data ,$website_id, $template_id, $store_id, $woo_template_id, $category_name ,$text_exclude, $domain)
    {
        if ($pre_data['custom_template_id'] > 0)
        {
            $info = \DB::table('custom_templates')
                ->select('*')
                ->where('id',$pre_data['custom_template_id'])
                ->first();
            $web_link = $info->web_link;
            $page_string = $info->page_string;
            $title_catalog_class = $info->title_catalog_class;
            $title_product_class = $info->title_product_class;
            $product_tag = $info->product_tag;
            $domain_origin = $info->domain_origin;
            $page_catalog_class = $info->page_catalog_class;
            $last_page_catalog_class = $info->last_page_catalog_class;
            $page_exclude_string = $info->page_exclude_string;
            if (strlen($page_string) > 0)
            {
                $tmp_page = explode($page_string.'1', $web_link);
                $link_first = trim($tmp_page[0]);
                $link_last = trim($tmp_page[1]);
            } else {
                $link_first = $web_link;
                $link_last = '';
            }
            // so sanh product cu. trung thi se k lay nua
            $products_old = $this->checkProductExist($template_id, $store_id);
            if (strlen($page_catalog_class) > 0 && strlen($last_page_catalog_class) > 0)
            {
                $page = 1;
            } else {
                $page = '';
            }
            $data = array();
            $text_exclude = ucwords($text_exclude);
            $pre_info = $this->preDataBeforScrap($pre_data);
            $i = 1;
            $links = array();
            do {
                echo $page . '-page' . "\n";
                $url = $link_first.$page_string.$page.$link_last;
                $curent_page = $page;
                $client = new \Goutte\Client();
                $response = $client->request('GET', $url);
                $crawler = $response;
                // kiem tra xem co ton tai product nao ở page hiện tại hay không
                $products = ($crawler->filter($title_catalog_class)->count() > 0) ? $crawler->filter($title_catalog_class)->count() : 0;
                if ($products > 0) {
                    try {
                        $crawler->filter($title_catalog_class)
                            ->each(function ($node) use (&$data, &$website_id, &$template_id, &$store_id, &$url, &$domain_origin,
                                &$products_old, &$links, &$category_name ,&$text_exclude, &$i, &$pre_info, &$title_product_class,
                                &$product_tag
                            ) {
                                $link = $domain_origin . trim($node->filter('a')->attr('href'));
                                if (!in_array($link, $products_old))
                                {
                                    if (!in_array($link, $links))
                                    {
                                        // kiểm tra xem có sku tự động hay không
                                        $sku_auto_string = null;
                                        if (sizeof($pre_info) > 0)
                                        {
                                            $count = $pre_info['count'] + $i;
                                            $sku_auto_string = $pre_info['sku'].$count.$pre_info['last_prefix'];
                                        }
                                        // end kiểm tra xem có sku tự động hay không
                                        $links[] = $link;
                                        $name = ucwords(trim($node->filter($title_product_class)->text()));
                                        $name = str_replace($text_exclude, '', $name);
                                        if (strlen($product_tag) > 0)
                                        {
                                            $tag_name = $product_tag;
                                        } else {
                                            $tmp_tag = explode(' ', strtolower($name));
                                            if (sizeof($tmp_tag) > 0 && $tmp_tag[0] != '')
                                            {
                                                $tag = explode(' ', strtolower($name))[0];
                                            } else {
                                                $tag = $tmp_tag[1];
                                            }
                                            $tag_name = preg_replace('/[^a-z\d]/i', '-', sanitizer($tag));
                                            $tag_name = rtrim($tag_name, '-');
                                        }

                                        $data[] = [
                                            'category_name' => $category_name,
                                            'tag_name' => $tag_name,
                                            'link' => $link,
                                            'website_id' => $website_id,
                                            'website' => $url,
                                            'template_id' => $template_id,
                                            'store_id' => $store_id,
                                            'sku_auto_string' => $sku_auto_string,
                                            'status' => 0,
                                            'created_at' => date("Y-m-d H:i:s"),
                                            'updated_at' => date("Y-m-d H:i:s")
                                        ];
                                        $i++;
                                    }
                                }
                            });
                    } catch (\Exception $e) {}
                } else {
                    logfile_system('Website: '.$domain.' Không tồn tại sản phẩm nào với class : '.$title_catalog_class);
                }

                //Phần cuối cùng. Không được chèn thêm ở đây nữa
                // Nếu không khai báo pagination class thì chỉ quét trang đầu tiên
                if (strlen($last_page_catalog_class) > 0 && strlen($page_catalog_class) > 0)
                {
                    // kiểm tra xem đây có phải là trang cuối cùng hay không
                    $check = $crawler->filter($last_page_catalog_class)->count();
                    if ($check == 0)
                    {
                        try {
                            $next_page_link = $crawler->filter($page_catalog_class)->attr('href');
                            $page_link = str_replace($page_exclude_string, '', $next_page_link);
                            $next_page = preg_replace("/[^0-9]/", '', $page_link);
                            $page = $next_page;
                        } catch (\Exception $e) {
                            $next_page = 0;
                        }
                    } else {
                        $next_page = 0;
                    }
                } else {
                    $next_page = 0;
                }
            } while ($next_page > $curent_page);
            // Lưu dữ liệu vào database
            $this->saveTemplate($data, $woo_template_id, $domain);
        } else {
            logfile_system('-- Platform không tồn tại info về class custom. Chuyển sang website khác');
            \DB::table('websites')->where('id',$website_id)->update(['status' => 2]);
            \DB::table('woo_templates')->where('id',$woo_template_id)->update(['status' => 25]);
        }
    }

    private function autoScanMerchKing($pre_data, $website_id, $template_id, $store_id, $woo_template_id, $category_name, $text_exclude, $domain)
    {
        // so sanh product cu. trung thi se k lay nua
        $products_old = $this->checkProductExist($template_id, $store_id);
        $domain_origin = explode('/search', $domain)[0];
        $link = $domain.'&page=';
        echo $link."\n";
        $page = 1;
        $data = array();
        $text_exclude = ucwords($text_exclude);
        $pre_info = $this->preDataBeforScrap($pre_data);
        $i = 1;
        $links = array();
        do {
            echo $page . '-page' . "\n";
            $url = $link . $page;
            $curent_page = $page;
            $client = new \Goutte\Client();
            $response = $client->request('GET', $url);
            $crawler = $response;
            // kiem tra xem co ton tai product nao ở page hiện tại hay không
            $products = ($crawler->filter('section.site-content div.container div.col-md-3')->count() > 0) ?
                $crawler->filter('section.site-content div.container div.col-md-3')->count() : 0;
            if ($products > 0) {
                $crawler->filter('section.site-content div.container div.col-md-3')
                    ->each(function ($node) use (&$data, &$website_id, &$template_id, &$store_id, &$url, &$domain_origin,
                        &$products_old, &$links, &$category_name ,&$text_exclude, &$i, &$pre_info) {
                        $link = $domain_origin . trim($node->filter('a')->attr('href'));
                        if (!in_array($link, $products_old))
                        {
                            if (!in_array($link, $links))
                            {
                                // kiểm tra xem có sku tự động hay không
                                $sku_auto_string = null;
                                if (sizeof($pre_info) > 0)
                                {
                                    $count = $pre_info['count'] + $i;
                                    $sku_auto_string = $pre_info['sku'].$count.$pre_info['last_prefix'];
                                }
                                // end kiểm tra xem có sku tự động hay không
                                $links[] = $link;
                                $name = ucwords(trim($node->filter('a')->text()));
                                $name = str_replace($text_exclude, '', $name);
                                $tmp_tag = explode(' ', strtolower($name));
                                if (sizeof($tmp_tag) > 0 && $tmp_tag[0] != '')
                                {
                                    $tag = explode(' ', strtolower($name))[0];
                                } else {
                                    $tag = $tmp_tag[1];
                                }
                                $tag_name = preg_replace('/[^a-z\d]/i', '-', sanitizer($tag));
                                $tag_name = rtrim($tag_name, '-');
                                $data[] = [
                                    'category_name' => $category_name,
                                    'tag_name' => $tag_name,
                                    'link' => $link,
                                    'website_id' => $website_id,
                                    'website' => $url,
                                    'template_id' => $template_id,
                                    'store_id' => $store_id,
                                    'sku_auto_string' => $sku_auto_string,
                                    'status' => 0,
                                    'created_at' => date("Y-m-d H:i:s"),
                                    'updated_at' => date("Y-m-d H:i:s")
                                ];
                                $i++;
                            }
                        }
                    });
            }

            //Phần cuối cùng. Không được chèn thêm ở đây nữa
            // kiểm tra xem đây có phải là trang cuối cùng hay không
            $check = $crawler->filter('ul.pager li:nth-last-child(1) .disabled')->count();
            if ($check == 0)
            {
                $next_page_link = $crawler->filter('ul.pager li:nth-last-child(1) a')->attr('href');
                $text_split = 'page';
                // nếu tồn tại text split
                if (strpos($next_page_link, $text_split) !== false) {
                    $tmp_page = explode($text_split, $next_page_link)[1];
                    $next_page = preg_replace("/[^0-9]/", '', $tmp_page);
                    $page = $next_page;
                } else {
                    $next_page = 0;
                }
            } else {
                $next_page = 0;
            }
        } while ($next_page > $curent_page);
        // Lưu dữ liệu vào database
        $this->saveTemplate($data, $woo_template_id, $domain);
    }

    private function autoScanEsty($pre_data, $website_id, $template_id, $store_id, $woo_template_id, $category_name, $text_exclude, $domain, $first_title)
    {
        // so sanh product cu. trung thi se k lay nua
        $products_old = $this->checkProductExist($template_id, $store_id);
        $link = $domain.'&page=';
        echo $link."\n";
        $page = 1;
        $data = array();
        $text_exclude = ucwords($text_exclude);
        $tag_name = ucwords($first_title);
        $pre_info = $this->preDataBeforScrap($pre_data);
        $i = 1;
        $links = array();
        do {
            echo $page . '-page' . "\n";
            $url = $link . $page;
            $curent_page = $page;
            $client = new \Goutte\Client();
            $response = $client->request('GET', $url);
            $crawler = $response;

            // kiem tra xem co ton tai product nao ở page hiện tại hay không
            $products = ($crawler->filter('ul.listing-cards li.v2-listing-card')->count() > 0) ?
                $crawler->filter('ul.listing-cards li.v2-listing-card')->count() : 0;
            if ($products > 0) {
                $crawler->filter('ul.listing-cards li.block-grid-item')
                    ->each(function ($node) use (&$data, &$website_id, &$template_id, &$store_id, &$url,
                        &$products_old, &$links, &$category_name ,&$text_exclude, &$tag_name, &$i, &$pre_info) {
                        $link = trim($node->filter('a.listing-link')->attr('href'));
                        if (!in_array($link, $products_old))
                        {
                            if (!in_array($link, $links))
                            {
                                // kiểm tra xem có sku tự động hay không
                                $sku_auto_string = null;
                                if (sizeof($pre_info) > 0)
                                {
                                    $count = $pre_info['count'] + $i;
                                    $sku_auto_string = $pre_info['sku'].$count.$pre_info['last_prefix'];
                                }
                                // end kiểm tra xem có sku tự động hay không
                                $links[] = $link;
                                $name = ucwords(trim($node->filter('a')->text()));
                                $name = str_replace($text_exclude, '', $name);
                                $data[] = [
                                    'category_name' => $category_name,
                                    'tag_name' => $tag_name,
                                    'link' => $link,
                                    'website_id' => $website_id,
                                    'website' => $url,
                                    'template_id' => $template_id,
                                    'store_id' => $store_id,
                                    'status' => 0,
                                    'created_at' => date("Y-m-d H:i:s"),
                                    'updated_at' => date("Y-m-d H:i:s")
                                ];
                                $i++;
                            }
                        }
                    });
            }

            //Phần cuối cùng. Không được chèn thêm ở đây nữa
            // kiểm tra xem đây có phải là 1 page hay không
            $one_page = $crawler->filter('ul.wt-action-group')->count();
            if ($one_page > 0) {
                // kiểm tra xem đây có phải là trang cuối cùng hay không
                $check = $crawler->filter('.wt-action-group__item-container:nth-last-child(1) > a.wt-is-disabled')->count();
                if ($check == 0) {
                    $next_page = $crawler->filter('.wt-action-group__item-container:nth-last-child(1) > a')->attr('data-page');
                    $page = $next_page;
                }
            } else {
                $next_page = 0;
            }
        } while ($next_page > $curent_page);
        // Lưu dữ liệu vào database
        $this->saveTemplate($data, $woo_template_id, $domain);
    }

    private function scanCreationsLaunch_getTag($website_id, $template_id, $store_id, $woo_template_id, $category_name)
    {
        $website = website();
        $domain = $website[$website_id];
        $domain_origin = explode('/search', $domain)[0];
        $link = $domain.'&page=';
        $page = 1;
        $data = array();
        do {
            echo $page . '-page' . "\n";
            $url = $link . $page;
            $curent_page = $page;
            $client = new \Goutte\Client();
            $response = $client->request('GET', $url);
            $crawler = $response;

            // kiem tra xem co ton tai product nao ở page hiện tại hay không
            $products = ($crawler->filter('ul.list-view-items li.list-view-item ')->count() > 0) ?
                $crawler->filter('ul.list-view-items li.list-view-item ')->count() : 0;
            if ($products > 0) {
                $crawler->filter('ul.list-view-items li.list-view-item')
                    ->each(function ($node) use (&$data, &$website_id, &$template_id, &$store_id, &$url, &$domain_origin, &$category_name) {
                        $category_name = (strlen($category_name) > 0) ? $category_name : 'Shoes' ;
                        $name = trim($node->filter('span.product-card__title')->text());
                        if (strpos(strtolower($name), strtolower($category_name)) !== false) {
                            $link = $domain_origin . trim($node->filter('a')->attr('href'));
                            $tag = explode(' ', strtolower($name))[0];
                            $tag_name = preg_replace('/[^a-z\d]/i', '-', sanitizer($tag));

                            $data[] = [
                                'category_name' => $category_name,
                                'tag_name' => $tag_name,
                                'link' => $link,
                                'website_id' => $website_id,
                                'website' => $url,
                                'template_id' => $template_id,
                                'store_id' => $store_id,
                                'status' => 0,
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                        }
                    });
            }
            //Phần cuối cùng. Không được chèn thêm ở đây nữa
            // kiểm tra xem đây có phải là trang cuối cùng hay không
            $check = $crawler->filter('.pagination li:nth-last-child(1) a')->count();
            if ($check > 0)
            {
                $next_page_link = $crawler->filter('.pagination li:nth-last-child(1) a')->attr('href');
                $next_page = preg_replace("/[^0-9]/", '', $next_page_link);
                $page = $next_page;
            } else {
                $next_page = 0;
            }
        } while ($next_page > $curent_page);
        // Lưu dữ liệu vào database
        $this->saveTemplate($data, $woo_template_id, $domain);
    }
    /*End website merch king*/
}
