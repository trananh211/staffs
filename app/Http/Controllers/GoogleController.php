<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Components\GoogleClient;
use Google_Service_Drive;
use DB;

class GoogleController extends Controller
{
    protected $client;

    public function __construct(GoogleClient $client)
    {
        $this->client = $client->getClient();
    }

    /*GOOGLE API*/
    private function getImageID($image)
    {
        $driveService = new Google_Service_Drive($this->client);
        try {
            $fileMetadata = new \Google_Service_Drive_DriveFile([
                'name' => time() . '.' . $image->getClientOriginalExtension(),
            ]);
            $file = $driveService->files->create($fileMetadata, [
                'data' => file_get_contents($image->getRealPath()),
                'uploadType' => 'multipart',
                'fields' => 'id',
            ]);
            return $file->id;
        } catch (\Exception $e) {
            //
        }
    }

    private function getFolderID($path)
    {
        echo "<pre>";
        $exists = \DB::table('gg_folders')->select('google_id')->where('path',$path)->first();
        print_r($exists);
    }
    /*End GOOGLE API*/

    /*GOOGLE API*/
    public function test()
    {
        $path = public_path(env('DIR_DONE'));
        $this->getFolderID($path);
    }
    /*END GOOGLE API*/
}
