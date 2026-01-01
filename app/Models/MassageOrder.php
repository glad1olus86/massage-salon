<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MassageOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'client_name',
        'employee_id',
        'branch_id',
        'service_id',
        'service_name',
        'order_date',
        'order_time',
        'duration',
        'amount',
        'tip',
        'payment_method',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'amount' => 'decimal:2',
        'tip' => 'decimal:2',
    ];

    // Клиент
    public function client()
    {
        return $this->belongsTo(MassageClient::class, 'client_id');
    }

    // Массажистка (user)
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    // Филиал
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Услуга
    public function service()
    {
        return $this->belongsTo(MassageService::class, 'service_id');
    }

    // Кто создал
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Получить имя клиента (из связи или из поля)
    public function getClientDisplayNameAttribute()
    {
        return $this->client?->full_name ?? $this->client_name ?? 'N/A';
    }

    // Получить имя услуги
    public function getServiceDisplayNameAttribute()
    {
        return $this->service?->name ?? $this->service_name ?? 'N/A';
    }

    // Получить имя сотрудника
    public function getEmployeeDisplayNameAttribute()
    {
        return $this->employee?->name ?? 'N/A';
    }

    // Форматированная сумма
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', ' ') . ' CZK';
    }

    // Форматированная дата
    public function getFormattedDateAttribute()
    {
        return $this->order_date?->format('d.m.Y');
    }

    // Статусы
    public static function getStatuses()
    {
        return [
            'pending' => __('В ожидании'),
            'confirmed' => __('Подтверждён'),
            'completed' => __('Завершён'),
            'cancelled' => __('Отменён'),
        ];
    }

    // Методы оплаты
    public static function getPaymentMethods()
    {
        return [
            'cash' => __('Наличные'),
            'card' => __('Карта'),
            'transfer' => __('Перевод'),
        ];
    }
}
