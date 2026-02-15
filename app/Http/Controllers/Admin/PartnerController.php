<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function index(Request $request)
    {
        $q = Partner::query();

        if ($search = trim((string)$request->get('q'))) {
            $q->where('name', 'like', "%{$search}%");
        }

        $partners = $q->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.partners.index', compact('partners'));
    }

    public function create()
    {
        return view('admin.partners.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'comment' => ['nullable', 'string'],
        ]);

        // у тебя comment NOT NULL в миграции, поэтому страхуемся
        $data['comment'] = $data['comment'] ?? '';

        Partner::query()->create($data);

        return redirect()
            ->route('partners.index')
            ->with('success', 'Партнёр создан');
    }

    public function edit(Partner $partner)
    {
        return view('admin.partners.edit', compact('partner'));
    }

    public function update(Request $request, Partner $partner)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'comment' => ['nullable', 'string'],
        ]);

        $data['comment'] = $data['comment'] ?? '';

        $partner->fill($data)->save();

        return redirect()
            ->route('partners.index')
            ->with('success', 'Партнёр обновлён');
    }

    public function destroy(Partner $partner)
    {
        // позже можно запретить удаление, если partner_id используется в crypto_trades
        $partner->delete();

        return redirect()
            ->route('partners.index')
            ->with('success', 'Партнёр удалён');
    }
}
