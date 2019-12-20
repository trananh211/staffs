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
        logfile('========================= [ Bắt đầu cào website ] ===========================');
        try {
            $check = $this->checkWebsite();
            //Nếu tồn tại website đang cần được scrap
            if (is_array($check)) {
                $website_id = $check[0]->website_id;
                $template_id = $check[0]->template_id;
                $store_id = $check[0]->store_id;
                $woo_template_id = $check[0]->id;
                switch ($website_id) {
                    case 1:
                        $this->scanNamestories($website_id, $template_id, $store_id, $woo_template_id);
                        break;
                    case 2:
                    case 3:
                        $this->scanEsty($website_id, $template_id, $store_id, $woo_template_id);
                        break;
                    case 4:
                    case 5:
                        $this->scanPercre($website_id, $template_id, $store_id, $woo_template_id);
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
                        $this->scanMerchKing_getTag($website_id, $template_id, $store_id, $woo_template_id);
                        break;
                    case 10:
                        $this->scanEsty_collection($website_id, $template_id, $store_id, $woo_template_id,'Personalized');
                        break;
                    default:
                        $str = "-- Không có website nào cần được cào.";
                        logfile($str);
                }
            } else {
                $str = '-- Không tồn tại website nào cần được scrap dữ liệu.';
                logfile($str);
            }
        } catch (\Exception $e) {
            logfile($e->getMessage());
        }
        logfile('========================= [ Kết thúc cào website ] ===========================');
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
                logfile('Insert thành công dữ liệu ' . sizeof($data) . ' link sản phẩm website '.$domain);
            }
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
                logfile('Insert thành công dữ liệu ' . sizeof($data) . ' link sản phẩm website namestories.com');
            }
        }
    }
    /* End website namestories.com */

    /*website percre*/
    private function scanPercre($website_id, $template_id, $store_id, $woo_template_id)
    {
        $website = website();
        $domain = $website[$website_id];
        $tmp_link = explode('https://percre.com/',$domain);
        $link = 'https://percre.com/page/';
        $page = 1;
        $data = array();
        do {
            echo $page . '-page' . "\n";
            $url = $link . $page .'/'.$tmp_link[1];
            $curent_page = $page;
            $client = new \Goutte\Client();
            $response = $client->request('GET', $url);
            $crawler = $response;

            // kiem tra xem co ton tai product nao ở page hiện tại hay không
            $products = ($crawler->filter('ul.products li.wvs-pro-product')->count() > 0) ?
                $crawler->filter('ul.products li.wvs-pro-product')->count() : 0;
            if ($products > 0) {
                $crawler->filter('ul.products li.wvs-pro-product')
                    ->each(function ($node) use (&$data, &$website_id, &$template_id, &$store_id, &$url) {
                        $link = trim($node->filter('a')->attr('href'));
                        $name = trim($node->filter('h2.woocommerce-loop-product__title')->text());
                        //nếu tồn tại chữ shoes trong title thì mới cào. không thì bỏ qua
                        if (strpos(strtolower($name),'shoes') !== false)
                        {
                            $category_name = 'Shoes';
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
                        }
                    });
            }

            //Phần cuối cùng. Không được chèn thêm ở đây nữa
            // kiểm tra xem đây có phải là trang cuối cùng hay không
            $check = $crawler->filter('ul.page-numbers li:nth-last-child(1) a')->count();
            if ($check != 0)
            {
                $next_page_link = $crawler->filter('ul.page-numbers li:nth-last-child(1) a')->attr('href');
                $next_page = preg_replace("/[^0-9]/", '', $next_page_link);
                $page = $next_page;
            } else {
                $next_page = 0;
            }
        } while ($next_page > $curent_page);
        // Lưu dữ liệu vào database
        $this->saveTemplate($data, $woo_template_id, $domain);
    }
    /*End website percre*/

    /*website esty store*/
    private function scanEsty($website_id, $template_id, $store_id, $woo_template_id)
    {
        //Get categories from esty
        $website = website();
        $link = $website[$website_id];
        $data = array();
        $client = new \Goutte\Client();
        $response = $client->request('GET', $link);
        $crawler = $response;

        // kiem tra xem co ton tai category nao ở shop hay không
        $categories = ($crawler->filter('div.shop-home-wider-sections ul.list-nav > li')->count() > 0) ?
            $crawler->filter('div.shop-home-wider-sections ul.list-nav > li')->count() : 0;
        $ar_category = array();
        if ($categories > 0) {
            $crawler->filter('div.shop-home-wider-sections ul.list-nav > li')
                ->each(function ($node) use (&$ar_category) {
                    $link = trim($node->filter('a')->attr('href'));
                    $name = trim($node->filter('a')->text());
                    $name = preg_replace("/&#?[a-z0-9]+;/i", "", $name);
                    $count = trim($node->filter('a span.badge')->text());
                    $name = trim(rtrim(strip_tags($name), $count));
                    $ar_category[$name] = $link;
                });
            if (sizeof($ar_category) > 0) {
                logfile('-- Phát hiện ' . (sizeof($ar_category) - 1) . ' categories cần được cào.');
                $i = 1;
                foreach ($ar_category as $category_name => $link) {
                    if (strtolower($category_name) === 'all') {
                        continue;
                    }
                    logfile('--- ' . $i . " Category: " . $category_name);
                    $dt = array();
                    $url = 'https://www.etsy.com' . $link;
                    $dt = $this->scanCollectionEsty($client, $url, $category_name, $website_id, $template_id, $store_id, $woo_template_id);
                    $data = array_merge($data, $dt);
                    $i++;
                }
            }
        } else {
            logfile('-- Không tồn tại categories nào ở shop ' . $link);
        }

        if (sizeof($data) > 0) {
            $insert = \DB::table('scrap_products')->insert($data);
            if ($insert) {
                \DB::table('woo_templates')->where('id', $woo_template_id)->update([
                    'status' => 1,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                logfile('-- Insert thành công dữ liệu ' . sizeof($data) . ' link sản phẩm website '.$link);
            }
        }
    }

    private function scanCollectionEsty($client, $url, $category_name, $website_id, $template_id, $store_id, $woo_template_id)
    {
        $page = 1;
        $data = [];
        do {
            $str = '';
            $str .= '---- Page- ' . $page;
            $curent_page = $page;
            $link_collection = $url . "&page=" . $curent_page;
            $response = $client->request('GET', $link_collection);
            $str .= $link_collection . " - ";
            $crawler = $response;

            // kiem tra xem co ton tai product nao ở page hiện tại hay không
            $products = ($crawler->filter('ul.listing-cards li.v2-listing-card')->count() > 0) ?
                $crawler->filter('ul.listing-cards li.v2-listing-card')->count() : 0;
            if ($products > 0) {
                $crawler->filter('ul.listing-cards li.block-grid-item')
//                $crawler->filter('ul.listing-cards li.v2-listing-card')
                    ->each(function ($node) use (&$data, &$category_name, &$website_id, &$template_id, &$store_id, &$url) {
                        $link = trim($node->filter('a.listing-link')->attr('href'));
//                        $name = trim($node->filter('div.v2-listing-card__info h2.text-body')->text());
//                        $key = intval(preg_replace('/[^0-9.]/','',$link));
                        preg_match_all('!\d+!', $link, $ar_number);
                        $key = implode($ar_number[0]);
                        $data[] = [
                            'category_name' => preg_replace('/[^a-z\d]/i', '-', sanitizer($category_name)),
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
                $str .= ' - Product ' . sizeof($data);
                logfile($str);
            } else {
                logfile('---- Không có product nào ở trang này. Bỏ qua');
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
        return $data;
    }

    private function scanEsty_collection($website_id, $template_id, $store_id, $woo_template_id ,$category_name)
    {
        //Get categories from esty
        $website = website();
        $link = $website[$website_id];
        $domain = $link;
        $data = array();
        $client = new \Goutte\Client();
        $page = 3;
        do {
            $str = '';
            $str .= '---- Page- ' . $page;
            $curent_page = $page;
            $url = $link . "&page=" . $curent_page;
            $response = $client->request('GET', $url);
            $crawler = $response;

            // kiem tra xem co ton tai product nao ở page hiện tại hay không
            $products = ($crawler->filter('ul.listing-cards li.v2-listing-card')->count() > 0) ?
                $crawler->filter('ul.listing-cards li.v2-listing-card')->count() : 0;
            if ($products > 0) {
                $crawler->filter('ul.listing-cards li.block-grid-item')
                    ->each(function ($node) use (&$data, &$website_id, &$template_id, &$store_id, &$url, &$category_name) {
                        $link = trim($node->filter('a.listing-link')->attr('href'));
                        $data[] = [
                            'category_name' => preg_replace('/[^a-z\d]/i', '-', sanitizer($category_name)),
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
    /*End website esty store*/

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

    private function scanMerchKing_getTag($website_id, $template_id, $store_id, $woo_template_id)
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
                        $link = $domain_origin . trim($node->filter('a')->attr('href'));
                        $name = trim($node->filter('a')->text());
                        $tag = explode(' ', strtolower($name))[0];
                        $tag_name = preg_replace('/[^a-z\d]/i', '-', sanitizer($tag));
                        $tag_name = rtrim($tag_name, '-');
                        $category_name = 'Blankets';
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
    /*End website merch king*/
}
