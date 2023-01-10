<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\File;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function saveApkFile(Request $request){

        if ($request->get('file')) {
            foreach ($request->get('file') as $file) {
                $fileName = $file->getClientOriginalName();
                $finalName = date('his') .'_'. $fileName;
                $path = $file->storeAs('uploads/apk',$finalName,'public');

                File::create([
                    'name'  =>  $finalName,
                    'type'  =>  'apk',
                    'path'  =>  $path,
                ]);
            }
            return response()->json('Upload success!');
        }
        return response()->json($request);
    }

    public function saveNamesListFile(Request $request){

        if($request->hasFile('selectedFile')){
            $file = $request->file('selectedFile');
            $fileName = $file->getClientOriginalName();
            $finalName = date('his') .'_'. $fileName;
            $path = $request->file('selectedFile')->storeAs('uploads/lists',$finalName,'public');

            File::create([
                'name'  =>  $finalName,
                'type'  =>  'list',
                'path'  =>  $path,
            ]);

            return response()->json('Upload success!');
        }

        return response()->json('Upload error!');
    }

    public function downloadApkFile(Request $request){

        $url = 'https://d.apkpure.com/b/APK/'.$request->package_name.'?version=latest';

        $apk_file = file_get_contents($url);

        Storage::disk('local')->put('uploads/apk/'.$request->package_name,$apk_file);

        return response()->json('Download success!');
    }
}
