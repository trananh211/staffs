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
            $this->scanCarrier();
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
}
