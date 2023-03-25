<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    public function saveApkFile(Request $request){

        $apk_file_names = [];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $fileName = $file->getClientOriginalName();
                $finalName = date('his') .'_'. $fileName;
                $file->storeAs('uploads/apk_inserted',$finalName,'public');

                $apk_file_names[] = $finalName;
            }

            return response()->json($apk_file_names);
        }
        return response()->json('Upload Error!');
    }

    public function saveNamesListFile(Request $request){

        if($request->hasFile('selectedFile')){
            $file = $request->file('selectedFile');
            $fileName = $file->getClientOriginalName();
            $finalName = date('his') .'_'. $fileName;
            $path = $request->file('selectedFile')->storeAs('uploads/lists',$finalName,'public');

            return response()->json('Upload success!');
        }

        return response()->json('Upload error!');
    }

    public function downloadApkFile(Request $request){

        $url = 'https://d.apkpure.com/b/APK/'.$request->package_name.'?version=latest';

        $file_prefix = date('his');

        $process = new Process(['python3', '../scripts/DownloadFile.py', $url, $file_prefix]);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json('APK download failed!');
        }

        $file_path = $process->getOutput();

        return response()->json($file_path);
    }
}
