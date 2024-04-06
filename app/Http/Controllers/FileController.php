<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class FileController extends Controller {

    /**
     * @brief The function ensures the saving text file with apps package names
     * @param Request $request HTTP request data
     * @return JsonResponse Operation status message
     */
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

    /**
     * @brief The function ensures the getting all files from database
     * @return JsonResponse List of files from database
     */
    public function getFilesForAdmin(): JsonResponse {
        $files = DB::table('files')
        ->join('applications','files.app_id', '=','applications.id')
        ->select('files.id AS file_id', 'files.name AS file_name','files.type AS file_type',
            'files.path AS file_path', 'applications.name AS app_name')
        ->get();

        return response()->json($files);
    }

    /**
     * @brief The function ensures the deleting files from database
     * @param Request $request HTTP request data
     * @return JsonResponse Operation status message
     */
    public function deleteFile(Request $request): JsonResponse {

        $file_path = DB::table('files')->where('files.id', '=', $request->file_id)->first()->path;
        // Extract relative path of file to delete
        $relative_path = substr($file_path,
            strpos($file_path, '/storage/app') + strlen('/storage/app'));

        if (Storage::exists($relative_path)) {
            Storage::delete($relative_path);
        }

        DB::table('files')->where('files.id', '=', $request->file_id)->delete();

        return response()->json('File was deleted!');
    }
}
