<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|min:5|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'data'    => $validator->errors(),
            ], 422);
        }
        $user = User::create([
            'name'     => $request->get('name'),
            'email'    => $request->get('email'),
            'password' => Hash::make($request->get('password')),
        ]);
        $token = JWTAuth::fromUser($user);
        return response()->json([
            'status'  => true,
            'message' => 'User created successfully',
            'data'    => [
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
                    'status'  => false,
                    'message' => 'Invalid credentials',
                    'data'    => [],
                ], 401);
            }
            $user = auth()->user();
            return response()->json([
                'status'  => true,
                'message' => 'Login successful',
                'data'    => [
                    'user'  => $user,
                    'token' => $token,
                ],
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
                'data'    => [],
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

    public function profile()
    {
        if (! $user = JWTAuth::parseToken()->authenticate()) {
            return response()->json([
                'status'  => false,
                'message' => 'User not found',
                'data'    => [],
            ]);
        }
        return response()->json([
            'status'  => true,
            'message' => 'User data fetched successfully',
            'data'    => $user,
        ]);
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json([
            'status'  => true,
            'message' => 'User logged out successfully',
            'data'    => [],
        ]);
    }

    public function refresh()
    {
        return response()->json([
            'status'  => true,
            'message' => 'Token refreshed successfully',
            'data'    => [
                'token' => JWTAuth::refresh(JWTAuth::getToken()),
            ],
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'data'    => $validator->errors(),
            ], 422);
        }
        $token = Str::random(60);
        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => now()]
        );
        $resetLink = url("api/reset-password?token={$token}&email={$request->email}");
        return response()->json([
            'status'  => true,
            'message' => 'Password reset link sent to your email',
            'data'    => [
                'resetLink' => $resetLink,
            ],
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token'           => 'required',
            'email'           => 'required|email|exists:users,email',
            'password'        => 'required|min:8',
            'confirmPassword' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'data'    => $validator->errors(),
            ], 422);
        }
        $token = DB::table('password_resets')->where([
            'token' => $request->token,
            'email' => $request->email,
        ])->first();

        if (! $token) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid token',
                'data'    => [
                    'token' => ['Invalid token'],
                ],
            ], 401);
        }

        $user = DB::table('users')->where('email', $request->email)->first();
        if ($user) {
            DB::table('users')->where('email', $request->email)->update([
                'password' => Hash::make($request->password),
            ]);
            DB::table('password_resets')->where('email', $request->email)->delete();
            return response()->json([
                'status'  => true,
                'message' => 'Password reset successful',
                'data'    => [],
            ]);
        }
        return response()->json([
            'status'  => false,
            'message' => 'User not found',
            'data'    => [],
        ], 400);
    }

}
