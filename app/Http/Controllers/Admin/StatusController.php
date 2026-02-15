<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Status;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public function index(Request $request)
    {
        $q = Status::query();

        if ($search = trim((string)$request->get('q'))) {
            $q->where('name', 'like', "%{$search}%");
        }

        if (($isEnd = $request->get('is_end')) !== null && $isEnd !== '') {
            $q->where('is_end', (bool)$isEnd);
        }

        $statuses = $q->orderBy('id')->paginate(20)->withQueryString();

        return view('admin.statuses.index', compact('statuses'));
    }

    public function create()
    {
        return view('admin.statuses.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_end' => ['nullable'], // checkbox
        ]);

        $data['is_end'] = (bool)($data['is_end'] ?? false);

        Status::query()->create($data);

        return redirect()
            ->route('statuses.index')
            ->with('success', 'Статус создан');
    }

    public function edit(Status $status)
    {
        return view('admin.statuses.edit', compact('status'));
    }

    public function update(Request $request, Status $status)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_end' => ['nullable'],
        ]);

        $data['is_end'] = (bool)($data['is_end'] ?? false);

        $status->fill($data)->save();

        return redirect()
            ->route('statuses.index')
            ->with('success', 'Статус обновлён');
    }

    public function destroy(Status $status)
    {
        // позже можно запретить удаление, если используется в сделках
        $status->delete();

        return redirect()
            ->route('statuses.index')
            ->with('success', 'Статус удалён');
    }
}
