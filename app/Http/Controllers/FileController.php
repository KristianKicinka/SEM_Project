<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

class FileController extends Controller {


    public function saveNamesListFile(Request $request): JsonResponse {

        if($request->hasFile('selectedFile')){
            $file = $request->file('selectedFile');
            $fileName = $file->getClientOriginalName();
            $finalName = date('his') .'_'. $fileName;
            $path = $request->file('selectedFile')->storeAs('uploads/lists',$finalName,'public');

            return response()->json('Upload success!');
        }

        return response()->json('Upload error!');
    }
}
