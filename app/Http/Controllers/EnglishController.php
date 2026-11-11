<?php

namespace App\Http\Controllers;

use App\Models\EnglishSet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EnglishController extends Controller
{
    public function index(): View
    {
        return view('english.index');
    }

    public function sets(Request $request): JsonResponse
    {
        return response()->json(
            $this->query($request)->latest('id')->get()->map->toClient()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $set = new EnglishSet($this->validated($request));
        $set->user()->associate($request->user());
        $set->save();

        return response()->json($set->toClient());
    }

    public function update(Request $request, int $set): JsonResponse
    {
        $model = $this->query($request)->findOrFail($set);
        $model->update($this->validated($request));

        return response()->json($model->toClient());
    }

    public function destroy(Request $request, int $set): JsonResponse
    {
        $this->query($request)->findOrFail($set)->delete();

        return response()->json([]);
    }

    /** Faqat joriy foydalanuvchining to'plamlari — boshqa birovnikini ochib/o'zgartirib bo'lmaydi. */
    private function query(Request $request)
    {
        return EnglishSet::query()->whereBelongsTo($request->user());
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'desc' => ['nullable', 'string', 'max:500'],
            'best' => ['nullable', 'numeric', 'gt:0', 'lt:1000000'],
            'cards' => ['required', 'array', 'max:2000'],
            'cards.*.id' => ['nullable', 'string', 'max:40', 'regex:/^[\w-]+$/'],
            'cards.*.term' => ['nullable', 'string', 'max:1000'],
            'cards.*.def' => ['nullable', 'string', 'max:1000'],
            'cards.*.lvl' => ['nullable', 'integer', 'between:0,2'],
        ]);

        $cards = collect($data['cards'])
            ->map(fn (array $card): array => [
                'id' => $card['id'] ?? Str::random(8),
                'term' => trim((string) ($card['term'] ?? '')),
                'def' => trim((string) ($card['def'] ?? '')),
                'lvl' => (int) ($card['lvl'] ?? 0),
            ])
            ->filter(fn (array $card): bool => $card['term'] !== '' && $card['def'] !== '')
            ->values();

        if ($cards->count() < 2) {
            throw ValidationException::withMessages(['cards' => __("Kamida 2 ta atama va ta'rifini to'ldiring.")]);
        }

        return [
            'title' => trim($data['title']),
            'description' => trim((string) ($data['desc'] ?? '')) ?: null,
            'cards' => $cards->all(),
            'best_time' => $data['best'] ?? null,
        ];
    }
}
