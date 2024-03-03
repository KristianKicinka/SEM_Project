<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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


    public function getFilesForAdmin(): JsonResponse {
        $files = DB::table('files')
        ->join('applications','files.app_id', '=','applications.id')
        ->select('files.id AS file_id', 'files.name AS file_name','files.type AS file_type', 'files.path AS file_path', 'applications.name AS app_name')
        ->get();

        return response()->json($files);
    }

    public function deleteFile(Request $request): JsonResponse {
        
        DB::table('files')->where('files.id', '=', $request->file_id)->delete();

        return response()->json('File was deleted!');
    }
}
