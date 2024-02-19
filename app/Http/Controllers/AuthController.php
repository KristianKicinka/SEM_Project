<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use App\Models\User;

class AuthController extends Controller {

    /**
     * @param $user
     * @param $token
     * @param $token_type
     * @return array
     */
    private function createResponse($user, $token, $token_type): array {
        return [
            'status' => 'success',
            'user' => $user,
            'auth' => [
                'token' => $token,
                'type' => $token_type,
            ],
        ];
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $token = JWTAuth::attempt(['email' => $request->email, 'password' => $request->password]);

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = Auth::user();
        return response()->json($this->createResponse($user, $token, "bearer"));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse {

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'surname' => 'required',
            'email' => 'required|email|unique:users',
            'phone' => 'required|unique:users',
            'password' => 'required|min:8',
            're_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'surname' => $request->surname,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'basic_user',
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json($this->createResponse($user, $token, "bearer"), 200);

    }

    /**
     * @return JsonResponse
     */
    public function logout(): JsonResponse {
        Auth::logout();

        return response()->json([
            'state' => 'success',
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function refresh(): JsonResponse {
        $user = Auth::user();
        $token = Auth::refresh();

        return response()->json($this->createResponse($user, $token, "bearer"));
    }
}
