<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\DocumentTemplate;
use App\Models\Worker;

class DocumentAuditService
{
    /**
     * Log template created event
     */
    public function logTemplateCreated(DocumentTemplate $template): void
    {
        AuditLog::logEvent(
            'document_template.created',
            __('Создан шаблон документа: :name', ['name' => $template->name]),
            $template,
            null,
            $template->toArray()
        );
    }

    /**
     * Log template updated event
     */
    public function logTemplateUpdated(DocumentTemplate $template, array $oldValues): void
    {
        AuditLog::logEvent(
            'document_template.updated',
            __('Обновлен шаблон документа: :name', ['name' => $template->name]),
            $template,
            $oldValues,
            $template->toArray()
        );
    }

    /**
     * Log template deleted event
     */
    public function logTemplateDeleted(DocumentTemplate $template): void
    {
        AuditLog::logEvent(
            'document_template.deleted',
            __('Удален шаблон документа: :name', ['name' => $template->name]),
            $template,
            $template->toArray(),
            null
        );
    }

    /**
     * Log document generated event
     */
    public function logDocumentGenerated(DocumentTemplate $template, Worker $worker, string $format): void
    {
        AuditLog::logEvent(
            'document.generated',
            __('Сгенерирован документ ":template" для работника :worker в формате :format', [
                'template' => $template->name,
                'worker' => $worker->first_name . ' ' . $worker->last_name,
                'format' => strtoupper($format),
            ]),
            $worker,
            null,
            [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'worker_id' => $worker->id,
                'format' => $format,
            ]
        );
    }
}
