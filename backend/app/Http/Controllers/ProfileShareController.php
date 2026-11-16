<?php

namespace App\Http\Controllers;

use App\Models\ProfileShare;
use App\Services\SharedProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileShareController extends Controller
{
    private function student(Request $request): void { abort_unless($request->user()->role === 'student', 403); }
    private function validated(Request $request): array
    {
        return $request->validate(['selection' => 'required|array|min:1|max:6', 'selection.*' => ['required', 'distinct', Rule::in(SharedProfile::FIELDS)], 'days' => ['sometimes', Rule::in([1, 7, 30])]]);
    }
    private function data(ProfileShare $share): array
    {
        $active = !$share->revoked_at && $share->expires_at->isFuture();
        return ['id' => $share->id, 'path' => $active ? '/share/'.$share->encrypted_token : null, 'selection' => $share->selection, 'expiresAt' => $share->expires_at->toISOString(), 'active' => $active, 'revoked' => (bool) $share->revoked_at];
    }
    public function index(Request $request)
    {
        $this->student($request);
        return response()->json(['data' => ProfileShare::where('user_id', $request->user()->id)->latest()->get()->map(fn ($s) => $this->data($s))]);
    }
    public function preview(Request $request, SharedProfile $profile)
    {
        $this->student($request);
        return response()->json($profile->data($request->user(), $this->validated($request)['selection']));
    }
    public function store(Request $request)
    {
        $this->student($request);
        $data = $this->validated($request);
        abort_if(ProfileShare::where('user_id', $request->user()->id)->whereNull('revoked_at')->where('expires_at', '>', now())->count() >= 10, 422, 'You have 10 active links. Revoke an old link before creating another.');
        $token = Str::random(64);
        $share = ProfileShare::create(['user_id' => $request->user()->id, 'token_hash' => hash('sha256', $token), 'encrypted_token' => $token, 'selection' => $data['selection'], 'expires_at' => now()->addDays($data['days'] ?? 7)]);
        return response()->json($this->data($share), 201);
    }
    public function destroy(Request $request, ProfileShare $share)
    {
        $this->student($request);
        abort_unless($share->user_id === $request->user()->id, 404);
        $share->update(['revoked_at' => now()]);
        return response()->json(['message' => 'Link revoked.']);
    }
    public function show(string $token, SharedProfile $profile)
    {
        abort_unless(preg_match('/^[a-zA-Z0-9]{64}$/D', $token), 404);
        $share = ProfileShare::with('user')->where('token_hash', hash('sha256', $token))->whereNull('revoked_at')->where('expires_at', '>', now())->firstOrFail();
        abort_unless($share->user && $share->user->is_active, 404);
        return response()->json($profile->data($share->user, $share->selection))->withHeaders(['Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex, nofollow', 'Referrer-Policy' => 'no-referrer']);
    }
}
