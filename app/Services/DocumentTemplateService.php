<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DocumentTemplateService
{
    /**
     * Get all templates for current user
     */
    public function getAll(): Collection
    {
        return DocumentTemplate::forCurrentUser()
            ->orderBy('name')
            ->get();
    }

    /**
     * Get active templates for current user
     */
    public function getActive(): Collection
    {
        return DocumentTemplate::forCurrentUser()
            ->active()
            ->orderBy('name')
            ->get();
    }

    /**
     * Create new template
     */
    public function create(array $data): DocumentTemplate
    {
        $variables = $this->extractVariables($data['content'] ?? '');

        return DocumentTemplate::create([
            'name' => $data['name'],
            'content' => $data['content'],
            'variables' => $variables,
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'created_by' => Auth::user()->creatorId(),
        ]);
    }

    /**
     * Update template
     */
    public function update(DocumentTemplate $template, array $data): DocumentTemplate
    {
        $variables = $this->extractVariables($data['content'] ?? $template->content);

        $template->update([
            'name' => $data['name'] ?? $template->name,
            'content' => $data['content'] ?? $template->content,
            'variables' => $variables,
            'description' => $data['description'] ?? $template->description,
            'is_active' => $data['is_active'] ?? $template->is_active,
        ]);

        return $template->fresh();
    }

    /**
     * Delete template
     */
    public function delete(DocumentTemplate $template): bool
    {
        return $template->delete();
    }

    /**
     * Extract variables from content
     */
    public function extractVariables(string $content): array
    {
        preg_match_all('/\{([a-z_]+)\}/', $content, $matches);
        return array_unique($matches[0] ?? []);
    }
}
