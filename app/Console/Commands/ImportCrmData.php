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

class ImportCrmData extends Command
{
    protected $signature = 'import:crm-data {--user-id=1 : ID пользователя-владельца данных} {--dry-run : Тестовый запуск без сохранения}';
    protected $description = 'Импорт данных из старой CRM (папка importquestion)';

    protected $createdBy;
    protected $dryRun = false;
    
    // Маппинг для связей
    protected $workPlacesMap = [];  // name => id
    protected $hotelsMap = [];       // name => id
    protected $roomsMap = [];        // "hotel_name|room_number" => id
    protected $positionsMap = [];    // "workplace_name|position_name" => id
    
    // Статистика и ошибки
    protected $stats = [
        'work_places' => ['created' => 0, 'skipped' => 0],
        'hotels' => ['created' => 0, 'skipped' => 0],
        'rooms' => ['created' => 0, 'skipped' => 0],
        'workers' => ['created' => 0, 'skipped' => 0, 'errors' => []],
        'work_assignments' => ['created' => 0, 'skipped' => 0],
        'room_assignments' => ['created' => 0, 'skipped' => 0],
    ];

    public function handle()
    {
        $this->createdBy = $this->option('user-id');
        $this->dryRun = $this->option('dry-run');
        
        $this->info('=== Импорт данных из CRM ===');
        $this->info('Пользователь-владелец: ' . $this->createdBy);
        
        if ($this->dryRun) {
            $this->warn('>>> ТЕСТОВЫЙ РЕЖИМ - данные НЕ будут сохранены <<<');
        }
        
        $basePath = base_path('importquestion');
        
        if (!is_dir($basePath)) {
            $this->error('Папка importquestion не найдена!');
            return 1;
        }

        DB::beginTransaction();
        
        try {
            // 1. Импорт рабочих мест
            $this->info("\n[1/4] Импорт рабочих мест...");
            $this->importWorkPlaces($basePath . '/Střediska - Jobsi export.csv');
            
            // 2. Импорт отелей
            $this->info("\n[2/4] Импорт отелей...");
            $this->importHotels($basePath . '/Ubytovny - Jobsi export.csv');
            
            // 3. Подготовка комнат из данных работников
            $this->info("\n[3/4] Создание комнат из данных работников...");
            $this->prepareRoomsFromWorkers($basePath . '/Zaměstnanci - Jobsi export.csv');
            
            // 4. Импорт работников
            $this->info("\n[4/4] Импорт работников...");
            $this->importWorkers($basePath . '/Zaměstnanci - Jobsi export.csv');
            
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
        
        // Вывод статистики
        $this->printStats();
        
        return 0;
    }


    /**
     * Импорт рабочих мест
     */
    protected function importWorkPlaces($filePath)
    {
        if (!file_exists($filePath)) {
            $this->warn("Файл не найден: $filePath");
            return;
        }
        
        $rows = $this->parseCsv($filePath);
        
        foreach ($rows as $row) {
            $name = trim($row['name'] ?? '');
            if (empty($name) || $name === '-') continue;
            
            // Проверяем существование
            $existing = WorkPlace::where('name', $name)
                ->where('created_by', $this->createdBy)
                ->first();
                
            if ($existing) {
                $this->workPlacesMap[$name] = $existing->id;
                $this->stats['work_places']['skipped']++;
                continue;
            }
            
            $workPlace = new WorkPlace();
            $workPlace->name = $name;
            $workPlace->address = trim($row['adress'] ?? '') ?: null;
            $workPlace->phone = trim($row['mobile'] ?? '') ?: null;
            $workPlace->email = trim($row['email'] ?? '') ?: null;
            $workPlace->created_by = $this->createdBy;
            
            if (!$this->dryRun) {
                $workPlace->save();
                $this->workPlacesMap[$name] = $workPlace->id;
            } else {
                // В тестовом режиме используем временный ID
                $this->workPlacesMap[$name] = 'temp_' . count($this->workPlacesMap);
            }
            
            $this->stats['work_places']['created']++;
        }
        
        // Создаём дефолтную должность для каждого рабочего места
        foreach ($this->workPlacesMap as $wpName => $wpId) {
            if (str_starts_with($wpId, 'temp_')) continue;
            
            $positionName = 'Сотрудник';
            $existing = Position::where('work_place_id', $wpId)
                ->where('name', $positionName)
                ->first();
                
            if (!$existing && !$this->dryRun) {
                $position = new Position();
                $position->work_place_id = $wpId;
                $position->name = $positionName;
                $position->created_by = $this->createdBy;
                $position->save();
                $this->positionsMap[$wpName . '|' . $positionName] = $position->id;
            } elseif ($existing) {
                $this->positionsMap[$wpName . '|' . $positionName] = $existing->id;
            }
        }
        
        $this->line("  Создано: {$this->stats['work_places']['created']}, Пропущено: {$this->stats['work_places']['skipped']}");
    }

    /**
     * Импорт отелей
     */
    protected function importHotels($filePath)
    {
        if (!file_exists($filePath)) {
            $this->warn("Файл не найден: $filePath");
            return;
        }
        
        $rows = $this->parseCsv($filePath);
        
        foreach ($rows as $row) {
            $name = trim($row['name'] ?? '');
            if (empty($name)) continue;
            
            // Проверяем существование
            $existing = Hotel::where('name', $name)
                ->where('created_by', $this->createdBy)
                ->first();
                
            if ($existing) {
                $this->hotelsMap[$name] = $existing->id;
                $this->stats['hotels']['skipped']++;
                continue;
            }
            
            $hotel = new Hotel();
            $hotel->name = $name;
            $hotel->address = trim($row['adress'] ?? '') ?: $name;
            $hotel->phone = trim($row['mobile'] ?? '') ?: null;
            $hotel->email = trim($row['email'] ?? '') ?: null;
            $hotel->created_by = $this->createdBy;
            
            if (!$this->dryRun) {
                $hotel->save();
                $this->hotelsMap[$name] = $hotel->id;
            } else {
                $this->hotelsMap[$name] = 'temp_' . count($this->hotelsMap);
            }
            
            $this->stats['hotels']['created']++;
        }
        
        $this->line("  Создано: {$this->stats['hotels']['created']}, Пропущено: {$this->stats['hotels']['skipped']}");
    }

    /**
     * Подготовка комнат из данных работников
     */
    protected function prepareRoomsFromWorkers($filePath)
    {
        if (!file_exists($filePath)) {
            $this->warn("Файл не найден: $filePath");
            return;
        }
        
        $rows = $this->parseCsv($filePath);
        $roomsToCreate = [];
        
        // Собираем уникальные комнаты
        foreach ($rows as $row) {
            $hotelName = trim($row['ubytovna'] ?? '');
            $roomNumber = trim($row['roomName'] ?? '');
            
            if (empty($hotelName) || empty($roomNumber)) continue;
            
            $key = $hotelName . '|' . $roomNumber;
            if (!isset($roomsToCreate[$key])) {
                $roomsToCreate[$key] = [
                    'hotel' => $hotelName,
                    'room' => $roomNumber,
                    'count' => 0
                ];
            }
            $roomsToCreate[$key]['count']++;
        }
        
        // Создаём комнаты
        foreach ($roomsToCreate as $key => $data) {
            $hotelId = $this->hotelsMap[$data['hotel']] ?? null;
            
            if (!$hotelId || str_starts_with($hotelId, 'temp_')) {
                // Отель не найден - создаём его
                if (!isset($this->hotelsMap[$data['hotel']])) {
                    $hotel = new Hotel();
                    $hotel->name = $data['hotel'];
                    $hotel->address = $data['hotel'];
                    $hotel->created_by = $this->createdBy;
                    
                    if (!$this->dryRun) {
                        $hotel->save();
                        $this->hotelsMap[$data['hotel']] = $hotel->id;
                        $hotelId = $hotel->id;
                        $this->stats['hotels']['created']++;
                    } else {
                        $this->hotelsMap[$data['hotel']] = 'temp_' . count($this->hotelsMap);
                        continue;
                    }
                }
            }
            
            if (!$hotelId || str_starts_with($hotelId, 'temp_')) continue;
            
            // Проверяем существование комнаты
            $existing = Room::where('hotel_id', $hotelId)
                ->where('room_number', $data['room'])
                ->first();
                
            if ($existing) {
                $this->roomsMap[$key] = $existing->id;
                $this->stats['rooms']['skipped']++;
                continue;
            }
            
            $room = new Room();
            $room->hotel_id = $hotelId;
            $room->room_number = $data['room'];
            $room->capacity = max($data['count'], 2); // Минимум 2 места
            $room->monthly_price = 0;
            $room->payment_type = 'agency';
            $room->created_by = $this->createdBy;
            
            if (!$this->dryRun) {
                $room->save();
                $this->roomsMap[$key] = $room->id;
            } else {
                $this->roomsMap[$key] = 'temp_' . count($this->roomsMap);
            }
            
            $this->stats['rooms']['created']++;
        }
        
        $this->line("  Создано комнат: {$this->stats['rooms']['created']}, Пропущено: {$this->stats['rooms']['skipped']}");
    }


    /**
     * Импорт работников
     */
    protected function importWorkers($filePath)
    {
        if (!file_exists($filePath)) {
            $this->warn("Файл не найден: $filePath");
            return;
        }
        
        $rows = $this->parseCsv($filePath);
        $defaultDob = '2000-01-01';
        $today = now()->format('Y-m-d');
        
        foreach ($rows as $index => $row) {
            $fullName = trim($row['name'] ?? '');
            if (empty($fullName)) continue;
            
            $lineNum = $index + 2; // +2 потому что индекс с 0 и есть заголовок
            
            try {
                // Парсим имя (Фамилия Имя)
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
                
                // Определяем пол
                $genderRaw = trim($row['gender'] ?? '');
                $gender = $this->parseGender($genderRaw);
                
                // Создаём работника
                $worker = new Worker();
                $worker->first_name = $nameParts['first_name'];
                $worker->last_name = $nameParts['last_name'];
                $worker->dob = $defaultDob;
                $worker->gender = $gender;
                $worker->nationality = 'Украина'; // По умолчанию
                $worker->registration_date = $today;
                $worker->created_by = $this->createdBy;
                
                if (!$this->dryRun) {
                    $worker->save();
                }
                
                $this->stats['workers']['created']++;
                
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
                
            } catch (\Exception $e) {
                $this->stats['workers']['errors'][] = [
                    'line' => $lineNum,
                    'name' => $fullName,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $this->line("  Создано: {$this->stats['workers']['created']}, Пропущено: {$this->stats['workers']['skipped']}, Ошибок: " . count($this->stats['workers']['errors']));
    }

    /**
     * Назначение работника на рабочее место
     */
    protected function assignToWorkPlace($worker, $workPlaceName)
    {
        if ($this->dryRun) return;
        
        $workPlaceId = $this->workPlacesMap[$workPlaceName] ?? null;
        
        if (!$workPlaceId || str_starts_with($workPlaceId, 'temp_')) {
            return;
        }
        
        // Получаем должность
        $positionKey = $workPlaceName . '|Сотрудник';
        $positionId = $this->positionsMap[$positionKey] ?? null;
        
        // Проверяем существующее назначение
        $existing = WorkAssignment::where('worker_id', $worker->id)
            ->whereNull('ended_at')
            ->first();
            
        if ($existing) {
            $this->stats['work_assignments']['skipped']++;
            return;
        }
        
        $assignment = new WorkAssignment();
        $assignment->worker_id = $worker->id;
        $assignment->work_place_id = $workPlaceId;
        $assignment->position_id = $positionId;
        $assignment->started_at = now();
        $assignment->created_by = $this->createdBy;
        $assignment->save();
        
        $this->stats['work_assignments']['created']++;
    }

    /**
     * Заселение работника в комнату
     */
    protected function assignToRoom($worker, $hotelName, $roomNumber)
    {
        if ($this->dryRun) return;
        
        $roomKey = $hotelName . '|' . $roomNumber;
        $roomId = $this->roomsMap[$roomKey] ?? null;
        $hotelId = $this->hotelsMap[$hotelName] ?? null;
        
        if (!$roomId || !$hotelId) {
            return;
        }
        
        if (str_starts_with($roomId, 'temp_') || str_starts_with($hotelId, 'temp_')) {
            return;
        }
        
        // Проверяем существующее заселение
        $existing = RoomAssignment::where('worker_id', $worker->id)
            ->whereNull('check_out_date')
            ->first();
            
        if ($existing) {
            $this->stats['room_assignments']['skipped']++;
            return;
        }
        
        $assignment = new RoomAssignment();
        $assignment->worker_id = $worker->id;
        $assignment->room_id = $roomId;
        $assignment->hotel_id = $hotelId;
        $assignment->check_in_date = now();
        $assignment->created_by = $this->createdBy;
        $assignment->save();
        
        $this->stats['room_assignments']['created']++;
    }

    /**
     * Парсинг имени (Фамилия Имя -> first_name, last_name)
     */
    protected function parseName($fullName)
    {
        // Убираем лишние пробелы
        $fullName = preg_replace('/\s+/', ' ', trim($fullName));
        
        // Разбиваем по пробелу
        $parts = explode(' ', $fullName);
        
        if (count($parts) >= 2) {
            // Первое слово - фамилия, остальное - имя
            $lastName = array_shift($parts);
            $firstName = implode(' ', $parts);
        } else {
            $firstName = $fullName;
            $lastName = '';
        }
        
        return [
            'first_name' => $firstName,
            'last_name' => $lastName
        ];
    }

    /**
     * Парсинг пола
     */
    protected function parseGender($gender)
    {
        $gender = mb_strtolower($gender);
        
        if (str_contains($gender, 'muž') || str_contains($gender, 'male') || str_contains($gender, 'муж')) {
            return 'male';
        }
        
        if (str_contains($gender, 'žen') || str_contains($gender, 'female') || str_contains($gender, 'жен')) {
            return 'female';
        }
        
        return 'male'; // По умолчанию
    }

    /**
     * Парсинг CSV файла
     */
    protected function parseCsv($filePath)
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        
        if (!$handle) {
            throw new \Exception("Не удалось открыть файл: $filePath");
        }
        
        // Читаем заголовки
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return $rows;
        }
        
        // Очищаем заголовки от BOM и пробелов
        $headers = array_map(function($h) {
            return trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h));
        }, $headers);
        
        // Читаем данные
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

    /**
     * Вывод статистики
     */
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
        
        // Выводим ошибки
        if (!empty($this->stats['workers']['errors'])) {
            $this->error("\n=== ОШИБКИ ПРИ ИМПОРТЕ РАБОТНИКОВ ===");
            $this->table(
                ['Строка', 'Имя', 'Ошибка'],
                array_map(function($e) {
                    return [$e['line'], $e['name'], $e['error']];
                }, $this->stats['workers']['errors'])
            );
        }
    }
}
