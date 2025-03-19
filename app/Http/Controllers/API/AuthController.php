<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
     // Login API
     public function login(Request $request)
     {
         try {
             $request->validate([
                 'email' => 'required|email',
                 'password' => 'required'
             ]);
     
             $user = User::where('email', $request->email)->first();
     
             // Check if user exists and password is correct
             if (!$user || !Hash::check($request->password, $user->password)) {
                 return response()->json([
                    'success' => false,
                    'message' => 'Invalid Credential!'
                ], 401);
             }
     
            // Create short-lived access token for checking user auth
            $accessToken = $user->createToken('access_token', ['*'], now()->addMinutes(15))->plainTextToken;
    
            // Create long-lived refresh token for when access token expired to refresh access token
            $refreshToken = $user->createToken('refresh_token', ['*'], now()->addDays(7))->plainTextToken;
    
             return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_at' => now()->addMinutes(15)->timestamp
             ], 200);

         } catch (\Exception $e) {
             return response()->json([
                 'success' => false,
                 'message' => $e->getMessage()
             ], 500);
         }
     }

     // Logout API
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::updateOrCreate([
                'email' => $googleUser->getEmail(),
            ], [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'password' => bcrypt('password') // Default password
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'access_token' => $token,
                'token_type' => 'Bearer'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Google login failed'], 401);
        }
    }

    public function register(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Create short-lived access token for checking user auth
        $accessToken = $user->createToken('access_token', ['*'], now()->addMinutes(15))->plainTextToken;

        // Create long-lived refresh token for when access token expired to refresh access token
        $refreshToken = $user->createToken('refresh_token', ['*'], now()->addDays(7))->plainTextToken;

 
         return response()->json([
            'success' => true,
            'message' => 'Register successful',
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_at' => now()->addMinutes(15)->timestamp
         ], 200);
    }

    public function refreshToken(Request $request)
    {
        $user = $request->user();

        // Check if refresh token is valid
        $refreshToken = $request->refresh_token;
        if (!$user || !$refreshToken) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        // Create new access token
        $accessToken = $user->createToken('access_token', ['*'], now()->addMinutes(15))->plainTextToken;
        $accessTokenExpiration = now()->addMinutes(15)->timestamp;

        return response()->json([
            'access_token' => $accessToken,
            'expires_at' => $accessTokenExpiration
        ]);
    }


}
