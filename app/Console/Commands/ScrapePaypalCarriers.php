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
        logfile('---------------------- [PAYPAL CARRIER] ------------------------');
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
                    logfile('-- Phát hiện ra carriers mới: ' . $carrie_name . ' : ' . $carrie_value . ' : ' . $carrie_country_code);
                }
            });
            if (sizeof($data) > 0) {
                $result = \DB::table('paypal_carriers')->insert($data);
                logfile('-- [Paypal] [Scan Carries] Success. Quét được ' . sizeof($data) . ' carriers mới ở Paypal');
            } else {
                logfile('-- [Paypal] [Scan Carries] Không tìm được carriers nào mới từ Paypal');
            }
        } else {
            logfile('-- [Paypal] [Scan Carries] Error. Xảy ra lỗi không thể quét được.');
        }
    }
}
