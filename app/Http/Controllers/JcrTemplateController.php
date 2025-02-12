<?php

namespace App\Http\Controllers;

use App\Models\JcrTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JcrTemplateController extends Controller
{
    public function index()
    {
        return response()->json(
            JcrTemplate::select('id', 'name', 'is_default')->get()
        );
    }

    public function show(JcrTemplate $template)
    {
        return response()->json($template);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|json',
            'is_default' => 'boolean'
        ]);

        $template = JcrTemplate::create([
            'name' => $validated['name'],
            'content' => json_decode($validated['content'], true),
            'is_default' => $validated['is_default'] ?? false
        ]);

        return response()->json($template, 201);
    }

    public function update(Request $request, JcrTemplate $template)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'json'],
            'is_default' => ['sometimes', 'boolean']
        ]);

        $template->update([
            'name' => $validated['name'] ?? $template->name,
            'content' => isset($validated['content']) ? json_decode($validated['content'], true) : $template->content,
            'is_default' => $validated['is_default'] ?? $template->is_default
        ]);

        return response()->json($template);
    }
} 