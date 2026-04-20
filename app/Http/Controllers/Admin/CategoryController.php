<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::query()->orderBy('name')->get(),
            'priorities' => TicketPriority::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'default_priority' => ['required', Rule::in(array_column(TicketPriority::cases(), 'value'))],
        ]);

        Category::create([
            ...$data,
            'slug' => $this->uniqueSlug($data['name']),
            'is_active' => true,
        ]);

        return back()->with('status', "Kategoriya qo'shildi.");
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'default_priority' => ['required', Rule::in(array_column(TicketPriority::cases(), 'value'))],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            ...$data,
            'slug' => $category->name === $data['name'] ? $category->slug : $this->uniqueSlug($data['name'], $category),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', "Kategoriya yangilandi.");
    }

    protected function uniqueSlug(string $name, ?Category $ignore = null): string
    {
        $baseSlug = Str::slug($name) ?: Str::lower(Str::random(8));
        $slug = $baseSlug;
        $index = 2;

        while (Category::query()
            ->where('slug', $slug)
            ->when($ignore, fn ($query) => $query->where('id', '!=', $ignore->id))
            ->exists()) {
            $slug = $baseSlug.'-'.$index;
            $index++;
        }

        return $slug;
    }
}
