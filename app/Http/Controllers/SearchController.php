<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Http;

class SearchController extends Controller
{

    public function index(Request $request){

        $url = "https://serpapi.com/search.json";
        $api_key = "e694fba92d38dbfb77e6d4fe838fba1e7e259465ed7a1a519400cabf4453a593";
        $results = [];

        $query = [
            "engine" => "google_play",
            "store" => "apps",
            "q" => $request->input("app_name"),
            "api_key" => $api_key,
        ];

        $response = Http::withHeaders(['Accept'=>'application/json'])->get($url, $query)->json();

        if(array_key_exists('app_highlight', $response)){
            $results[] = [
                'app_name' => $response['app_highlight']['title'],
                'package_name' => $response['app_highlight']['product_id'],
                'thumbnail' => $response['app_highlight']['thumbnail'],
            ];
        }

        if(array_key_exists('organic_results', $response)){
            foreach ($response['organic_results'][0]['items'] as $item){
                $results[] = [
                    'app_name' => $item['title'],
                    'package_name' => $item['product_id'],
                    'thumbnail' => $item['thumbnail'],
                ];
            }
        }

        return response()->json($results);
    }
}
