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

    protected $website = [
        '1' => 'https://namestories.com'
    ];

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
                        $this->scanEsty($website_id, $template_id, $store_id, $woo_template_id);
                        break;
                    default:
                        $str = "-- Không có website nào cần được cào.";
                        logfile($str);
                        echo $str;
                }
            } else {
                $str = '-- Khong ton tai website nào đang được scrap';
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

    /*website namestories.com*/
    private function scanNamestories($website_id, $template_id, $store_id, $woo_template_id)
    {
        $website = $this->website;
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
//        $this->getProduct($data);
    }

    private function getProduct($data)
    {
        $client = new \Goutte\Client();
        $db = array();
        foreach ($data as $key => $dt) {
            $link = $dt['link'];
            $response = $client->request('GET', $link);
            $crawler = $response;
            if ($crawler->filter('ul.product-single__thumbnails')->count() > 0) {
                $crawler->filter('ul.product-single__thumbnails .grid__item')->each(function ($node) use (&$data, &$key) {
                    $image = trim($node->filter('a')->attr('href'));
                    $data[$key]['image'][] = $image;
                });
            }
        }
        print_r($data);
    }
    /* End website namestories.com */

    /*website esty store*/
    private function scanEsty($website_id, $template_id, $store_id, $woo_template_id)
    {
        $website = website();
        $link = $website[$website_id] . "=";
        $page = 15;
        $data = array();
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
                $crawler->filter('ul.listing-cards li.v2-listing-card')
                    ->each(function ($node) use (&$data, &$domain, &$website_id, &$template_id, &$store_id, &$website) {
                        $link = trim($node->filter('a.listing-link')->attr('href'));
                        $name = trim($node->filter('div.v2-listing-card__info h2.text-body')->text());
                        $category_name = categories()[$website_id];
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
            echo sizeof($data) . "\n";
            //Phần cuối cùng. Không được chèn thêm ở đây nữa
            // kiểm tra xem đây có phải là trang cuối cùng hay không
            $check = $crawler->filter('.wt-action-group__item-container:nth-last-child(1) > a.wt-is-disabled')->count();
            if ($check == 0) {
                $next_page = $crawler->filter('.wt-action-group__item-container:nth-last-child(1) > a')->attr('data-page');
                $page = $next_page;
            }
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
    /*End website esty store*/
}
