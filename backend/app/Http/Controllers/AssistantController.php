<?php

namespace App\Http\Controllers;

use App\Models\AssistantSession;
use App\Services\CareerAssistantService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AssistantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => AssistantSession::where('user_id', $request->user()->id)->latest('updated_at')->get()->map(fn ($s) => $this->data($s, false))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['cv_id' => ['nullable', 'integer'], 'mode' => ['required', 'in:coach,interview'], 'language' => ['sometimes', 'required', 'in:en,ms']]);
        if (! empty($data['cv_id'])) abort_unless($request->user()->role === 'student' && $request->user()->generatedCvs()->whereKey($data['cv_id'])->exists(), 404);
        abort_if($request->user()->role === 'admin' && $data['mode'] === 'interview', 422, 'Interview practice is available in the student workspace.');
        $session = AssistantSession::create(['user_id' => $request->user()->id, 'generated_cv_id' => $data['cv_id'] ?? null, 'mode' => $data['mode'], 'language' => $data['language'] ?? null, 'messages' => []]);
        return response()->json($this->data($session), 201);
    }

    public function show(Request $request, AssistantSession $session): JsonResponse
    {
        $this->owner($request, $session);
        return response()->json($this->data($session));
    }

    public function message(Request $request, AssistantSession $session, CareerAssistantService $assistant): JsonResponse
    {
        $this->owner($request, $session);
        $data = $request->validate(['message' => ['required', 'string', 'max:1800'], 'cv_id'=>['sometimes','nullable','integer']]);
        if (!empty($data['cv_id'])) abort_unless($request->user()->role==='student' && $request->user()->generatedCvs()->whereKey($data['cv_id'])->exists(),404);
        $lock = Cache::lock('assistant:'.$request->user()->id, 240);
        abort_unless($lock->get(), 409, 'Navi is still replying. Please wait before sending another message.');
        try {
            set_time_limit(240);
            $session->refresh();
            // A CV's Ask Navi action can supply its document without a visible context selector.
            if (array_key_exists('cv_id',$data)) { $session->generated_cv_id=$data['cv_id']; $session->unsetRelation('cv'); }
            $messages = $session->messages ?? [];
            abort_if(count($messages) >= 40, 422, 'This conversation is full. Start a new conversation to continue.');
            $reply = $assistant->reply($request->user(), $session, $data['message']);
            $messages[] = ['role' => 'user', 'content' => $data['message'], 'createdAt' => now()->toISOString()];
            $messages[] = $reply;
            $session->update(['messages' => $messages, 'title' => count($messages) === 2 ? Str::limit($data['message'], 100, '') : $session->title]);
        } finally { $lock->release(); }
        return response()->json($this->data($session->refresh()));
    }

    public function destroy(Request $request, AssistantSession $session): JsonResponse
    {
        $this->owner($request, $session);
        $session->delete();
        return response()->json(['message' => 'Conversation deleted.']);
    }

    private function owner(Request $request, AssistantSession $session): void
    {
        abort_unless($session->user_id === $request->user()->id, 404);
    }

    private function data(AssistantSession $session, bool $messages = true): array
    {
        return ['id' => $session->id, 'title' => $session->title, 'mode' => $session->mode, 'language' => $session->language, 'cvId' => $session->generated_cv_id, 'messages' => $messages ? $session->messages : [], 'updatedAt' => $session->updated_at->toISOString()];
    }
}
