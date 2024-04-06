<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SearchController extends Controller {

    /**
     * @brief The function ensures the return of the app data from SerpApi
     * @param Request $request HTTP request data
     * @return JsonResponse Applications data
     */
    public function index(Request $request): JsonResponse {
        $url = "https://serpapi.com/search.json";
        $api_key = "e694fba92d38dbfb77e6d4fe838fba1e7e259465ed7a1a519400cabf4453a593";
        $results = [];

        $query = [
            "engine" => "google_play",
            "store" => "apps",
            "q" => $request->input("app_name"),
            "api_key" => $api_key,
        ];

        // API response
        $response = Http::withHeaders(['Accept'=>'application/json'])->get($url, $query)->json();

        // Process first item returned from SerpApi
        if(array_key_exists('app_highlight', $response)){
            $results[] = [
                'app_name' => $response['app_highlight']['title'],
                'package_name' => $response['app_highlight']['product_id'],
                'thumbnail' => $response['app_highlight']['thumbnail'],
            ];
        }

        // Process the others items in separate data object
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
