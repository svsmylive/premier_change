<?php

namespace App\Http\Controllers\Admin;

class ClientController
{
    public function quickStore(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'telegram' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string'],
        ]);

        // если у тебя comment NOT NULL в миграции — подстрахуем
        $data['telegram'] = $data['telegram'] ?? '';
        $data['comment'] = $data['comment'] ?? '';

        $client = \App\Models\Client::query()->create($data);

        return response()->json([
            'id' => $client->id,
            'name' => $client->name,
        ]);
    }

}
