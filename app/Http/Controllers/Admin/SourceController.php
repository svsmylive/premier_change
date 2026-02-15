<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Source;
use Illuminate\Http\Request;

class SourceController extends Controller
{
    public function index(Request $request)
    {
        $q = Source::query();

        if ($search = trim((string)$request->get('q'))) {
            $q->where('name', 'like', "%{$search}%");
        }

        $sources = $q->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.sources.index', compact('sources'));
    }

    public function create()
    {
        return view('admin.sources.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'comment' => ['nullable', 'string'],
        ]);

        // comment NOT NULL по твоей миграции
        $data['comment'] = $data['comment'] ?? '';

        Source::query()->create($data);

        return redirect()
            ->route('sources.index')
            ->with('success', 'Источник создан');
    }

    public function edit(Source $source)
    {
        return view('admin.sources.edit', compact('source'));
    }

    public function update(Request $request, Source $source)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'comment' => ['nullable', 'string'],
        ]);

        $data['comment'] = $data['comment'] ?? '';

        $source->fill($data)->save();

        return redirect()
            ->route('sources.index')
            ->with('success', 'Источник обновлён');
    }

    public function destroy(Source $source)
    {
        $source->delete();

        return redirect()
            ->route('sources.index')
            ->with('success', 'Источник удалён');
    }
}
