<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use App\Models\User;

class AuthController extends Controller {

    /**
     * @brief The function ensures the return
     * @param Authenticatable $user Auth user data
     * @param string $token Auth token string
     * @return array Auth token data
     */
    private function createResponse(Authenticatable $user, string $token): array {
        return [
            'status' => 'success',
            'user' => $user,
            'auth' => [
                'token' => $token,
                'type' => "bearer",
            ],
        ];
    }

    /**
     * @brief The function ensures the user login
     * @param Request $request HTTP request data
     * @return JsonResponse Auth token data
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
        return response()->json($this->createResponse($user, $token));
    }

    /**
     * @brief The function ensures the user registration
     * @param Request $request HTTP request data
     * @return JsonResponse Auth token data
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

        return response()->json($this->createResponse($user, $token), 200);
    }

    /**
     * @brief The function ensures the user logout
     * @return JsonResponse Operation status
     */
    public function logout(): JsonResponse {
        Auth::logout();

        return response()->json([
            'state' => 'success',
        ]);
    }

    /**
     * @brief The function ensures the refresh of auth token
     * @return JsonResponse New auth token data
     */
    public function refresh(): JsonResponse {
        $user = Auth::user();
        $token = Auth::refresh();

        return response()->json($this->createResponse($user, $token));
    }
}
