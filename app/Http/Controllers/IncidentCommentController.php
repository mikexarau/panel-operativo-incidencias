<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncidentCommentRequest;
use App\Models\Incident;
use Illuminate\Http\RedirectResponse;

class IncidentCommentController extends Controller
{
    public function store(StoreIncidentCommentRequest $request, Incident $incident): RedirectResponse
    {
        $incident->comments()->create([
            'author_name' => $request->validated('actor_name'),
            'body' => $request->validated('body'),
        ]);

        return back()->with('success', 'Comentario añadido.');
    }
}
