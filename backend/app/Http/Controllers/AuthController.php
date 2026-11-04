<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'programme' => ['required', 'string', 'max:100'],
            'matric_no' => ['required', 'string', 'max:40', 'unique:users,matric_no'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => $validated['password'],
            'role' => 'student',
            'programme' => $validated['programme'],
            'matric_no' => strtoupper($validated['matric_no']),
        ]);

        return response()->json([
            'user' => self::userData($user),
            'token' => $user->createToken('web-app')->plainTextToken,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', strtolower($validated['email']))->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'The email or password is incorrect.',
            ], 422);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'This account is inactive. Contact an administrator.',
            ], 403);
        }

        $user->tokens()->where('name', 'web-app')->delete();

        return response()->json([
            'user' => self::userData($user),
            'token' => $user->createToken('web-app')->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Signed out successfully.']);
    }

    /** @return array<string, mixed> */
    public static function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'isActive' => (bool) $user->is_active,
            'programme' => $user->programme,
            'matricNo' => $user->matric_no,
            'createdAt' => $user->created_at?->toISOString(),
        ];
    }
}
