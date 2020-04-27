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
//                'query_string_auth' => true,
//                'verify_ssl' => false
            ]
        );
        return $woocommerce;
    }

    protected $color_name_stories = [
        'navy-blue' => 'Navy Blue',
        'deep-purple' => 'Deep Purple',
        'light-pink' => 'Light Pink',
        'dark-pink' => 'Dark Pink',
        'lavender' => 'Lavender',
        'charcoal-gray' => 'Charcoal Gray',
        'light-gray' => 'Light Gray',
        'deep-red' => 'Deep Red',
        'orange' => 'Orange',
        'gold' => 'Gold',
        'hunter-green' => 'Hunter Green',
        'emerald-green' => 'Emerald Green',
        'teal-blue' => 'Teal Blue',
        'light-green' => 'Light Green',
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
        logfile_system('========================= [ Bắt đầu scrap products ] ==========================');
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
            logfile_system('Đã hết product để import vào website');
        }
        logfile_system('========================= [ Kết thúc scrap products ] =========================');
    }

    private function preProduct($data)
    {
        $check = $this->checkCategory();
        $check_tag = false;
        if ($check) {
            $check_tag = $this->checkTag();
        }
        if ($check_tag) {
            $str = '-- Đã cập nhật xong categories và tags vào toàn bộ product. Chuyển sang tạo mới sản phẩm.';
            logfile_system($str);
            foreach ($data as $website_id => $dt) {
                switch ($website_id) {
                    case 1:
                        $this->getProductNamestories($dt);
                        break;
                    case 2:
                    case 3:
                        $this->getProductEsty($dt);
                        break;
                    case 4:
                    case 5:
                        $this->getProductPercre($dt);
                        break;
                    case 6:
                    case 8:
                        $this->getProductMerchKing($dt, array(1, 2, 4, 6));
                        break;
                    case 7:
                        $this->getProductMerchKing($dt, array(1));
                        break;
                    case 9:
                    case 17:
                        $this->getProductMerchKing($dt, array(2,3,4,7));
                        break;
                    case 10:
                        $this->getProductEsty_LinkName($dt);
                        break;
                    case 11:
                    case 12:
                    case 13:
                    case 14:
                        $this->getProductEsty_LinkName($dt,true);
                        break;
                    case 15:
                    case 16:
                        $this->getProductCreationsLaunch_LinkName($dt,array(0,1,2,3) ,true);
                        break;
                    case 18:
                        $text_exclude = 'B6L2AF01';
                        $this->getProductMerchKing_excludeText($dt, array(1,2,3,4,7), $text_exclude);
                    case 19:
                        $text_exclude = ' - PREMIUM - BLANKET - B450';
                        $this->getProductMerchKing_excludeText($dt, array(1,2,3,4,7), $text_exclude);
                    case 20:
                        $text_exclude = 'PREMIUM BLANKET - B750';
                        $this->getProductMerchKing_excludeText($dt, array(1,2,3,4,7), $text_exclude);
                    default:
                        $str = "-- Không có website nào cần được up sản phẩm.";
                        logfile_system($str);
                }
                break;
            }
        } else {
            $str = '-- Đang cập nhật tag vào scrap_products';
            logfile_system($str);
        }
    }

    // kiểm tra xem có sản phẩm mới chưa được up lên shop hay không
    private function checkProductNew()
    {
        $result = false;
        $limit = 1;
        $products = \DB::table('scrap_products as spd')
            ->leftjoin('woo_categories as woo_cat', 'spd.woo_category_id', '=', 'woo_cat.id')
            ->leftjoin('woo_tags as woo_tag', 'spd.woo_tag_id', '=', 'woo_tag.id')
            ->leftjoin('woo_infos as woo_info', 'spd.store_id', '=', 'woo_info.id')
            ->leftjoin('woo_templates as woo_temp', function ($join) {
                $join->on('spd.template_id', '=', 'woo_temp.template_id');
                $join->on('spd.store_id', '=', 'woo_temp.store_id');
            })
            ->select(
                'spd.id', 'spd.website_id', 'spd.store_id', 'spd.website', 'spd.link', 'spd.category_name', 'spd.tag_name',
                'woo_cat.woo_category_id',
                'woo_tag.woo_tag_id',
                'woo_temp.template_path', 'woo_temp.template_id',
                'woo_info.url', 'woo_info.consumer_key', 'woo_info.consumer_secret'
            )
            ->where('spd.status', 0)
            ->orderByRaw('spd.website_id ASC', 'spd.store_id ASC')
            ->limit($limit)
            ->get()->toArray();
        if (sizeof($products) > 0) {
            $result = json_decode(json_encode($products), true);
        }
        return $result;
    }

    // kiểm tra categories để lưu vào product
    private function checkCategory()
    {
        logfile_system('--[ Check Category ] ---------------------------');
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
                ->select('id', 'store_id', 'name')
                ->get()->toArray();
            // tạo mảng mới có key là store_id và name folder để so sánh
            $compare_categories = array();
            foreach ($categories as $category) {
                $key = $category->store_id . '_' . $category->name;
                $compare_categories[$key] = $category->id;
            }
            foreach ($lst_product_category as $val) {
                $val->category_name = ($val->category_name);
                $key_compare = $val->store_id . '_' . $val->category_name;
                //nếu đã tồn tại
                if (array_key_exists($key_compare, $compare_categories)) {
                    $scrap_product_update[$compare_categories[$key_compare]][] = $val->scrap_product_id;
                } else { // nếu chưa tồn tại. lưu vào 1 mảng khác để truy vấn.
                    if (!in_array($val->category_name, $tmp)) {
                        $tmp[] = $val->category_name;
                    }
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
                    logfile_system('-- Tạo mới thông tin woo_categories : ' . sizeof($woo_categories_data) . ' news');
                    \DB::table('woo_categories')->insert($woo_categories_data);
                }
            }

            // Nếu tồn tại thông tin để update vào sản phẩm scrap_products
            if (sizeof($scrap_product_update) > 0) {
                logfile_system('-- Cập nhật thông tin category vào scrap_products : ' . sizeof($scrap_product_update) . ' update.');
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
            logfile_system('-- Đã chuẩn bị đủ category. Chuyển sang cập nhật category vào từng product.');
        }
        return $result;
    }

    // kiểm tra tag để lưu vào product
    private function checkTag()
    {
        logfile_system('--[ Check Tag ] ---------------------------');
        $lst_product_tag = \DB::table('scrap_products as spd')
            ->join('woo_infos as woo_info', 'spd.store_id', '=', 'woo_info.id')
            ->select(
                'spd.id as scrap_product_id', 'spd.tag_name', 'spd.store_id',
                'woo_info.url', 'woo_info.consumer_key', 'woo_info.consumer_secret'
            )
            ->where([
                ['woo_tag_id', '=', NULL],
                ['tag_name', '<>', NULL]
            ])
            ->limit(30)
            ->get()->toArray();
        if (sizeof($lst_product_tag) > 0) {
            $tag_store_lst = array();
            $tmp = array();
            $scrap_product_update = array();
            // cập nhật tag_id vào woo_product_drivers
            $tags = \DB::table('woo_tags')
                ->select('id', 'store_id', 'slug')
                ->get()->toArray();
            // tạo mảng mới có key là store_id và name folder để so sánh
            $compare_tag = array();
            foreach ($tags as $tag) {
                $key = $tag->store_id . '_' . $tag->slug;
                $compare_tag[$key] = $tag->id;
            }
            foreach ($lst_product_tag as $val) {
                $val->tag_name = strtolower($val->tag_name);
                $key_compare = $val->store_id . '_' . $val->tag_name;
                //nếu đã tồn tại
                if (array_key_exists($key_compare, $compare_tag)) {
                    $scrap_product_update[$compare_tag[$key_compare]][] = $val->scrap_product_id;
                } else { // nếu chưa tồn tại. lưu vào 1 mảng khác để truy vấn.
                    if (!in_array($val->tag_name, $tmp)) {
                        $tmp[] = $val->tag_name;
                    }
                    $tag_store_lst[$val->store_id] = [
                        'url' => $val->url,
                        'consumer_key' => $val->consumer_key,
                        'consumer_secret' => $val->consumer_secret,
                        'tags' => $tmp
                    ];
                }
            }
            //nếu tồn tại sản phẩm chưa có tag
            if (sizeof($tag_store_lst) > 0) {
                $woo_tags_data = array();
                foreach ($tag_store_lst as $store_id => $info) {
                    $woocommerce = $this->getConnectStore($info['url'], $info['consumer_key'], $info['consumer_secret']);
                    foreach ($info['tags'] as $tag_name) {
                        $data = [
                            'slug' => $tag_name,
                        ];
                        // kết nối tới woocommerce store để lấy thông tin
                        $result = ($woocommerce->get('products/tags', $data));
                        //nếu không thấy thông tin thì tạo mới
                        if (sizeof($result) == 0) {
                            $data = [
                                'name' => $tag_name
                            ];
                            $i = ($woocommerce->post('products/tags', $data));
                            $woo_tags_data[] = [
                                'woo_tag_id' => $i->id,
                                'name' => $i->name,
                                'slug' => $i->slug,
                                'store_id' => $store_id,
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                        } else {
                            $woo_tags_data[] = [
                                'woo_tag_id' => $result[0]->id,
                                'name' => $result[0]->name,
                                'slug' => $result[0]->slug,
                                'store_id' => $store_id,
                                'created_at' => date("Y-m-d H:i:s"),
                                'updated_at' => date("Y-m-d H:i:s")
                            ];
                        }
                    }
                }
                //them toan bo thong tin woo_tags mới get được về database
                if (sizeof($woo_tags_data) > 0) {
                    logfile_system('-- Tạo mới thông tin woo_tags : ' . sizeof($woo_tags_data) . ' news');
                    \DB::table('woo_tags')->insert($woo_tags_data);
                }
            }

            // Nếu tồn tại thông tin để update vào sản phẩm scrap_products
            if (sizeof($scrap_product_update) > 0) {
                logfile_system('-- Cập nhật thông tin tag vào scrap_products : ' . sizeof($scrap_product_update) . ' update.');
                foreach ($scrap_product_update as $woo_tag_id => $list_id) {
                    $data = [
                        'woo_tag_id' => $woo_tag_id
                    ];
                    \DB::table('scrap_products')->whereIn('id', $list_id)->update($data);
                }
            }
            $result = false;
        } else {
            $result = true;
            logfile_system('-- Đã chuẩn bị đủ tag. Chuyển sang cập nhật tag vào từng product.');
        }
        return $result;
    }



    /* Begin website namestories.com */
    // chuẩn bị dữ liệu
    private function getProductNamestories($data)
    {
        $client = new \Goutte\Client();
        $db = array();
        $variation_id = array();
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
                    ->each(function ($node) use (&$data, &$key) {
                        $img = trim($node->filter('a')->attr('href'));
                        $data[$key]['images'][] = 'https:' . $img;
                    });
            }
            $variation_id[$dt['template_id']] = $dt['template_id'];
        }
        if (sizeof($data) > 0) {
            try {
                $this->createProductNameStories($data, $variation_id);
            } catch (\Exception $e) {
                logfile_system($e->getMessage());
            }
        }
    }

    // Tạo mới sản phẩm name stories
    private function createProductNameStories($data, $list_variation_id)
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
            $tmp_namestories_name = explode(trim($val['category_name']), $val['product_name']);
            $tmp_name = explode('-', $template_json['name']);
            if (sizeof($tmp_namestories_name) == 1) {
                $tmp_namestories_name = explode("  ", $val['product_name']);
                if (sizeof($tmp_namestories_name) == 1) {
                    $woo_product_name = ucwords(trim($val['product_name']) . ' ' . $template_json['name']);
                } else {
                    $woo_product_name = ucwords(trim($tmp_namestories_name[0])) . ' ' . ucwords($tmp_name[0] . trim($tmp_namestories_name[1]) . ' -' . $tmp_name[1]);
                }
            } else {
                $woo_product_name = ucwords(trim($val['category_name'])) . ' ' . ucwords($tmp_name[0] . trim($tmp_namestories_name[1]) . ' -' . $tmp_name[1]);
            }

            //chon image
            $images = $val['images'];
            $ar_image = array();
            foreach ($color_name_stories as $color_slug => $color_name) {
                foreach ($images as $key_img => $value_image) {
                    if (strpos(strtolower($value_image), $color_slug) !== false) {
                        $ar_image[$color_slug] = $value_image;
                        unset($images[$key_img]);
                        break;
                    }
                }
            }
            // Kết thúc chọn name
            logfile_system("-- Đang tạo sản phẩm mới : " . $woo_product_name);
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
            $prd_image = array();
            $key_variation = $val['template_id'] . '_' . $val['store_id'];
            foreach ($variation_store[$key_variation] as $variation_path) {
                //đọc file json cua variation con
                $variation_json = readFileJson($variation_path);
                //lấy ra permalink có chứa slug color để so sánh
                $variation_permalink = $variation_json['permalink'];
                // lặp toàn bộ image được tìm thấy và so sánh. nếu tồn tại thì gắn vào variation và xóa khỏi array image
                foreach ($ar_image as $color_slug => $image) {
                    if (strpos($variation_permalink, strtolower($color_slug)) !== false) {
                        $link_image = $image;
                        break;
                    }
                }
                $prd_image[]['src'] = $link_image;
                $variation_data = array(
                    'price' => $variation_json['price'],
                    'regular_price' => $variation_json['regular_price'],
                    'sale_price' => $variation_json['sale_price'],
                    'image' => [
                        'src' => $link_image,
                    ],
                    'status' => $variation_json['status'],
                    'attributes' => $variation_json['attributes'],
                    'menu_order' => $variation_json['menu_order'],
                    'meta_data' => $variation_json['meta_data'],
                );
                $re = $woocommerce->post('products/' . $woo_product_id . '/variations', $variation_data);
                $str = ('-- Đang cập nhật variation ' . $color_slug . ' của ' . $woo_product_id);
            }
            $key_rand = array_rand($prd_image, 2);
            $i = 0;
            $update_img = array();
            foreach ($prd_image as $v) {
                if (sizeof($update_img) >= 2) break;
                if (in_array($i, $key_rand)) {
                    $update_img[] = $v;
                }
                $i++;
            }
            $tmp = array(
                'id' => $woo_product_id,
                'status' => 'publish',
                'images' => $update_img,
                'date_created' => date("Y-m-d H:i:s", strtotime(" -3 days"))
            );
            $result = $woocommerce->put('products/' . $woo_product_id, $tmp);
            if ($result) {
                logfile_system('-- Đã tạo thành công sản phẩm ' . $woo_product_name);
            } else {
                logfile_system('-- Thất bại. Khôn tạo được sản phẩm ' . $woo_product_name);
            }
        }
    }

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
                        $data[$key]['images'][$i]['name'] = $product_name . "_" . basename($image);
                        $i++;
                    });
            }
            $variation_id[$dt['template_id']] = $dt['template_id'];
        }
        if (sizeof($data) > 0) {
            try {
                $this->createProductEsty($data, $variation_id);
            } catch (\Exception $e) {
                logfile_system($e->getMessage());
            }
        }
    }

    /*fixed : true : cố định giá và description theo template*/
    private function getProductEsty_LinkName($data, $fixed = false)
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
                $product_name_1 = trim($crawler->filter('div.listing-page-title-component h1')->text());
                $data[$key]['des_more'] = $product_name_1;
                $tmp = (explode("/",$link));
                $tmp_name = explode("?",$tmp[sizeof($tmp) - 1]);
                $product_name = ucwords(str_replace("-"," ",$tmp_name[0]));
                $data[$key]['product_name'] = trim(preg_replace('/[^a-z\d ]/i', '', $product_name));
                // get description
