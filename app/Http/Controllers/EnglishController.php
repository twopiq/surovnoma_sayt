<?php

namespace App\Http\Controllers;

use App\Models\EnglishSet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
        $kind = $request->validate(['kind' => ['nullable', Rule::in([EnglishSet::KIND_WORDS, EnglishSet::KIND_VERBS])]])['kind'] ?? EnglishSet::KIND_WORDS;

        $set = new EnglishSet(['kind' => $kind] + $this->validated($request, $kind));
        $set->user()->associate($request->user());
        $set->save();

        return response()->json($set->toClient());
    }

    public function update(Request $request, int $set): JsonResponse
    {
        $model = $this->query($request)->findOrFail($set);
        // To'plam turi yaratilgandan keyin o'zgarmaydi.
        $model->update($this->validated($request, $model->kind));

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

    private function validated(Request $request, string $kind): array
    {
        $verbs = $kind === EnglishSet::KIND_VERBS;

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'desc' => ['nullable', 'string', 'max:500'],
            'best' => ['nullable', 'numeric', 'gt:0', 'lt:1000000'],
            'cards' => ['required', 'array', 'max:2000'],
            'cards.*.id' => ['nullable', 'string', 'max:40', 'regex:/^[\w-]+$/'],
            'cards.*.term' => ['nullable', 'string', 'max:1000'],
            'cards.*.def' => ['nullable', 'string', 'max:1000'],
            'cards.*.v2' => ['nullable', 'string', 'max:200'],
            'cards.*.v3' => ['nullable', 'string', 'max:200'],
            'cards.*.lvl' => ['nullable', 'integer', 'between:0,2'],
        ]);

        $cards = collect($data['cards'])
            ->map(fn (array $card): array => [
                'id' => $card['id'] ?? Str::random(8),
                'term' => trim((string) ($card['term'] ?? '')),
                ...($verbs ? ['v2' => trim((string) ($card['v2'] ?? '')), 'v3' => trim((string) ($card['v3'] ?? ''))] : []),
                'def' => trim((string) ($card['def'] ?? '')),
                'lvl' => (int) ($card['lvl'] ?? 0),
            ])
            // Chala to'ldirilgan qatorlar tashlab yuboriladi (fe'lda uchala shakl ham shart).
            ->filter(fn (array $card): bool => ! in_array('', array_diff_key($card, ['lvl' => 0]), true))
            ->values();

        if ($cards->count() < 2) {
            throw ValidationException::withMessages(['cards' => $verbs
                ? __("Kamida 2 ta fe'lni uchala shakli va tarjimasi bilan to'ldiring.")
                : __("Kamida 2 ta atama va ta'rifini to'ldiring.")]);
        }

        return [
            'title' => trim($data['title']),
            'description' => trim((string) ($data['desc'] ?? '')) ?: null,
            'cards' => $cards->all(),
            'best_time' => $data['best'] ?? null,
        ];
    }
}
