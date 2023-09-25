<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller {

    /**
     * @return JsonResponse
     */
    public function getUsersForAdmin(): JsonResponse {
        $users = DB::table('users')->select('id', 'name','surname', 'email', 'phone', 'role')->get();

        return response()->json(['users' => $users]);
    }
}
