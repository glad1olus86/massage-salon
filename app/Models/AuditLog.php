<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'description',
        'subject_type',
        'subject_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_by',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */

    // Пользователь, который совершил действие
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Пользователь-создатель (для multi-tenancy)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Polymorphic relationship для объекта действия
    public function subject()
    {
        return $this->morphTo();
    }

    /**
     * Scopes
     */

    // Фильтр по диапазону дат
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
    }

    // Фильтр по пользователю
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Фильтр по типу события
    public function scopeByEventType($query, $type)
    {
        if (is_array($type)) {
            return $query->whereIn('event_type', $type);
        }
        return $query->where('event_type', $type);
    }

    // Фильтр по типу объекта
    public function scopeBySubjectType($query, $type)
    {
        return $query->where('subject_type', $type);
    }

    // События за сегодня
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    // События за текущий месяц
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year);
    }

    // События за последние N дней
    public function scopeLastDays($query, $days = 7)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    // Для текущего создателя (multi-tenancy)
    public function scopeForCurrentUser($query)
    {
        /** @var \App\Models\User $user */
        $user = \Auth::user();
        return $query->where('created_by', $user->creatorId());
    }

    /**
     * Accessors
     */

    // Получить цвет для типа события
    public function getEventColorAttribute()
    {
        $colors = [
            // Работники
            'worker.created' => '#28a745',      // Зеленый
            'worker.updated' => '#17a2b8',      // Голубой
            'worker.deleted' => '#6c757d',      // Серый

            // Проживание
            'worker.checked_in' => '#007bff',   // Синий
            'worker.checked_out' => '#fd7e14',  // Оранжевый

            // Трудоустройство
            'worker.hired' => '#6f42c1',        // Фиолетовый
            'worker.dismissed' => '#dc3545',    // Красный

            // Комнаты
            'room.created' => '#20c997',        // Бирюзовый
            'room.updated' => '#17a2b8',        // Голубой
            'room.deleted' => '#6c757d',        // Серый

            // Рабочие места
            'work_place.created' => '#20c997',  // Бирюзовый
            'work_place.updated' => '#17a2b8',  // Голубой
            'work_place.deleted' => '#6c757d',  // Серый

            // Отели
            'hotel.created' => '#28a745',       // Зеленый
            'hotel.updated' => '#17a2b8',       // Голубой
            'hotel.deleted' => '#6c757d',       // Серый

            // Касса
            'cashbox.deposit' => '#28a745',         // Зеленый - внесение
            'cashbox.distribution' => '#007bff',    // Синий - выдача
            'cashbox.refund' => '#fd7e14',          // Оранжевый - возврат
            'cashbox.self_salary' => '#6f42c1',     // Фиолетовый - ЗП себе
            'cashbox.status_change' => '#17a2b8',   // Голубой - смена статуса

            // Документы
            'document.generated' => '#e83e8c',      // Розовый - генерация документа
            'document_template.created' => '#20c997', // Бирюзовый - создание шаблона
            'document_template.updated' => '#17a2b8', // Голубой - обновление шаблона
            'document_template.deleted' => '#6c757d', // Серый - удаление шаблона
        ];

        return $colors[$this->event_type] ?? '#6c757d';
    }

    // Получить иконку для типа события
    public function getEventIconAttribute()
    {
        $icons = [
            // Работники
            'worker.created' => 'ti-user-plus',
            'worker.updated' => 'ti-user-edit',
            'worker.deleted' => 'ti-user-x',

            // Проживание
            'worker.checked_in' => 'ti-door-enter',
            'worker.checked_out' => 'ti-door-exit',

            // Трудоустройство
            'worker.hired' => 'ti-briefcase-plus',
            'worker.dismissed' => 'ti-briefcase-off',

            // Комнаты
            'room.created' => 'ti-door-plus',
            'room.updated' => 'ti-door-edit',
            'room.deleted' => 'ti-door-x',

            // Рабочие места
            'work_place.created' => 'ti-building-plus',
            'work_place.updated' => 'ti-building-edit',
            'work_place.deleted' => 'ti-building-x',

            // Отели
            'hotel.created' => 'ti-building-skyscraper',
            'hotel.updated' => 'ti-building-edit',
            'hotel.deleted' => 'ti-building-x',

            // Касса
            'cashbox.deposit' => 'ti-cash',
            'cashbox.distribution' => 'ti-send',
            'cashbox.refund' => 'ti-arrow-back',
            'cashbox.self_salary' => 'ti-wallet',
            'cashbox.status_change' => 'ti-refresh',

            // Документы
            'document.generated' => 'ti-file-text',
            'document_template.created' => 'ti-file-plus',
            'document_template.updated' => 'ti-file-pencil',
            'document_template.deleted' => 'ti-file-x',
        ];

        return $icons[$this->event_type] ?? 'ti-info-circle';
    }

    // Форматированное описание события
    public function getFormattedDescriptionAttribute()
    {
        return $this->description;
    }

    // Переведённое описание события (переводит на текущий язык пользователя)
    public function getTranslatedDescriptionAttribute()
    {
        $description = $this->description;
        
        // Словарь замен: все варианты на всех языках -> ключ перевода
        $translations = [
            // Заселение
            'Заселён:' => __('Checked in') . ':',
            'Заселен:' => __('Checked in') . ':',
            'Ubytován:' => __('Checked in') . ':',
            'Заселено:' => __('Checked in') . ':',
            'Checked in:' => __('Checked in') . ':',
            
            // Выселение
            'Выселен:' => __('Checked out') . ':',
            'Odhlášen:' => __('Checked out') . ':',
            'Виселено:' => __('Checked out') . ':',
            'Checked out:' => __('Checked out') . ':',
            
            // Создание работника
            'Создан работник:' => __('Worker created') . ':',
            'Worker created:' => __('Worker created') . ':',
            'Pracovník vytvořen:' => __('Worker created') . ':',
            'Створено працівника:' => __('Worker created') . ':',
            
            // Обновление работника
            'Обновлены данные работника:' => __('Worker updated') . ':',
            'Обновлён работник:' => __('Worker updated') . ':',
            'Worker updated:' => __('Worker updated') . ':',
            'Pracovník aktualizován:' => __('Worker updated') . ':',
            'Оновлено працівника:' => __('Worker updated') . ':',
            
            // Удаление работника
            'Удален работник:' => __('Worker deleted') . ':',
            'Удалён работник:' => __('Worker deleted') . ':',
            'Worker deleted:' => __('Worker deleted') . ':',
            'Pracovník smazán:' => __('Worker deleted') . ':',
            'Видалено працівника:' => __('Worker deleted') . ':',
            
            // Трудоустройство
            'Устроен на работу:' => __('Hired') . ':',
            'Hired:' => __('Hired') . ':',
            'Zaměstnán:' => __('Hired') . ':',
            'Працевлаштовано:' => __('Hired') . ':',
            
            // Увольнение
            'Уволен:' => __('Dismissed') . ':',
            'Dismissed:' => __('Dismissed') . ':',
            'Propuštěn:' => __('Dismissed') . ':',
            'Звільнено:' => __('Dismissed') . ':',
            
            // Комнаты
            'Создана комната:' => __('Room created') . ':',
            'Room created:' => __('Room created') . ':',
            'Pokoj vytvořen:' => __('Room created') . ':',
            'Створено кімнату:' => __('Room created') . ':',
            
            'Обновлена комната:' => __('Room updated') . ':',
            'Room updated:' => __('Room updated') . ':',
            'Pokoj aktualizován:' => __('Room updated') . ':',
            'Оновлено кімнату:' => __('Room updated') . ':',
            
            'Удалена комната:' => __('Room deleted') . ':',
            'Room deleted:' => __('Room deleted') . ':',
            'Pokoj smazán:' => __('Room deleted') . ':',
            'Видалено кімнату:' => __('Room deleted') . ':',
            
            // Рабочие места
            'Создано рабочее место:' => __('Work place created') . ':',
            'Work place created:' => __('Work place created') . ':',
            'Pracoviště vytvořeno:' => __('Work place created') . ':',
            'Створено робоче місце:' => __('Work place created') . ':',
            
            'Обновлено рабочее место:' => __('Work place updated') . ':',
            'Work place updated:' => __('Work place updated') . ':',
            'Pracoviště aktualizováno:' => __('Work place updated') . ':',
            'Оновлено робоче місце:' => __('Work place updated') . ':',
            
            'Удалено рабочее место:' => __('Work place deleted') . ':',
            'Work place deleted:' => __('Work place deleted') . ':',
            'Pracoviště smazáno:' => __('Work place deleted') . ':',
            'Видалено робоче місце:' => __('Work place deleted') . ':',
            
            // Отели
            'Создан отель:' => __('Hotel created') . ':',
            'Hotel created:' => __('Hotel created') . ':',
            'Hotel vytvořen:' => __('Hotel created') . ':',
            'Створено готель:' => __('Hotel created') . ':',
            
            'Обновлен отель:' => __('Hotel updated') . ':',
            'Обновлён отель:' => __('Hotel updated') . ':',
            'Hotel updated:' => __('Hotel updated') . ':',
            'Hotel aktualizován:' => __('Hotel updated') . ':',
            'Оновлено готель:' => __('Hotel updated') . ':',
            
            'Удален отель:' => __('Hotel deleted') . ':',
            'Удалён отель:' => __('Hotel deleted') . ':',
            'Hotel deleted:' => __('Hotel deleted') . ':',
            'Hotel smazán:' => __('Hotel deleted') . ':',
            'Видалено готель:' => __('Hotel deleted') . ':',
            
            // Дополнительные слова в тексте
            '→ Отель' => '→ ' . __('Hotel'),
            '← Отель' => '← ' . __('Hotel'),
            '→ Hotel' => '→ ' . __('Hotel'),
            '← Hotel' => '← ' . __('Hotel'),
            '→ Готель' => '→ ' . __('Hotel'),
            '← Готель' => '← ' . __('Hotel'),
            ', Комната ' => ', ' . __('Room') . ' ',
            ', Room ' => ', ' . __('Room') . ' ',
            ', Кімната ' => ', ' . __('Room') . ' ',
            ', Pokoj ' => ', ' . __('Room') . ' ',
            ' в отеле ' => ' ' . __('in hotel') . ' ',
            ' in hotel ' => ' ' . __('in hotel') . ' ',
            ' v hotelu ' => ' ' . __('in hotel') . ' ',
            ' в готелі ' => ' ' . __('in hotel') . ' ',
            
            // Касса - внесение
            ' внёс ' => ' ' . __('deposited') . ' ',
            ' внес ' => ' ' . __('deposited') . ' ',
            ' в кассу' => ' ' . __('to cashbox'),
            
            // Касса - выдача
            ' выдал ' => ' ' . __('issued') . ' ',
            ' пользователю ' => ' ' . __('to user') . ' ',
            
            // Касса - возврат
            ' вернул ' => ' ' . __('returned') . ' ',
            
            // Касса - зарплата себе
            ' взял себе зарплату ' => ' ' . __('took salary') . ' ',
            
            // Касса - смена статуса
            ' изменил статус транзакции ' => ' ' . __('changed transaction status') . ' ',
            ' с "' => ' ' . __('from') . ' "',
            '" на "' => '" ' . __('to') . ' "',
            'Ожидает' => __('Pending'),
            'В работе' => __('In progress'),
            'Выполнено' => __('Completed'),
            'Просрочено' => __('Overdue'),
            
            // Касса - задача
            '(задача: ' => '(' . __('task') . ': ',
            '(причина: ' => '(' . __('reason') . ': ',
            
            // Документы - генерация
            'Сгенерирован документ "' => __('Document generated') . ' "',
            '" для работника ' => '" ' . __('for worker') . ' ',
            ' в формате ' => ' ' . __('in format') . ' ',
            
            // Документы - шаблоны
            'Создан шаблон документа: ' => __('Document template created') . ': ',
            'Обновлен шаблон документа: ' => __('Document template updated') . ': ',
            'Удален шаблон документа: ' => __('Document template deleted') . ': ',
        ];
        
        foreach ($translations as $search => $replace) {
            $description = str_replace($search, $replace, $description);
        }
        
        return $description;
    }

    // Получить имя пользователя
    public function getUserNameAttribute()
    {
        return $this->user ? $this->user->name : __('Система');
    }

    /**
     * Helper методы
     */

    // Создать лог события
    public static function logEvent($eventType, $description, $subject = null, $oldValues = null, $newValues = null)
    {
        return self::create([
            'user_id' => Auth::id(),
            'event_type' => $eventType,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->id : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_by' => Auth::user() ? Auth::user()->creatorId() : 1,
        ]);
    }
}
