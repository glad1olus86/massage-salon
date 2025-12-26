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

class ImportJobsiOldData extends Command
{
    protected $signature = 'import:jobsi-old 
                            {file : Путь к CSV файлу} 
                            {--user-id=1 : ID пользователя-владельца данных} 
                            {--dry-run : Тестовый запуск без сохранения}';
    protected $description = 'Импорт данных из старого экспорта JOBSI (единый CSV файл)';

    protected $createdBy;
    protected $dryRun = false;
    
    protected $workPlacesMap = [];
    protected $hotelsMap = [];
    protected $roomsMap = [];
    protected $positionsMap = [];
    
    protected $stats = [
        'work_places' => ['created' => 0, 'skipped' => 0],
        'hotels' => ['created' => 0, 'skipped' => 0],
        'rooms' => ['created' => 0, 'skipped' => 0],
        'workers' => ['created' => 0, 'skipped' => 0, 'errors' => []],
        'work_assignments' => ['created' => 0, 'skipped' => 0],
        'room_assignments' => ['created' => 0, 'skipped' => 0],
    ];

    // Маппинг кодов стран (английские названия для перевода)
    protected $countryMap = [
        'UA' => 'Ukraine',
        'BG' => 'Bulgaria',
        'AZ' => 'Azerbaijan',
        'SK' => 'Slovakia',
        'HU' => 'Hungary',
        'CZ' => 'Czech Republic',
        'PL' => 'Poland',
        'RU' => 'Russia',
        'MD' => 'Moldova',
        'RO' => 'Romania',
        'BY' => 'Belarus',
        'GE' => 'Georgia',
        'AM' => 'Armenia',
        'KZ' => 'Kazakhstan',
        'UZ' => 'Uzbekistan',
    ];

