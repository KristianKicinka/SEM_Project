<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\File;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function saveApkFile(Request $request){

        $apk_file_names = [];

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $fileName = $file->getClientOriginalName();
                $finalName = date('his') .'_'. $fileName;
                $file->storeAs('uploads/apk',$finalName,'public');

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
        
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://d.apkpure.com/b/APK/com.facebook.orca?version=latest',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return response()->json($response);
        
        $file = file_get_contents($url);

        return response()->json($url);

        $fileName = basename($url);

        $finalName = date('his') .'_'. $fileName;

        

        Storage::disk('local')->put('uploads/apk/'.$finalName, $file);

        return response()->json('Download success!');
    }
}
