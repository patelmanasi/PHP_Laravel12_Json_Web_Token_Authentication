<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends BaseController
{
    // Register new user
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'c_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);

        return $this->sendResponse(['user' => $user], 'User registered successfully.');
    }

    // Login user and get JWT
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials)) {
            return $this->sendError('Unauthorised', ['error' => 'Invalid credentials']);
        }

        return $this->sendResponse($this->respondWithToken($token), 'User logged in successfully.');
    }

    // Logout user
    public function logout()
    {
        auth()->logout();
        return $this->sendResponse([], 'Logged out successfully.');
    }

    // Refresh JWT token
    public function refresh()
    {
        return $this->sendResponse($this->respondWithToken(auth()->refresh()), 'Token refreshed successfully.');
    }

    // Get profile
    public function profile()
    {
        return $this->sendResponse(auth()->user(), 'User profile retrieved successfully.');
    }

    // Update profile
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
        ]);

        $user->update($request->only('name', 'email'));

        return $this->sendResponse($user, 'Profile updated successfully.');
    }

    // Change password
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->sendError('Current password is incorrect');
        }

        $user->password = bcrypt($request->new_password);
        $user->save();

        return $this->sendResponse([], 'Password changed successfully.');
    }

    // Forgot password (send reset token)
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $token = Str::random(60);

        \DB::table('password_resets')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => now(),
        ]);

        // Here you can send email with token
        // Mail::to($request->email)->send(new ResetPasswordMail($token));

        return $this->sendResponse(['token' => $token], 'Password reset token generated.');
    }

    // Format JWT response
    protected function respondWithToken($token)
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ];
    }
}