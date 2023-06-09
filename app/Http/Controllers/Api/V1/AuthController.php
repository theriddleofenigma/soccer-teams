<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Get token by authenticating the user based on the email and password.
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return response()->json([
            'access_token' => $user->createToken('auth_token')->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Logout the current user.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request): \Illuminate\Http\Response
    {
        $request->user()->currentAccessToken()->delete();
        return Response::noContent();
    }
}
