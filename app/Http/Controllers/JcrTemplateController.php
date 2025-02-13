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

    public function loadTemplate(JcrTemplate $template)
    {
        return response()->json($template);
    }
    public function deleteTemplate(JcrTemplate $template)
    {
        $template->delete();
        
        return response()->json(null, 204);
    }

    public function createTemplate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'sometimes|json',
            'is_default' => 'sometimes|boolean'
        ]);

        $template = JcrTemplate::create([
            'name' => $validated['name'],
            'content' => isset($validated['content']) ? json_decode($validated['content'], true) : null,
            'is_default' => $validated['is_default'] ?? false
        ]);

        return response()->json($template, 201);
    }

    public function updateTemplate(Request $request, JcrTemplate $template)
    {

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required'],
            'is_default' => ['sometimes', 'boolean']
        ]);

        $template->update([
            'name' => $validated['name'] ?? $template->name,
            'content' => isset($validated['content']) ? $validated['content'] : $template->content,
            'is_default' => $validated['is_default'] ?? $template->is_default
        ]);

        return response()->json($template);
    }
} 