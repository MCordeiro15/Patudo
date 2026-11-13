<?php

/**
 * Controlador API para autenticação. Lida com login, logout e obtenção do utilizador autenticado via tokens.
 * API controller for authentication. Handles login, logout, and retrieving the authenticated user via tokens.
 */
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Regista um novo utilizador, cria a conta e retorna o token de autenticação.
     * Registers a new user, creates the account, and returns the authentication token.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:9',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    /**
     * Autentica o utilizador com email e password. Retorna token e dados do utilizador.
     * Authenticates the user with email and password. Returns token and user data.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();
        $user->load('petsitter');
        return new UserResource($user);
    }

    public function myInformation(Request $request)
    {
        $user = $request->user();
        $user->load('petsitter');
        return new UserResource($user);
    }

    /**
     * Termina a sessão do utilizador, invalidando o token de autenticação.
     * Ends the user's session, invalidating the authentication token.
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
