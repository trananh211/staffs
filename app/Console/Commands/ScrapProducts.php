<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Automattic\WooCommerce\Client;

class ScrapProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrap:product';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrapp all products';

    /*WooCommerce API*/
    protected function getConnectStore($url, $consumer_key, $consumer_secret)
    {
        $woocommerce = new Client(
            $url,
            $consumer_key,
            $consumer_secret,
            [
                'wp_api' => true,
                'version' => 'wc/v3',
                'query_string_auth' => true,
                'verify_ssl' => false
            ]
        );
        return $woocommerce;
    }

    protected $color_name_stories = [
        'light-pink' => 'Light Pink',
        'dark-pink' => 'Dark Pink',
        'lavender' => 'Lavender',
        'deep-purple' => 'Deep Purple',
        'charcoal-gray' => 'Charcoal Gray',
        'light-gray' => 'Light Gray',
        'deep-red' => 'Deep Red',
        'orange' => 'Orange',
        'gold' => 'Gold',
        'hunter-green' => 'Hunter Green',
        'emerald-green' => 'Emerald Green',
        'teal-blue' => 'Teal Blue',
        'light-green' => 'Light Green',
        'navy-blue' => 'Navy Blue',
        'royal-blue' => 'Royal Blue',
        'light-blue' => 'Light Blue'
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
        set_time_limit(0);
        echo "<pre>";
        logfile('========================= [ Bắt đầu scrap products ] ==========================');
        $products = $this->checkProductNew();
        // tồn tại sản phẩm chưa up lên trên shop
        if (is_array($products)) {
            $data = array();
            // gộp các website_id vào chung 1 mảng
            foreach ($products as $value) {
                $data[$value['website_id']][] = $value;
            }
            // gửi mảng data sang bộ phận chuẩn bị dữ liệu
            if (sizeof($data) > 0) {
                $this->preProduct($data);
            }
        } else {
            logfile('Đã hết product để import vào website');
        }
        logfile('========================= [ Kết thúc scrap products ] =========================');
    }

    // kiểm tra xem có sản phẩm mới chưa được up lên shop hay không
    private function checkProductNew()
    {
        echo '-- Kiem tra product new' . "\n";
        $result = false;
        $products = \DB::table('scrap_products as spd')
            ->leftjoin('woo_categories as woo_cat', 'spd.woo_category_id', '=', 'woo_cat.id')
            ->leftjoin('woo_infos as woo_info', 'spd.store_id', '=', 'woo_info.id')
            ->leftjoin('woo_templates as woo_temp', function ($join) {
                $join->on('spd.template_id', '=', 'woo_temp.template_id');
                $join->on('spd.store_id', '=', 'woo_temp.store_id');
            })
            ->select(
                'spd.id', 'spd.website_id', 'spd.store_id', 'spd.website', 'spd.link', 'spd.category_name',
                'woo_cat.woo_category_id',
                'woo_temp.template_path', 'woo_temp.template_id',
                'woo_info.url', 'woo_info.consumer_key', 'woo_info.consumer_secret'
            )
            ->where('spd.status', 0)
            ->orderByRaw('spd.website_id ASC', 'spd.store_id ASC')
            ->limit(5)
            ->get()->toArray();
        if (sizeof($products) > 0) {
            $result = json_decode(json_encode($products), true);
        }
        return $result;
    }

    // kiểm tra categories để lưu vào product
    private function checkCategory()
    {
        logfile('--[ Check Category ] ---------------------------');
        $lst_product_category = \DB::table('scrap_products as spd')
            ->join('woo_infos as woo_info', 'spd.store_id', '=', 'woo_info.id')
            ->select(
                'spd.id as scrap_product_id', 'spd.category_name', 'spd.store_id',
                'woo_info.url', 'woo_info.consumer_key', 'woo_info.consumer_secret'
            )
            ->where([
                ['woo_category_id', '=', NULL]
            ])
            ->limit(99)
            ->get()->toArray();
        if (sizeof($lst_product_category) > 0) {
            $category_store_lst = array();
            $tmp = array();
            $scrap_product_update = array();
            // cập nhật category_id vào woo_product_drivers
            $categories = \DB::table('woo_categories')
                ->select('id', 'store_id', 'slug')
                ->get()->toArray();
            // tạo mảng mới có key là store_id và name folder để so sánh
            $compare_categories = array();
            foreach ($categories as $category) {
                $key = $category->store_id . '_' . $category->slug;
                $compare_categories[$key] = $category->id;
            }
            foreach ($lst_product_category as $val) {
                $val->category_name = strtolower($val->category_name);
                $key_compare = $val->store_id . '_' . $val->category_name;
                //nếu đã tồn tại
                if (array_key_exists($key_compare, $compare_categories)) {
                    $scrap_product_update[$compare_categories[$key_compare]][] = $val->scrap_product_id;
                } else { // nếu chưa tồn tại. lưu vào 1 mảng khác để truy vấn.
                    $tmp[] = $val->category_name;
                    $category_store_lst[$val->store_id] = [
                        'url' => $val->url,
                        'consumer_key' => $val->consumer_key,
                        'consumer_secret' => $val->consumer_secret,
                        'categories' => $tmp
                    ];
                }
            }
            //nếu tồn tại sản phẩm chưa có category
            if (sizeof($category_store_lst) > 0) {
                $woo_categories_data = array();
                foreach ($category_store_lst as $store_id => $info) {
                    $woocommerce = $this->getConnectStore($info['url'], $info['consumer_key'], $info['consumer_secret']);
                    foreach ($info['categories'] as $category_name) {
                        $data = [
                            'slug' => $category_name,
                        ];
                        // kết nối tới woocommerce store để lấy thông tin
                        $result = ($woocommerce->get('products/categories', $data));
                        //nếu không thấy thông tin thì tạo mới
                        if (sizeof($result) == 0) {
                            $data = [
                                'name' => $category_name
                            ];
                            $i = ($woocommerce->post('products/categories', $data));
                            $woo_categories_data[] = [
                                'woo_category_id' => $i->id,
                                'name' => $i->name,
                                'slug' => $i->slug,
                                'store_id' => $store_id,
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                        } else {
                            $woo_categories_data[] = [
                                'woo_category_id' => $result[0]->id,
                                'name' => $result[0]->name,
                                'slug' => $result[0]->slug,
                                'store_id' => $store_id,
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                        }
                    }
                }
                //them toan bo thong tin woo_categories mới get được về database
                if (sizeof($woo_categories_data) > 0) {
                    logfile('-- Tạo mới thông tin woo_categories : ' . sizeof($woo_categories_data) . ' news');
                    \DB::table('woo_categories')->insert($woo_categories_data);
                }
            }

            // Nếu tồn tại thông tin để update vào sản phẩm scrap_products
            if (sizeof($scrap_product_update) > 0) {
                logfile('-- Cập nhật thông tin category vào scrap_products : ' . sizeof($scrap_product_update) . ' update.');
                foreach ($scrap_product_update as $woo_category_id => $list_id) {
                    $data = [
                        'woo_category_id' => $woo_category_id
                    ];
                    \DB::table('scrap_products')->whereIn('id', $list_id)->update($data);
                }
            }
            $result = false;
        } else {
            $result = true;
            logfile('-- Đã chuẩn bị đủ category. Chuyển sang cập nhật category vào từng product.');
        }
        return $result;
    }

    private function preProduct($data)
    {
        $check = $this->checkCategory();
        if ($check) {
            $str = '-- Đã cập nhật xong categories vào toàn bộ product. Chuyển sang tạo mới sản phẩm.';
            logfile($str);
            echo $str . "\n";
            foreach ($data as $website_id => $dt) {
                switch ($website_id) {
                    case 1:
                        $this->getProductNamestories($dt);
                        break;
                    case 2:
                    case 3:
                        $this->getProductEsty($dt);
                        break;
                    default:
                        $str = "-- Không có website nào cần được up sản phẩm.";
                        logfile($str);
                        echo $str;
                }
                break;
            }
        } else {
            $str = '-- Đang cập nhật categories vào scrap_products';
            echo $str . "\n";
            logfile($str);
        }
    }

    /* Begin website namestories.com */

    // chuẩn bị dữ liệu
    private function getProductNamestories($data)
    {
        $client = new \Goutte\Client();
        $db = array();
        $variation_id = array();
        $images = array();
        foreach ($data as $key => $dt) {
            $link = $dt['link'];
            $response = $client->request('GET', $link);
            $crawler = $response;
            if ($crawler->filter('ul.product-single__thumbnails')->count() > 0) {
                //get name
                $product_name = $crawler->filter('div.product-info-wrapper h1[itemprop="name"]')->text();
                $data[$key]['product_name'] = trim(preg_replace('/[^a-z\d ]/i', '', $product_name));
                // get description
                $description = $crawler->filter('div.product-description')->text();
                $data[$key]['description'] = htmlentities($description);
                //get image to variation color
                $crawler->filter('ul.product-single__thumbnails .grid__item')
                    ->each(function ($node) use (&$data, &$key, &$images) {
                        $img = trim($node->filter('a')->attr('href'));
                        $images[] = 'https:' . $img;
                        $data[$key]['images'][]['src'] = 'https:' . $img;
                    });
            }
            $variation_id[$dt['template_id']] = $dt['template_id'];
        }
        if (sizeof($data) > 0) {
            try {
                $this->createProductNameStories($data, $variation_id, $images);
            } catch (\Exception $e) {
                logfile($e->getMessage());
            }
        }
    }

    // Tạo mới sản phẩm name stories

    private function createProductNameStories($data, $list_variation_id, $images)
    {
        $color_name_stories = $this->color_name_stories;
        $variations = \DB::table('woo_variations')
            ->select('store_id', 'variation_path', 'template_id')
            ->whereIn('template_id', $list_variation_id)
            ->get()->toArray();
        $variation_store = array();
        foreach ($variations as $value) {
            $variation_store[$value->template_id . '_' . $value->store_id][] = $value->variation_path;
        }
        $ar_image = array();
        foreach ($color_name_stories as $color_slug => $color_name) {
            foreach($images as $key_img => $value_image)
            {
                if (strpos(strtolower($value_image), $color_slug) !== false) {
                    $ar_image[$color_slug] = $value_image;
                    unset($images[$key_img]);
                    break;
                }
            }
        }

        foreach ($data as $key => $val) {
            $prod_data = array();
            // Tìm template
            $template_json = readFileJson($val['template_path']);
            // Chọn name
            $tmp_namestories_name = explode(trim($val['category_name']),$val['product_name']);
            $tmp_name = explode('-', $template_json['name']);
            if (sizeof($tmp_namestories_name) == 1)
            {
                $tmp_namestories_name = explode("  ",$val['product_name']);
                if (sizeof($tmp_namestories_name) == 1)
                {
                    $woo_product_name = ucwords(trim($val['product_name']).' '.$template_json['name']);
                } else {
                    $woo_product_name = ucwords(trim($tmp_namestories_name[0])).' '.ucwords($tmp_name[0] . trim($tmp_namestories_name[1]) . ' -' . $tmp_name[1]);
                }
            } else {
                $woo_product_name = ucwords(trim($val['category_name'])).' '.ucwords($tmp_name[0] . trim($tmp_namestories_name[1]) . ' -' . $tmp_name[1]);
            }
            // Kết thúc chọn name
            logfile("-- Đang tạo sản phẩm mới : " . $woo_product_name);
            $prod_data = $template_json;
            $prod_data['name'] = $woo_product_name;
            $prod_data['status'] = 'draft';
            $prod_data['categories'] = [
                ['id' => $val['woo_category_id']]
            ];
            $prod_data['description'] = html_entity_decode($val['description']);
            unset($prod_data['variations']);
            unset($prod_data['images']);
            // End tìm template

            //Kết nối với woocommerce
            $woocommerce = $this->getConnectStore($val['url'], $val['consumer_key'], $val['consumer_secret']);
            $save_product = ($woocommerce->post('products', $prod_data));
            $woo_product_id = $save_product->id;
            // Cap nhat product id vao woo_product_driver
            \DB::table('scrap_products')->where('id', $val['id'])
                ->update([
                    'woo_product_id' => $woo_product_id,
                    'woo_product_name' => $woo_product_name,
                    'woo_slug' => $save_product->permalink,
                    'status' => 1,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
            // tìm image và gán vào
            $i = 0;
            $prd_image = array();
            $key_variation = $val['template_id'].'_'.$val['store_id'];
            foreach($variation_store[$key_variation] as $variation_path)
            {
                //đọc file json cua variation con
                $variation_json = readFileJson($variation_path);
                //lấy ra permalink có chứa slug color để so sánh
                $variation_permalink = $variation_json['permalink'];
                // lặp toàn bộ image được tìm thấy và so sánh. nếu tồn tại thì gắn vào variation và xóa khỏi array image
                foreach($ar_image as $color_slug => $image)
                {
                    if (strpos($variation_permalink, strtolower($color_slug)) !== false) {
                        $link_image = $image;
                        break;
                    }
                }
                if ($i < 2)
                {
                    $prd_image[]['src'] = $link_image;
                }
                $variation_data = array(
                    'price' => $variation_json['price'],
                    'regular_price' => $variation_json['regular_price'],
                    'sale_price' => $variation_json['sale_price'],
                    'image'         => [
                        'src' => $link_image,
                    ],
                    'status' => $variation_json['status'],
                    'attributes' => $variation_json['attributes'],
                    'menu_order' => $variation_json['menu_order'],
                    'meta_data' => $variation_json['meta_data'],
                );
                $re = $woocommerce->post('products/' . $woo_product_id . '/variations', $variation_data);
                $str = ('-- Đang cập nhật variation '. $color_slug.' của '.$woo_product_id);
//                echo $str;
                $i++;
            }
            $tmp = array(
                'id' => $woo_product_id,
                'status' => 'publish',
                'images' => $prd_image,
                'date_created' => date("Y-m-d H:i:s", strtotime(" -3 days"))
            );
            $result = $woocommerce->put('products/' . $woo_product_id, $tmp);
            if ($result)
            {
                logfile('-- Đã tạo thành công sản phẩm '.$woo_product_name);
            } else {
                logfile('-- Thất bại. Khôn tạo được sản phẩm '.$woo_product_name);
            }
        }
    }

    private function createProductNameStories2($data, $list_variation_id)
    {
        $color_name_stories = $this->color_name_stories;
        $variations = \DB::table('woo_variations')
            ->select('store_id', 'variation_path', 'template_id')
            ->whereIn('template_id', $list_variation_id)
            ->get()->toArray();
        $variation_store = array();
        foreach ($variations as $value) {
            $variation_store[$value->template_id . '_' . $value->store_id][] = $value->variation_path;
        }
        foreach ($data as $key => $val) {
            $prod_data = array();
            // Tìm template
            $template_json = readFileJson($val['template_path']);
            // Chọn name
            $tmp_namestories_name = explode(trim($val['category_name']),$val['product_name']);
            $tmp_name = explode('-', $template_json['name']);
            if (sizeof($tmp_namestories_name) == 1)
            {
                $tmp_namestories_name = explode("  ",$val['product_name']);
                if (sizeof($tmp_namestories_name) == 1)
                {
                    $woo_product_name = ucwords(trim($val['product_name']).' '.$template_json['name']);
                } else {
                    $woo_product_name = ucwords(trim($tmp_namestories_name[0])).' '.ucwords($tmp_name[0] . trim($tmp_namestories_name[1]) . ' -' . $tmp_name[1]);
                }
            } else {
                $woo_product_name = ucwords(trim($val['category_name'])).' '.ucwords($tmp_name[0] . trim($tmp_namestories_name[1]) . ' -' . $tmp_name[1]);
            }
            // Kết thúc chọn name
            logfile("-- Đang tạo sản phẩm mới : " . $woo_product_name);
            $prod_data = $template_json;
            $prod_data['name'] = $woo_product_name;
            $prod_data['status'] = 'draft';
            $prod_data['categories'] = [
                ['id' => $val['woo_category_id']]
            ];
            $prod_data['description'] = html_entity_decode($val['description']);
            $prod_data['images'] = $val['images'];
            unset($prod_data['variations']);
            // End tìm template

            //Kết nối với woocommerce
            $woocommerce = $this->getConnectStore($val['url'], $val['consumer_key'], $val['consumer_secret']);
            $save_product = ($woocommerce->post('products', $prod_data));
            $ar_images_upload = $save_product->images;
            $ar_image = array();
            foreach ($color_name_stories as $color_slug => $color_name) {
                foreach($ar_images_upload as $key_img => $value_image)
                {
                    if (strpos(strtolower($value_image->src), $color_slug) !== false) {
                        $ar_image[$color_slug] = $value_image->src;
                        unset($ar_images_upload[$key_img]);
                        break;
                    }
                }
            }
            $woo_product_id = $save_product->id;
            // Cap nhat product id vao woo_product_driver
            \DB::table('scrap_products')->where('id', $val['id'])
                ->update([
                    'woo_product_id' => $woo_product_id,
                    'woo_product_name' => $woo_product_name,
                    'woo_slug' => $save_product->permalink,
                    'status' => 1,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
            // tìm image và gán vào
            $key_variation = $val['template_id'].'_'.$val['store_id'];
            foreach($variation_store[$key_variation] as $variation_path)
            {
                //đọc file json cua variation con
                $variation_json = readFileJson($variation_path);
                //lấy ra permalink có chứa slug color để so sánh
                $variation_permalink = $variation_json['permalink'];
                // lặp toàn bộ image được tìm thấy và so sánh. nếu tồn tại thì gắn vào variation và xóa khỏi array image
                foreach($ar_image as $color_slug => $image)
                {
                    if (strpos($variation_permalink, strtolower($color_slug)) !== false) {
                        $link_image = $image;
                        break;
                    }
                }

                $variation_data = array(
                    'price' => $variation_json['price'],
                    'regular_price' => $variation_json['regular_price'],
                    'sale_price' => $variation_json['sale_price'],
                    'image'         => [
                        'src' => $link_image,
                    ],
                    'status' => $variation_json['status'],
                    'attributes' => $variation_json['attributes'],
                    'menu_order' => $variation_json['menu_order'],
                    'meta_data' => $variation_json['meta_data'],
                );
                $re = $woocommerce->post('products/' . $woo_product_id . '/variations', $variation_data);
                $str = ('-- Đang cập nhật variation '. $color_slug.' của '.$woo_product_id);
//                echo $str;
            }
            $tmp = array(
                'id' => $woo_product_id,
                'status' => 'publish',
                'date_created' => date("Y-m-d H:i:s", strtotime(" -3 days"))
            );
            $result = $woocommerce->put('products/' . $woo_product_id, $tmp);
        }
    }
    /* End website namestories.com */

    /*Begin website esty*/
    private function getProductEsty($data)
    {
        $client = new \Goutte\Client();
        $db = array();
        $variation_id = array();
        foreach ($data as $key => $dt) {
            $link = $dt['link'];
            $response = $client->request('GET', $link);
            $crawler = $response;
            if ($crawler->filter('ul.list-unstyled')->count() > 0) {
                //get name
                $product_name = trim($crawler->filter('div.listing-page-title-component h1')->text());
                $data[$key]['product_name'] = trim(preg_replace('/[^a-z\d ]/i', '', $product_name));
                // get description
                $description = $crawler->filter('div.listing-page-overview-component')->text();
                $data[$key]['description'] = htmlentities($description);
                $i = 0;
                //get image to variation color
                $crawler->filter('ul.carousel-pane-list li.carousel-pane')
                    ->each(function ($node) use (&$data, &$key, &$i, &$product_name) {
                        $image = trim($node->filter('img')->attr('data-src-zoom-image'));
                        $data[$key]['images'][$i]['src'] = $image;
                        $data[$key]['images'][$i]['name'] = $product_name."_".basename($image);
                        $i++;
                    });
            }
            $variation_id[$dt['template_id']] = $dt['template_id'];
        }
        if (sizeof($data) > 0) {
            try {
                $this->createProductEsty($data, $variation_id);
            } catch (\Exception $e) {
                logfile($e->getMessage());
            }
        }
    }

    private function createProductEsty($data, $list_variation_id)
    {
        $variations = \DB::table('woo_variations')
            ->select('store_id', 'variation_path', 'template_id')
            ->whereIn('template_id', $list_variation_id)
            ->get()->toArray();
        $variation_store = array();
        foreach ($variations as $value) {
            $variation_store[$value->template_id . '_' . $value->store_id][] = $value->variation_path;
        }
        foreach ($data as $key => $val) {
            $prod_data = array();
            // Tìm template
            $template_json = readFileJson($val['template_path']);
            // Chọn name
            $woo_product_name = ucwords(trim($val['product_name'].' '.$template_json['name']));
            // Kết thúc chọn name
            logfile("-- Đang tạo sản phẩm mới : " . $woo_product_name);
            $prod_data = $template_json;
            $prod_data['name'] = $woo_product_name;
            $prod_data['status'] = 'draft';
            $prod_data['categories'] = [
                ['id' => $val['woo_category_id']]
            ];
            $prod_data['description'] = html_entity_decode($val['description']);
            $prod_data['images'] = $val['images'];
            unset($prod_data['variations']);
            // End tìm template

            //Kết nối với woocommerce
            $woocommerce = $this->getConnectStore($val['url'], $val['consumer_key'], $val['consumer_secret']);
            $save_product = ($woocommerce->post('products', $prod_data));

            $woo_product_id = $save_product->id;
            // Cap nhat product id vao woo_product_driver
            \DB::table('scrap_products')->where('id', $val['id'])
                ->update([
                    'woo_product_id' => $woo_product_id,
                    'woo_product_name' => $woo_product_name,
                    'woo_slug' => $save_product->permalink,
                    'status' => 1,
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
            // tìm image và gán vào
            $key_variation = $val['template_id'].'_'.$val['store_id'];
            foreach($variation_store[$key_variation] as $variation_path)
            {
                //đọc file json cua variation con
                $variation_json = readFileJson($variation_path);
                //lấy ra permalink có chứa slug color để so sánh
                $variation_permalink = $variation_json['permalink'];

                $variation_data = array(
                    'price' => $variation_json['price'],
                    'regular_price' => $variation_json['regular_price'],
                    'sale_price' => $variation_json['sale_price'],
                    'status' => $variation_json['status'],
                    'attributes' => $variation_json['attributes'],
                    'menu_order' => $variation_json['menu_order'],
                    'meta_data' => $variation_json['meta_data'],
                );
                $re = $woocommerce->post('products/' . $woo_product_id . '/variations', $variation_data);
                $str = ('-- Đang cập nhật variation của '.$woo_product_id);
                echo $str."\n";
            }
            $tmp = array(
                'id' => $woo_product_id,
                'status' => 'publish',
                'date_created' => date("Y-m-d H:i:s", strtotime(" -3 days"))
            );
            $result = $woocommerce->put('products/' . $woo_product_id, $tmp);
        }
    }
    /*End website esty*/
}
