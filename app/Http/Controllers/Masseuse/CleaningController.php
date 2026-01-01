<?php

namespace App\Http\Controllers\Masseuse;

use App\Http\Controllers\Controller;
use App\Models\CleaningStatus;
use App\Models\DutyPoints;
use Illuminate\Http\Request;

class CleaningController extends Controller
{
    /**
     * Mark a cleaning status as clean.
     */
    public function markClean(Request $request, CleaningStatus $status)
    {
        $user = auth()->user();
        $duty = $status->cleaningDuty;
        
        // Проверяем что это дежурство текущего пользователя
        if ($duty->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => __('Доступ запрещён.')], 403);
        }
        
        // Проверяем что дежурство на сегодня
        if (!$duty->duty_date->isToday()) {
            return response()->json(['success' => false, 'message' => __('Можно отмечать уборку только за сегодняшний день.')], 403);
        }
        
        // Отмечаем как убрано
        $status->update([
            'status' => 'clean',
            'cleaned_by' => $user->id,
            'cleaned_at' => now(),
        ]);
        
        // Проверяем, все ли зоны убраны
        $allClean = $duty->cleaningStatuses()->where('status', '!=', 'clean')->count() === 0;
        
        if ($allClean && $duty->status !== 'completed') {
            // Отмечаем дежурство как выполненное
            $duty->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            
            // Добавляем баллы
            $dutyPoints = DutyPoints::where('branch_id', $duty->branch_id)
                ->where('user_id', $user->id)
                ->first();
            
            if ($dutyPoints) {
                $dutyPoints->update([
                    'points' => $dutyPoints->points + 100,
                    'last_duty_date' => $duty->duty_date,
                ]);
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => __('Статус обновлён'),
            'all_clean' => $allClean,
        ]);
    }
}
