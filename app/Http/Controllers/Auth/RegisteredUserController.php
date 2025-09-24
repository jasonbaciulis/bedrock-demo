<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        event(new Registered($user));

        Auth::login($user);

        return response()->json([
            'success' => true,
            'redirect' => route('dashboard', absolute: false),
        ]);
    }
}
