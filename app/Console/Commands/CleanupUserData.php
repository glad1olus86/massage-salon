<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\WorkPlace;
use App\Models\Position;
use App\Models\Worker;
use App\Models\RoomAssignment;
use App\Models\WorkAssignment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupUserData extends Command
{
    protected $signature = 'cleanup:user-data 
                            {user-id : ID пользователя для очистки данных} 
                            {--dry-run : Тестовый запуск без удаления}
                            {--force : Пропустить подтверждение}';
    protected $description = 'Удаление всех работников, отелей, комнат и рабочих мест для указанного пользователя';

    public function handle()
    {
        $userId = $this->argument('user-id');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        
        $this->info("=== Очистка данных для пользователя ID: $userId ===");
        
        // Подсчёт данных
        $counts = [
            'workers' => Worker::where('created_by', $userId)->count(),
            'work_assignments' => WorkAssignment::where('created_by', $userId)->count(),
            'room_assignments' => RoomAssignment::where('created_by', $userId)->count(),
            'rooms' => Room::where('created_by', $userId)->count(),
            'hotels' => Hotel::where('created_by', $userId)->count(),
            'work_places' => WorkPlace::where('created_by', $userId)->count(),
            'positions' => Position::where('created_by', $userId)->count(),
        ];
        
        $this->table(
            ['Сущность', 'Количество'],
            [
                ['Работники', $counts['workers']],
                ['Назначения на работу', $counts['work_assignments']],
                ['Заселения в комнаты', $counts['room_assignments']],
                ['Комнаты', $counts['rooms']],
                ['Отели', $counts['hotels']],
                ['Рабочие места', $counts['work_places']],
                ['Должности', $counts['positions']],
            ]
        );
        
        $total = array_sum($counts);
        
        if ($total === 0) {
            $this->info('Нет данных для удаления.');
            return 0;
        }
        
        if ($dryRun) {
            $this->warn(">>> ТЕСТОВЫЙ РЕЖИМ - данные НЕ будут удалены <<<");
            $this->info("Будет удалено $total записей.");
            return 0;
        }
        
        if (!$force && !$this->confirm("Вы уверены что хотите удалить $total записей? Это действие необратимо!")) {
            $this->info('Операция отменена.');
            return 0;
        }
        
        DB::beginTransaction();
        
        try {
            $this->info("\nУдаление данных...");
            
            // Порядок важен из-за foreign keys
            $deleted = [];
            
            $this->line("  Удаление назначений на работу...");
            $deleted['work_assignments'] = WorkAssignment::where('created_by', $userId)->delete();
            
            $this->line("  Удаление заселений...");
            $deleted['room_assignments'] = RoomAssignment::where('created_by', $userId)->delete();
            
            $this->line("  Удаление работников...");
            $deleted['workers'] = Worker::where('created_by', $userId)->delete();
            
            $this->line("  Удаление должностей...");
            $deleted['positions'] = Position::where('created_by', $userId)->delete();
            
            $this->line("  Удаление комнат...");
            $deleted['rooms'] = Room::where('created_by', $userId)->delete();
            
            $this->line("  Удаление отелей...");
            $deleted['hotels'] = Hotel::where('created_by', $userId)->delete();
            
            $this->line("  Удаление рабочих мест...");
            $deleted['work_places'] = WorkPlace::where('created_by', $userId)->delete();
            
            DB::commit();
            
            $this->info("\n=== РЕЗУЛЬТАТ ===");
            $this->table(
                ['Сущность', 'Удалено'],
                [
                    ['Работники', $deleted['workers']],
                    ['Назначения на работу', $deleted['work_assignments']],
                    ['Заселения', $deleted['room_assignments']],
                    ['Комнаты', $deleted['rooms']],
                    ['Отели', $deleted['hotels']],
                    ['Рабочие места', $deleted['work_places']],
                    ['Должности', $deleted['positions']],
                ]
            );
            
            $this->info("Всего удалено: " . array_sum($deleted) . " записей");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Ошибка: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