    public function handle()
    {
        $filePath = $this->argument('file');
        $this->createdBy = $this->option('user-id');
        $this->dryRun = $this->option('dry-run');
        
        if (!file_exists($filePath)) {
            $this->error("Файл не найден: $filePath");
            return 1;
        }
        
        $this->info('=== Импорт данных из JOBSI Old Export ===');
        $this->info("Файл: $filePath");
        $this->info('Пользователь-владелец: ' . $this->createdBy);
        
        if ($this->dryRun) {
            $this->warn('>>> ТЕСТОВЫЙ РЕЖИМ - данные НЕ будут сохранены <<<');
        }

        DB::beginTransaction();
        
        try {
            $rows = $this->parseCsv($filePath);
            $this->info("Найдено записей: " . count($rows));
            
            // 1. Собираем уникальные рабочие места и отели
            $this->info("\n[1/4] Создание рабочих мест...");
            $this->createWorkPlaces($rows);
            
            $this->info("\n[2/4] Создание отелей...");
            $this->createHotels($rows);
            
            $this->info("\n[3/4] Создание комнат...");
            $this->createRooms($rows);
            
            $this->info("\n[4/4] Импорт работников...");
            $this->importWorkers($rows);
            
            if ($this->dryRun) {
                DB::rollBack();
                $this->warn("\n>>> Тестовый режим - изменения отменены <<<");
            } else {
                DB::commit();
                $this->info("\n>>> Данные успешно сохранены <<<");
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Ошибка: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
        
        $this->printStats();
        return 0;
    }

    protected function createWorkPlaces($rows)
    {
        $workPlaces = [];
        foreach ($rows as $row) {
            $name = trim($row['stredisko'] ?? '');
            if (!empty($name) && $name !== '-') {
                $workPlaces[$name] = true;
            }
        }
        
        foreach (array_keys($workPlaces) as $name) {
            $existing = WorkPlace::where('name', $name)
                ->where('created_by', $this->createdBy)
                ->first();
                
            if ($existing) {
                $this->workPlacesMap[$name] = $existing->id;
                $this->stats['work_places']['skipped']++;
                continue;
            }
            
            if (!$this->dryRun) {
                $wp = WorkPlace::create([
                    'name' => $name,
                    'created_by' => $this->createdBy,
                ]);
                $this->workPlacesMap[$name] = $wp->id;
                
                // Создаём дефолтную должность
                $position = Position::create([
                    'work_place_id' => $wp->id,
                    'name' => 'Worker',
                    'created_by' => $this->createdBy,
                ]);
                $this->positionsMap[$name] = $position->id;
            }
            $this->stats['work_places']['created']++;
        }
        
        $this->line("  Создано: {$this->stats['work_places']['created']}, Пропущено: {$this->stats['work_places']['skipped']}");
    }

    protected function createHotels($rows)
    {
        $hotels = [];
        foreach ($rows as $row) {
            $name = trim($row['ubytovna'] ?? '');
            if (!empty($name)) {
                $hotels[$name] = true;
            }
        }
        
        foreach (array_keys($hotels) as $name) {
            $existing = Hotel::where('name', $name)
                ->where('created_by', $this->createdBy)
                ->first();
                
            if ($existing) {
                $this->hotelsMap[$name] = $existing->id;
                $this->stats['hotels']['skipped']++;
                continue;
            }
            
            if (!$this->dryRun) {
                $hotel = Hotel::create([
                    'name' => $name,
                    'address' => $name,
                    'created_by' => $this->createdBy,
                ]);
                $this->hotelsMap[$name] = $hotel->id;
            }
            $this->stats['hotels']['created']++;
        }
        
        $this->line("  Создано: {$this->stats['hotels']['created']}, Пропущено: {$this->stats['hotels']['skipped']}");
    }

    protected function createRooms($rows)
    {
        $rooms = [];
        foreach ($rows as $row) {
            $hotelName = trim($row['ubytovna'] ?? '');
            $roomNumber = trim($row['roomName'] ?? '');
            if (!empty($hotelName) && !empty($roomNumber)) {
                $key = $hotelName . '|' . $roomNumber;
                if (!isset($rooms[$key])) {
                    $rooms[$key] = ['hotel' => $hotelName, 'room' => $roomNumber, 'count' => 0];
                }
                $rooms[$key]['count']++;
            }
        }
        
        foreach ($rooms as $key => $data) {
            $hotelId = $this->hotelsMap[$data['hotel']] ?? null;
            if (!$hotelId) continue;
            
            $existing = Room::where('hotel_id', $hotelId)
                ->where('room_number', $data['room'])
                ->first();
                
            if ($existing) {
                $this->roomsMap[$key] = $existing->id;
                $this->stats['rooms']['skipped']++;
                continue;
            }
            
            if (!$this->dryRun) {
                $room = Room::create([
                    'hotel_id' => $hotelId,
                    'room_number' => $data['room'],
                    'capacity' => max($data['count'], 2),
                    'monthly_price' => 0,
                    'payment_type' => 'agency',
                    'created_by' => $this->createdBy,
                ]);
                $this->roomsMap[$key] = $room->id;
            }
            $this->stats['rooms']['created']++;
        }
        
        $this->line("  Создано: {$this->stats['rooms']['created']}, Пропущено: {$this->stats['rooms']['skipped']}");
    }

    protected function importWorkers($rows)
    {
        $today = now()->format('Y-m-d');
        
        foreach ($rows as $index => $row) {
            $fullName = trim($row['name'] ?? '');
            if (empty($fullName)) continue;
            
            try {
                $nameParts = $this->parseName($fullName);
                
                // Проверяем дубликат
                $existing = Worker::where('first_name', $nameParts['first_name'])
                    ->where('last_name', $nameParts['last_name'])
                    ->where('created_by', $this->createdBy)
                    ->first();
                    
                if ($existing) {
                    $this->stats['workers']['skipped']++;
                    continue;
                }
                
                $gender = $this->parseGender(trim($row['gender'] ?? ''));
                $country = $this->countryMap[trim($row['country'] ?? 'UA')] ?? 'Ukraine';
                $registrationDate = $this->parseDate(trim($row['registr'] ?? '')) ?: $today;
                
                if (!$this->dryRun) {
                    $worker = Worker::create([
                        'first_name' => $nameParts['first_name'],
                        'last_name' => $nameParts['last_name'],
                        'dob' => '2000-01-01',
                        'gender' => $gender,
                        'nationality' => $country,
                        'registration_date' => $registrationDate,
                        'created_by' => $this->createdBy,
                    ]);
                    
                    // Назначаем на рабочее место
                    $workPlaceName = trim($row['stredisko'] ?? '');
                    if (!empty($workPlaceName) && $workPlaceName !== '-') {
                        $this->assignToWorkPlace($worker, $workPlaceName);
                    }
                    
                    // Заселяем в комнату
                    $hotelName = trim($row['ubytovna'] ?? '');
                    $roomNumber = trim($row['roomName'] ?? '');
                    if (!empty($hotelName) && !empty($roomNumber)) {
                        $this->assignToRoom($worker, $hotelName, $roomNumber);
                    }
                }
                
                $this->stats['workers']['created']++;
                
            } catch (\Exception $e) {
                $this->stats['workers']['errors'][] = [
                    'line' => $index + 2,
                    'name' => $fullName,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $this->line("  Создано: {$this->stats['workers']['created']}, Пропущено: {$this->stats['workers']['skipped']}, Ошибок: " . count($this->stats['workers']['errors']));
    }

    protected function assignToWorkPlace($worker, $workPlaceName)
    {
        $workPlaceId = $this->workPlacesMap[$workPlaceName] ?? null;
        $positionId = $this->positionsMap[$workPlaceName] ?? null;
        
        if (!$workPlaceId) return;
        
        WorkAssignment::create([
            'worker_id' => $worker->id,
            'work_place_id' => $workPlaceId,
            'position_id' => $positionId,
            'started_at' => now(),
            'created_by' => $this->createdBy,
        ]);
        
        $this->stats['work_assignments']['created']++;
    }

    protected function assignToRoom($worker, $hotelName, $roomNumber)
    {
        $roomKey = $hotelName . '|' . $roomNumber;
        $roomId = $this->roomsMap[$roomKey] ?? null;
        $hotelId = $this->hotelsMap[$hotelName] ?? null;
        
        if (!$roomId || !$hotelId) return;
        
        RoomAssignment::create([
            'worker_id' => $worker->id,
            'room_id' => $roomId,
            'hotel_id' => $hotelId,
            'check_in_date' => now(),
            'created_by' => $this->createdBy,
        ]);
        
        $this->stats['room_assignments']['created']++;
    }

    protected function parseName($fullName)
    {
        $fullName = preg_replace('/\s+/', ' ', trim($fullName));
        $parts = explode(' ', $fullName);
        
        if (count($parts) >= 2) {
            $lastName = array_shift($parts);
            $firstName = implode(' ', $parts);
        } else {
            $firstName = $fullName;
            $lastName = '';
        }
        
        return ['first_name' => $firstName, 'last_name' => $lastName];
    }

    protected function parseGender($gender)
    {
        $gender = mb_strtolower($gender);
        if (str_contains($gender, 'muž') || str_contains($gender, 'male')) return 'male';
        if (str_contains($gender, 'žen') || str_contains($gender, 'female')) return 'female';
        return 'male';
    }

    protected function parseDate($date)
    {
        if (empty($date)) return null;
        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function parseCsv($filePath)
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        
        $headers = fgetcsv($handle);
        $headers = array_map(fn($h) => trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h)), $headers);
        
        while (($data = fgetcsv($handle)) !== false) {
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = $data[$i] ?? '';
            }
            $rows[] = $row;
        }
        
        fclose($handle);
        return $rows;
    }

    protected function printStats()
    {
        $this->info("\n=== СТАТИСТИКА ИМПОРТА ===");
        $this->table(
            ['Сущность', 'Создано', 'Пропущено'],
            [
                ['Рабочие места', $this->stats['work_places']['created'], $this->stats['work_places']['skipped']],
                ['Отели', $this->stats['hotels']['created'], $this->stats['hotels']['skipped']],
                ['Комнаты', $this->stats['rooms']['created'], $this->stats['rooms']['skipped']],
                ['Работники', $this->stats['workers']['created'], $this->stats['workers']['skipped']],
                ['Назначения на работу', $this->stats['work_assignments']['created'], $this->stats['work_assignments']['skipped']],
                ['Заселения', $this->stats['room_assignments']['created'], $this->stats['room_assignments']['skipped']],
            ]
        );
        
        if (!empty($this->stats['workers']['errors'])) {
            $this->error("\n=== ОШИБКИ ===");
            foreach ($this->stats['workers']['errors'] as $e) {
                $this->line("  Строка {$e['line']}: {$e['name']} - {$e['error']}");
            }
        }
    }
}
