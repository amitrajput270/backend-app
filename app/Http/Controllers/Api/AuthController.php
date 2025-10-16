<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    public function index()
    {
        return response()->json([
            'statusCode' => 'TXN',
            'message'    => 'Users fetched successfully',
            'data'       => User::all(),
        ]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|min:5|max:255',
            'email'    => 'required|email:rfc,dns|unique:users',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 'ERR',
                'message'    => 'Validation error',
                'data'       => $validator->errors(),
            ], 422);
        }
        $user = User::create([
            'name'     => $request->get('name'),
            'email'    => $request->get('email'),
            'password' => Hash::make($request->get('password')),
        ]);
        $token = JWTAuth::fromUser($user);
        return response()->json([
            'statusCode' => 'TXN',
            'message'    => 'User created successfully',
            'data'       => [
                'user'  => $user,
                'token' => $token,
            ],
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'statusCode' => 'ERR',
                    'message'    => 'Invalid credentials',
                    'data'       => [],
                ], 400);
            }
            $user = auth()->user();
            return response()->json([
                'statusCode' => 'TXN',
                'message'    => 'Login successful',
                'data'       => [
                    'user'  => $user,
                    'token' => $token,
                ],
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'statusCode' => 'ERR',
                'message'    => $e->getMessage(),
                'data'       => [],
            ], $e->getStatusCode());
        }
    }

    public function profile()
    {
        if (! $user = JWTAuth::parseToken()->authenticate()) {
            return response()->json([
                'statusCode' => 'ERR',
                'message'    => 'User not found',
                'data'       => [],
            ], 400);
        }
        return response()->json([
            'statusCode' => 'TXN',
            'message'    => 'User data fetched successfully',
            'data'       => $user,
        ]);
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json([
            'statusCode' => 'TXN',
            'message'    => 'User logged out successfully',
            'data'       => [],
        ]);
    }

    public function refresh()
    {
        try {
            return response()->json([
                'statusCode' => 'TXN',
                'message'    => 'Token refreshed successfully',
                'data'       => [
                    'token' => JWTAuth::refresh(JWTAuth::getToken()),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'statusCode' => 'ERR',
                'message'    => $e->getMessage(),
                'data'       => [],
            ], $e->getStatusCode());
        }
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 'ERR',
                'message'    => 'Validation error',
                'data'       => $validator->errors(),
            ], 422);
        }
        try {
            $token = Str::random(60);
            \App\Models\PasswordReset::updateOrInsert(
                ['email' => $request->email],
                ['token' => $token, 'created_at' => now()]
            );
            $resetLink = url("api/reset-password?token={$token}&email={$request->email}");
            return response()->json([
                'statusCode' => 'TXN',
                'message'    => 'Password reset link sent to your email',
                'data'       => [
                    'resetLink' => $resetLink,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'statusCode' => 'ERR',
                'message'    => $e->getMessage(),
                'data'       => [],
            ], $e->getStatusCode());
        }
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token'           => 'required|string',
            'email'           => 'required|email',
            'password'        => 'required|min:8',
            'confirmPassword' => 'required|same:password',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 'ERR',
                'message'    => 'Validation error',
                'data'       => $validator->errors(),
            ], 422);
        }
        if (! $user = User::where('email', $request->email)->first()) {
            return response()->json([
                'statusCode' => 'ERR',
                'message'    => 'User not found',
                'data'       => [],
            ], 400);
        }
        $token = $user->passwordReset;
        if (! $token || $token->token !== $request->token) {
            return response()->json([
                'statusCode' => 'ERR',
                'message'    => 'Invalid token',
                'data'       => [
                    'token' => ['Invalid token'],
                ],
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);
        $user->passwordReset->delete();
        return response()->json([
            'statusCode' => 'TXN',
            'message'    => 'Password reset successful',
            'data'       => [],
        ]);
    }

    public function interviewQuestion()
    {
        $input  = [5, 1, 6, 2, 2, 3, 4, 4, 5];
        $unique = [];
        //remove duplicates
        for ($i = 0; $i < count($input); $i++) {
            $isDuplicate = false;
            for ($j = 0; $j < count($unique); $j++) {
                if ($input[$i] == $unique[$j]) {
                    $isDuplicate = true;
                    break;
                }
            }
            if (! $isDuplicate) {
                $unique[] = $input[$i];
            }
        }

        $num    = count($unique);
        $uarray = [];
        for ($i = 0; $i < $num - 1; $i++) {
            for ($j = 0; $j < $num - $i - 1; $j++) {
                if ($unique[$j] > $unique[$j + 1]) {
                    $temp           = $unique[$j];
                    $unique[$j]     = $unique[$j + 1];
                    $unique[$j + 1] = $temp;
                }
            }
        }

        $max = 0;

        for ($i = 0; $i < count($unique) - 1; $i++) {
            for ($j = $i + 1; $j < count($unique); $j++) {
                if ($unique[$i] < $unique[$j]) {
                    // Swap values
                    $temp       = $unique[$i];
                    $unique[$i] = $unique[$j];
                    $unique[$j] = $temp;
                }
            }
        }

        foreach ($unique as $key => $value) {
            if ($unique[$key] > $max) {
                $max = $value;
            }
        }

        $min = $max;
        for ($i = 0; $i < $num; $i++) {
            if ($unique[$i] < $min) {
                $min = $unique[$i];
            }
        }

        return response()->json([
            'statusCode' => 'TXN',
            'message'    => 'Unique values fetched successfully',
            'data'       => [
                'unique' => $unique,
                'max'    => $max,
                'min'    => $min,
            ],
        ]);
    }
}
