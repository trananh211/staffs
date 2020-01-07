<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Storage;

class DynamicAds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ads:dynamic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get content to sheet google';

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
        echo "<pre>";
        $this->getWebsite();
    }

    private function getWebsite()
    {
        $website = dynamic_website();
        $domain = $website[1];
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
                        $name = ucwords(strtolower(trim($node->filter('a')->text())));
                        $tmp_img = trim($node->filter('a img')->attr('src'));
                        $img = 'http:'.(explode('&width=',$tmp_img)[0]);
                        $price = trim($node->filter('h4 .slash-price')->text());
                        $h4 = trim($node->filter('.slash-price')->parents()->text());
                        $sale_price = trim(explode($price, $h4)[1]);
                        $category = 'Fleece Blanket';
                        $data[] = [
                            'link' => $link,
                            'name' => $name,
                            'img' => $img,
                            'category' => $category,
                            'price' => $price,
                            'sale_price' => $sale_price
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
        $this->makeFile($data, $domain_origin);
    }

    private function makeFile($data, $domain)
    {
        if (sizeof($data) > 0)
        {
            $str = '';
            $str .= "ID\t Item title\t Final URL\t Image URL\t Item category\t	Price\t Sale price\n";
            foreach($data as $v)
            {
                $price = str_replace("$","",$v['price'])." USD";
                $sale_price = str_replace("$","",$v['sale_price'])." USD";
                $url = $v['link'];
                $str .= "{$v['link']}\t {$v['name']}\t {$url}\t {$v['img']}\t {$v['category']}\t {$price}\t {$sale_price}\n";
            }
            $a = Storage::disk('local')->put('dynamic_ads_remarketing.txt', $str);
            var_dump($a);
        }
    }
}