//                $description = $crawler->filter('div.listing-page-overview-component')->html();
                $description = $crawler->filter('div#description-text-content-toggle')->html();
                $breaks = array("<br />","<br>","<br/>");
                $description = str_ireplace($breaks, "\r\n", $description);
                $exclude_des = explode('-----',$description);
                if (sizeof($exclude_des) > 1)
                {
                    $description = $exclude_des[1];
                }
                $description = trim($description);
                $data[$key]['description'] = $product_name_1."\n\n".($description);

                $price = trim($crawler->filter('span.override-listing-price')->text());
                $price = preg_replace("/[^0-9.,]/", '', $price);
                $sale_price = round($price*70/100, 2);
                $sale_unit = 30;
                $regular_price = round($sale_price/(1 - $sale_unit/100), 2);
                $data[$key]['sale_price'] = $sale_price;
                $data[$key]['regular_price'] = $regular_price;
                $i = 0;
                //get image to variation color
                $crawler->filter('ul.carousel-pane-list li.carousel-pane')
                    ->each(function ($node) use (&$data, &$key, &$i, &$product_name) {
                        $image = trim($node->filter('img')->attr('data-src-zoom-image'));
                        $data[$key]['images'][$i]['src'] = $image;
                        $data[$key]['images'][$i]['name'] = $product_name . "_" . basename($image);
                        $i++;
                    });
            }
            $variation_id[$dt['template_id']] = $dt['template_id'];
        }
        if (sizeof($data) > 0) {
            try {
                $this->createProductEsty_LinkName($data, $variation_id, $fixed);
            } catch (\Exception $e) {
                logfile_system($e->getMessage());
            }
        }
    }

    /*fixed : true : cố định giá và description theo template*/
    private function getProductCreationsLaunch_LinkName($data, $array_image, $fixed = false)
    {
        $client = new \Goutte\Client();
        $db = array();
        $variation_id = array();
        foreach ($data as $key => $dt) {
            $link = $dt['link'];
            $tmp_http = parse_url($link);
            $http = $tmp_http['scheme'];
            $response = $client->request('GET', $link);
            $crawler = $response;
            //kiem tra xem co anh hay khong
            if ($crawler->filter('h1.product-single__title')->count() > 0) {
                //get name
                $product_name = trim($crawler->filter('h1.product-single__title')->text());
                $data[$key]['product_name'] = trim(preg_replace('/[^a-z\d ]/i', '', $product_name));
                // get description
                $data[$key]['description'] = '';
                $i = 0;
                //get image to variation color
                $crawler->filter('ul.product-single__thumbnails li.product-single__thumbnails-item')
                    ->each(function ($node) use (&$data, &$key, &$i, &$product_name, &$http, &$array_image) {
                        if (in_array($i, $array_image)) {
                            $image = $http.":".$node->filter('a')->attr('href');
                            $data[$key]['images'][$i]['src'] = $image;
                            $data[$key]['images'][$i]['name'] = $product_name . "_" . basename($image);
                        }
                        $i++;
                    });
            }
            $variation_id[$dt['template_id']] = $dt['template_id'];
        }
        if (sizeof($data) > 0) {
            try {
                $this->createProduct($data, $variation_id);
            } catch (\Exception $e) {
                logfile_system($e->getMessage());
            }
        }
    }

    private function createProductEsty_LinkName($data, $list_variation_id, $fixed)
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
            $woo_product_name = ucwords(trim($val['product_name'] . ' ' . $template_json['name']));
            // Kết thúc chọn name
            logfile_system("-- Đang tạo sản phẩm mới : " . $woo_product_name);
            $prod_data = $this->preProductData($template_json);
            $prod_data['name'] = $woo_product_name;
            $prod_data['status'] = 'draft';
            $prod_data['categories'] = [
                ['id' => $val['woo_category_id']]
            ];
            if (!$fixed)
            {
                $prod_data['price'] = (string) $val['sale_price'];
                $prod_data['regular_price'] = (string) $val['regular_price'];
                $prod_data['sale_price'] = (string) $val['sale_price'];
                $prod_data['description'] = $val['description'];
            } else {
                if (array_key_exists('des_more',$val)){
                    $str_more = "<span class='des_more'>".$val['des_more']."</span>";
                    $prod_data['description'] = ($str_more."\n".$prod_data['description']);
                }
            }
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
            $key_variation = $val['template_id'] . '_' . $val['store_id'];
            if (sizeof($variation_store) > 0 && array_key_exists($key_variation, $variation_store))
            {
                foreach ($variation_store[$key_variation] as $variation_path) {
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
                    $str = ('-- Đang cập nhật variation của ' . $woo_product_id);
                }
            }
            $tmp = array(
                'id' => $woo_product_id,
                'status' => 'publish',
                'images' => $val['images'],
                'date_created' => date("Y-m-d H:i:s", strtotime(" -1 days"))
            );
            $result = $woocommerce->put('products/' . $woo_product_id, $tmp);
            if ($result)
            {
                logfile_system('--- Tạo thành công sản phẩm '.$woo_product_name);
            } else {
                logfile_system('--- Tạo thất bại sản phẩm '.$woo_product_name);
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
            $woo_product_name = ucwords(trim($val['product_name'] . ' ' . $template_json['name']));
            // Kết thúc chọn name
            logfile_system("-- Đang tạo sản phẩm mới : " . $woo_product_name);
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
            $key_variation = $val['template_id'] . '_' . $val['store_id'];
            foreach ($variation_store[$key_variation] as $variation_path) {
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
                $str = ('-- Đang cập nhật variation của ' . $woo_product_id);
            }
            $tmp = array(
                'id' => $woo_product_id,
                'status' => 'publish',
                'date_created' => date("Y-m-d H:i:s", strtotime(" -1 days"))
            );
            $result = $woocommerce->put('products/' . $woo_product_id, $tmp);
        }
    }
    /*End website esty*/

    /*Begin percre*/
    private function getProductPercre($data)
    {
        $client = new \Goutte\Client();
        $db = array();
        $variation_id = array();
        foreach ($data as $key => $dt) {
            $link = $dt['link'];
            $response = $client->request('GET', $link);
            $crawler = $response;
            //kiem tra xem co anh hay khong
            if ($crawler->filter('h1.product_title')->count() > 0) {
                //get name
                $product_name = trim($crawler->filter('h1.product_title')->text());
                $data[$key]['product_name'] = trim(preg_replace('/[^a-z\d ]/i', '', $product_name));
                // get description
                $description = $crawler->filter('div#tab-description')->text();
                $description = trim(str_replace('Description', '', $description));
                $data[$key]['description'] = htmlentities($description);
                $i = 0;
                //get image to variation color
                $crawler->filter('div.woocommerce-product-gallery .woocommerce-product-gallery__image')
                    ->each(function ($node) use (&$data, &$key, &$i, &$product_name) {
                        $image = $node->filter('a')->attr('href');
                        $data[$key]['images'][$i]['src'] = $image;
                        $data[$key]['images'][$i]['name'] = $product_name . "_" . basename($image);
                        $i++;
                    });
            }
            $variation_id[$dt['template_id']] = $dt['template_id'];
        }
        if (sizeof($data) > 0) {
            try {
                $this->createProduct($data, $variation_id);
            } catch (\Exception $e) {
                logfile_system($e->getMessage());
            }
        }
    }

    private function createProduct($data, $list_variation_id)
    {
        try {
            $variations = \DB::table('woo_variations')
                ->select('store_id', 'variation_path', 'template_id')
                ->whereIn('template_id', $list_variation_id)
                ->get()->toArray();
            $variation_store = array();
            if( sizeof($variations) > 0)
            {
                foreach ($variations as $value) {
                    $variation_store[$value->template_id . '_' . $value->store_id][] = $value->variation_path;
                }
            }
            $db_image = array();
            foreach ($data as $key => $val) {
                $prod_data = array();
                // Tìm template
                $template_json = readFileJson($val['template_path']);
                // Chọn name
                $woo_product_name = ucwords(trim($val['product_name'] . ' ' . $template_json['name']));

                //chon image. Luu vao data base
                $images = $val['images'];
                $db_image[$val['id'].'_'.$val['store_id']] = $images;

                // Kết thúc chọn name
                logfile_system("-- Đang tạo sản phẩm mới : " . $woo_product_name);
                $prod_data = $this->preProductData($template_json);
//            $prod_data = $template_json;
                $prod_data['name'] = $woo_product_name;
                $prod_data['status'] = 'draft';
                $prod_data['categories'] = [
                    ['id' => $val['woo_category_id']]
                ];
                //them tag vao san pham
                if ($val['woo_tag_id'] != '') {
                    $prod_data['tags'][] = [
                        'id' => $val['woo_tag_id'],
                        'name' => $val['tag_name'],
                        'slug' => $val['tag_name'],
                    ];
                }
//            $prod_data['description'] = html_entity_decode($val['description']);
//            unset($prod_data['variations']);
//            unset($prod_data['images']);
                // End tìm template

                //Kết nối với woocommerce
                $woocommerce = $this->getConnectStore($val['url'], $val['consumer_key'], $val['consumer_secret']);
                $save_product = ($woocommerce->post('products', $prod_data));
                $woo_product_id = $save_product->id;
                $link_product = $save_product->permalink;
                $key_variation = $val['template_id'] . '_' . $val['store_id'];
                if (sizeof($variation_store) > 0 && array_key_exists($key_variation, $variation_store))
                {
                    foreach ($variation_store[$key_variation] as $variation_path) {
                        //đọc file json cua variation con
                        $variation_json = readFileJson($variation_path);
                        $variation_data = array(
                            'price' => $variation_json['price'],
                            'regular_price' => $variation_json['regular_price'],
                            'sale_price' => $variation_json['sale_price'],
                            'status' => $variation_json['status'],
                            'attributes' => $variation_json['attributes'],
                            'menu_order' => $variation_json['menu_order'],
//                    'meta_data' => $variation_json['meta_data'],
                        );
                        $re = $woocommerce->post('products/' . $woo_product_id . '/variations', $variation_data);
                    }
                }
                $update_img = $images;
                $tmp = array(
                    'id' => $woo_product_id,
                    'status' => 'publish',
                    'images' => $update_img,
                    'date_created' => date("Y-m-d H:i:s", strtotime(" -3 days"))
                );
                $result = $woocommerce->put('products/' . $woo_product_id, $tmp);
                if ($result) {
                    $link_product = $result->permalink;
                    logfile_system('-- Đã tạo thành công sản phẩm ' . $woo_product_name);
                } else {
                    logfile_system('-- Thất bại. Không tạo được sản phẩm ' . $woo_product_name);
                }
                // Cap nhat product id vao woo_product_driver
                \DB::table('scrap_products')->where('id', $val['id'])
                    ->update([
                        'woo_product_id' => $woo_product_id,
                        'woo_product_name' => $woo_product_name,
                        'woo_slug' => $link_product,
                        'status' => 1,
                        'updated_at' => date("Y-m-d H:i:s")
                    ]);
            }
            /*// gui image luu vao database
            $this->saveImagePath($db_image);*/
        } catch (\Exception $e) {
            logfile_system('--- Xảy ra lỗi ngoài ý muốn. ' . $e->getMessage());
        }
    }

    /*End percre*/

    private function preProductData($json)
    {
        $data = [
            'name' => $json['name'],
            'type' => $json['type'],
            'description' => html_entity_decode($json['description']),
            'price' => $json['price'],
            'regular_price' => $json['regular_price'],
            'sale_price' => $json['sale_price'],
            'on_sale' => $json['on_sale'],
            'stock_status' => $json['stock_status'],
            'reviews_allowed' => $json['reviews_allowed'],
            'tags' => $json['tags'],
            'attributes' => $json['attributes'],
        ];
        return $data;
    }

    private function saveImagePath($data)
    {
        if(sizeof($data) > 0)
        {
            $db = array();
            foreach($data as $key => $value)
            {
                $tmp = explode('_',$key);
                $scrap_product_id = $tmp[0];
                $store_id = $tmp[1];
                foreach ($value as $img)
                {
                    $db[] = [
                        'path' => '',
                        'url' => $img['src'],
                        'woo_product_driver_id' => 0,
                        'woo_scrap_product_id' => $scrap_product_id,
                        'store_id' => $store_id,
                        'status' => 0,
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ];
                }
            }
            if (sizeof($db) > 0)
            {
                \DB::table('woo_image_uploads')->insert($db);
            }
        }
    }

    /*Begin Merch King*/
    private function getProductMerchKing($data, $array_image)
    {
        $client = new \Goutte\Client();
        $db = array();
        $variation_id = array();
        foreach ($data as $key => $dt) {
            $link = $dt['link'];
            $tmp_http = parse_url($link);
            $http = $tmp_http['scheme'];
            $response = $client->request('GET', $link);
            $crawler = $response;
            //kiem tra xem co anh hay khong
            if ($crawler->filter('h3.selected-campaign-mockup-title')->count() > 0) {
                //get name
                $product_name = ucwords(strtolower(trim($crawler->filter('h3.selected-campaign-mockup-title')->text())));
                $data[$key]['product_name'] = trim(preg_replace('/[^a-z\d ]/i', '', $product_name));
                // get description
                $description = $crawler->filter('div.campaign-description .sizing-specs-desk')->text();
                $description = trim(str_replace('Sizing Specs', '', $description));
                $description = trim(str_replace('Size Chart', '', $description));
                $data[$key]['description'] = htmlentities($description);
                $i = 0;
                //get image to variation color
                $crawler->filter('div.thumb-outter .thumb-box')
                    ->each(function ($node) use (&$data, &$key, &$i, &$product_name, &$array_image, &$http) {
                        if (!in_array($i, $array_image)) {
                            $tmp_img = $node->filter('img.shoe-preview')->attr('src');
                            $tmp = explode('&width=',$tmp_img);
                            if (sizeof($tmp) > 1)
                            {
                                $size_img = explode('&height=',$tmp[1]);
                                $width = ((int) $size_img[0])*7;
                                $height = ((int) $size_img[1])*7;
//                                $image = $http.':' . explode('&width=', $tmp_img)[0] . '&width='.$width.'&height='.$height;
                                $image = $http.':' . explode('&width=', $tmp_img)[0] . '&width=600&height=600';
//                                $image = 'http:' . explode('&width=', $tmp_img)[0];
                            } else {
                                $image = $tmp_img;
                            }
                            $data[$key]['images'][$i]['src'] = $image;
                            $data[$key]['images'][$i]['name'] = $product_name . "_" . basename($image);
                        }
                        $i++;
                    });
            }
            $variation_id[$dt['template_id']] = $dt['template_id'];
        }
        if (sizeof($data) > 0) {
            try {
                $this->createProduct($data, $variation_id);
            } catch (\Exception $e) {
                logfile_system($e->getMessage());
            }
        }
    }

    private function getProductMerchKing_excludeText($data, $array_image, $text_exclude)
    {
        $client = new \Goutte\Client();
        $db = array();
        $variation_id = array();
        $text_exclude = ucwords($text_exclude);
        $delete_scrap_id = array();
        foreach ($data as $key => $dt) {
            $link = $dt['link'];
            $tmp_http = parse_url($link);
            $http = $tmp_http['scheme'];
            $response = $client->request('GET', $link);
            $crawler = $response;
            try {
                $try = false;
                $cancel = $crawler->filter('pre')->text();
                if (strpos(strtolower($cancel), 'cannot get') !== false) {
                    $try = false;
                }
            } catch (\Exception $exception) {
                $try = true;
            }
            if ($try)
            {
                //kiem tra xem co anh hay khong
                if ($crawler->filter('h3.selected-campaign-mockup-title')->count() > 0) {
                    //get name
                    $name = $crawler->filter('h3.selected-campaign-mockup-title')->text();
                    $name = str_replace(strtolower($text_exclude),'', strtolower($name));
                    $product_name = ucwords(strtolower(trim($name)));
                    $data[$key]['product_name'] = trim(preg_replace('/[^a-z\d ]/i', '', $product_name));
                    // get description
                    $description = $crawler->filter('div.campaign-description .sizing-specs-desk')->text();
                    $description = trim(str_replace('Sizing Specs', '', $description));
                    $description = trim(str_replace('Size Chart', '', $description));
                    $data[$key]['description'] = htmlentities($description);
                    $i = 0;
                    //get image to variation color
                    $crawler->filter('div.thumb-outter .thumb-box')
                        ->each(function ($node) use (&$data, &$key, &$i, &$product_name, &$array_image, &$http) {
                            if (!in_array($i, $array_image)) {
                                $tmp_img = $node->filter('img.shoe-preview')->attr('src');
                                $tmp = explode('&width=',$tmp_img);
                                if (sizeof($tmp) > 1)
                                {
                                    $size_img = explode('&height=',$tmp[1]);
                                    $width = ((int) $size_img[0])*7;
                                    $height = ((int) $size_img[1])*7;
//                                $image = $http.':' . explode('&width=', $tmp_img)[0] . '&width='.$width.'&height='.$height;
                                    $image = $http.':' . explode('&width=', $tmp_img)[0] . '&width=600&height=600';
//                                $image = 'http:' . explode('&width=', $tmp_img)[0];
                                } else {
                                    $image = $tmp_img;
                                }
                                $data[$key]['images'][$i]['src'] = $image;
                                $data[$key]['images'][$i]['name'] = $product_name . "_" . basename($image);
                            }
                            $i++;
                        });
                }
                $variation_id[$dt['template_id']] = $dt['template_id'];
            } else {
                unset($data[$key]);
                $delete_scrap_id[] = $dt['id'];
                logfile_system('-- Xóa link : '.$dt['link']);
            }
        }
        if (sizeof($delete_scrap_id) > 0)
        {
            $result_delete = \DB::table('scrap_products')->whereIn('id',$delete_scrap_id)->delete();
            if ($result_delete)
            {
                logfile_system('-- Xóa thành công '.sizeof($delete_scrap_id).' scrap id');
            } else {
                logfile_system('-- Không thể xóa scrap id: '.implode(",",$delete_scrap_id));
            }
        }
        if (sizeof($data) > 0) {
            try {
                $this->createProduct($data, $variation_id);
            } catch (\Exception $e) {
                logfile_system($e->getMessage());
            }
        }
    }
    /*End Merch King*/
}
