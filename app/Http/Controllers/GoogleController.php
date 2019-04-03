<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Components\GoogleClient;
use Google_Service_Drive;
use DB;
use \Cache;
use File;

class GoogleController extends Controller
{
    /*GOOGLE API*/
    public function test()
    {
//        echo "<pre>";
//        $this->getFolderID($path);
//        $this->createDir('Public');
//        $this->deleteDir('Public');
//        $this->renameDir('hihi','Hehe');
        echo $this->upFile(public_path(env('DIR_DONE').'S247-USA-3156-PID-19.jpg'),'1is5OXHePxYfjym8b0ackAqO0db4ItYpm');
    }

    public function createDir($name, $path = null)
    {
        $name = trim($name);
        $return = false;
        $recursive = false; // Get subdirectories also?
        if (Storage::cloud()->makeDirectory($path.'/'.$name))
        {
            $dir = collect(Storage::cloud()->listContents($path, $recursive))
                ->where('type', '=', 'dir')
                ->where('filename', '=', $name)
                ->sortBy('timestamp')
                ->last();
            $return = $dir['path'];
        }
        return $return;
    }

    public function deleteDir($name, $path = null)
    {
        $return = false;
        $name = trim($name);
        $recursive = false; // Get subdirectories also?
        $check_before = collect(Storage::cloud()->listContents($path, $recursive))
            ->where('type', '=', 'dir')
            ->where('filename', '=', $name)
            ->first();
        if ($check_before){
            if(Storage::cloud()->deleteDirectory($check_before['path'])){
                $return = true;
            }
        }
        return $return;
    }

    public function renameDir($new_name, $old_name, $path = null)
    {
        $return = false;
        $new_name = trim($new_name);
        $old_name = trim($old_name);
        $recursive = false; // Get subdirectories also?
        $check_before = collect(Storage::cloud()->listContents($path, $recursive))
            ->where('type', '=', 'dir')
            ->where('filename', '=', $old_name)
            ->first();
        if ($check_before)
        {
            if(Storage::cloud()->move($check_before['path'], $new_name)){
                $return = true;
            }
        }
        return $return;
    }

    public function upFile($path_info, $path = null)
    {
        $return = false;
        if (\File::exists($path_info)){
            $filename = pathinfo($path_info)['basename'];
            $contents = File::get($path_info);
            if (Storage::cloud()->put($path.'/'.$filename,$contents))
            {
                $recursive = false; // Get subdirectories also?
                $file = collect(Storage::cloud()->listContents($path, $recursive))
                    ->where('type', '=', 'file')
                    ->where('filename', '=', pathinfo($filename, PATHINFO_FILENAME))
                    ->where('extension', '=', pathinfo($filename, PATHINFO_EXTENSION))
                    ->sortBy('timestamp')
                    ->last();
                $return = $file['path'];
            }
        }
        return $return;
    }

    public function deleteFile($filename, $path, $parent_path = null)
    {
        $return = false;
        $name = trim($filename);
        $recursive = false; // Get subdirectories also?
        $check_before = collect(Storage::cloud()->listContents($parent_path, $recursive))
            ->where('type', '=', 'file')
            ->where('name', '=', $filename)
            ->where('path','=',$path)
            ->first();
        if ($check_before){
            if(Storage::cloud()->delete($check_before['path'])){
                $return = true;
            }
        }
        return $return;
    }

    /*END GOOGLE API*/
}
