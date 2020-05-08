<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;


class ScrapePaypalCarriers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scan:paypal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan Carriers Paypal Provider';

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
        $check = $this->scanCarrier17Track();
        if ($check)
        {
            $check1 = $this->updateTrackingNotChooseCarrier();
            if ($check1)
            {
                $this->scanCarrier();
            }
        }
    }

    private function scanCarrier()
    {
        logfile_system('---------------------- [PAYPAL CARRIER] ------------------------');
        $url = env('PAYPAL_CARRIER');
        $client = new \Goutte\Client();
        $response = $client->request('GET', env('PAYPAL_CARRIER'));

//        echo $response->getStatusCode(); # 200
//        echo $response->getHeaderLine('content-type'); # 'application/json; charset=utf8'
//        echo $response->getBody(); # '{"id": 1420053, "name": "guzzle", ...}'
        $check = \DB::table('paypal_carriers')->pluck('enumerated_value')->toArray();

        $crawler = $response;
        $tr = ($crawler->filter('table.table-condensed tbody tr')->count() > 0) ?
            $crawler->filter('table.table-condensed tbody tr')->count() : 0;

        $data = array();
        if ($tr > 0) {
            $crawler->filter('table.table-condensed tbody tr')->each(function ($node) use (&$data, &$check) {
                $tmp = explode("\n", trim($node->text()));
                $carrie_name = trim($tmp[0]);
                $carrie_value = trim($tmp[1]);
                $carrie_country_code = trim($tmp[2]);
                if (!in_array($carrie_value, $check)) {
                    $data[$carrie_value] = [
                        'name' => $carrie_name,
                        'enumerated_value' => $carrie_value,
                        'country_code' => $carrie_country_code,
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ];
                    logfile_system('-- Phát hiện ra carriers mới: ' . $carrie_name . ' : ' . $carrie_value . ' : ' . $carrie_country_code);
                }
            });
            if (sizeof($data) > 0) {
                $result = \DB::table('paypal_carriers')->insert($data);
                logfile_system('-- [Paypal] [Scan Carries] Success. Quét được ' . sizeof($data) . ' carriers mới ở Paypal');
            } else {
                logfile_system('-- [Paypal] [Scan Carries] Không tìm được carriers nào mới từ Paypal');
            }
        } else {
            logfile_system('-- [Paypal] [Scan Carries] Error. Xảy ra lỗi không thể quét được.');
        }
    }

    private function scanCarrier17Track()
    {
        $return = false;
        $track_carriers = \DB::table('17track_carriers')->pluck('name')->toArray();
        $tmp = \DB::table('trackings')->select('shipping_method')->distinct()->get()->toArray();
        if (sizeof($tmp) > 0)
        {
            $insert_carriers = array();
            foreach ($tmp as $item)
            {
                if ($item->shipping_method != '' && !in_array($item->shipping_method, $track_carriers))
                {
                    $insert_carriers[] = [
                        'name' => $item->shipping_method,
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ];
                }
            }
            if(sizeof($insert_carriers) > 0)
            {
                $result = \DB::table('17track_carriers')->insert($insert_carriers);
                if ($result)
                {
                    logfile_system('-- Thêm mới '.sizeof($insert_carriers).' carriers từ 17 track thành công');
                } else {
                    logfile_system('-- [Error] Thêm mới '.sizeof($insert_carriers).' carriers từ 17 track thất bại');
                }
            } else {
                logfile_system('-- Đã hết Carriers mới từ 17 track. Chuyển sang quét carrires từ paypal');
                $return = true;
            }
        } else {
            logfile_system('-- Không tồn tại Carrires nào từ 17 track. Chuyển sang quét carriers từ paypal');
            $return = true;
        }
        return $return;
    }

    private function updateTrackingNotChooseCarrier()
    {
        $return = false;
        $lists = \DB::table('trackings')
            ->leftjoin('17track_carriers as t17', 't17.name', '=', 'trackings.shipping_method')
            ->select('trackings.id as tracking_id','trackings.shipping_method','t17.paypal_carrier_id')
            ->where('payment_status',env('PAYPAL_CARRIER_NOT_CHOOSE'))
            ->get()->toArray();
        if (sizeof($lists) > 0)
        {
            $update_trackings = array();
            $tracking_not_choose = array();
            foreach ($lists as $item)
            {
                if($item->paypal_carrier_id != 0)
                {
                    $update_trackings[] = $item->tracking_id;
                } else {
                    $tracking_not_choose[$item->shipping_method] = $item->shipping_method;
                }
            }
            if (sizeof($update_trackings) > 0) {
                $result = \DB::table('trackings')->whereIn('id',$update_trackings)->update([
                    'payment_status' => env('PAYPAL_STATUS_NEW'),
                    'payment_up_tracking' => 1, // paypal update new status
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                if ($result)
                {
                    logfile_system('-- Cập nhật thành công các nhà cung cấp chưa chọn vào danh sách mới');
                } else {
                    logfile_system('-- Cập nhật thất bại các nhà cung cấp chưa chọn vào danh sách mới');
                }
            }
            if (sizeof($tracking_not_choose) > 0)
            {
                logfile_system('-- Tồn tại '.sizeof($tracking_not_choose).' carrier chưa chọn nhà cung cấp là : '.implode(',', $tracking_not_choose));
            }
        } else {
            $return = true;
            logfile_system('-- Đã cập nhật đủ tracking chưa chọn nhà cung cấp lên paypal.');
        }
        return $return;
    }
}
