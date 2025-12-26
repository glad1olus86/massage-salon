<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\Worker;
use App\Models\WorkPlace;
use App\Models\SystemNotification;
use App\Models\NotificationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    protected $userId;
    protected $settings;

    public function __construct()
    {
        if (Auth::check()) {
            $this->userId = Auth::user()->creatorId();
            $this->loadSettings();
        }
    }

    protected function loadSettings()
    {
        $this->settings = DB::table('settings')
            ->where('created_by', 1)
            ->whereIn('name', [
                'notifications_enabled',
                'notification_poll_interval',
                'notification_create_interval',
            ])
            ->pluck('value', 'name')
            ->toArray();
    }

    protected function getSetting($key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Check if we should create new notifications (based on create interval)
     */
    public function shouldCreateNotifications(): bool
    {
        if ($this->getSetting('notifications_enabled', 'on') !== 'on') {
            return false;
        }

        $cacheKey = 'notification_last_create_' . $this->userId;
        $interval = (int) $this->getSetting('notification_create_interval', 60);
        $lastCreate = Cache::get($cacheKey);

        if (!$lastCreate) {
            Cache::put($cacheKey, now(), 60 * 24);
            return true;
        }

        $minutesPassed = now()->diffInMinutes($lastCreate, absolute: true);
        
        if ($minutesPassed >= $interval) {
            Cache::put($cacheKey, now(), 60 * 24);
            return true;
        }

        return false;
    }

    /**
     * Run all notification checks based on custom rules
     */
    public function runChecks(): array
    {
        $newNotifications = [];

        if (!$this->shouldCreateNotifications()) {
            return $newNotifications;
        }

        // Get active rules for this company
        $rules = NotificationRule::where('created_by', $this->userId)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            $notifications = $this->processRule($rule);
            $newNotifications = array_merge($newNotifications, $notifications);
        }

        return $newNotifications;
    }

    /**
     * Process a single notification rule
     */
    protected function processRule(NotificationRule $rule): array
    {
        return match($rule->entity_type) {
            NotificationRule::ENTITY_WORKER => $this->processWorkerRule($rule),
            NotificationRule::ENTITY_ROOM => $this->processRoomRule($rule),
            NotificationRule::ENTITY_HOTEL => $this->processHotelRule($rule),
            NotificationRule::ENTITY_WORK_PLACE => $this->processWorkPlaceRule($rule),
            default => [],
        };
    }

    /**
     * Process worker-based rules
     */
    protected function processWorkerRule(NotificationRule $rule): array
    {
        $notifications = [];
        $conditions = $rule->conditions ?? [];
        $matchedWorkers = [];
        
        $workers = Worker::where('created_by', $this->userId)
            ->with(['currentAssignment.hotel', 'currentWorkAssignment.workPlace'])
            ->get();

        foreach ($workers as $worker) {
            if (!$this->workerMatchesConditions($worker, $conditions)) {
                continue;
            }

            $days = $this->calculateWorkerDays($worker, $conditions);
            
            if (!$rule->matchesPeriod($days)) {
                continue;
            }

            $matchedWorkers[] = ['worker' => $worker, 'days' => $days];
        }

        if (empty($matchedWorkers)) {
            return $notifications;
        }

        // If grouped, create single notification
        if ($rule->is_grouped) {
            $notification = $this->createGroupedWorkerNotification($matchedWorkers, $rule);
            if ($notification) {
                $notifications[] = $notification;
            }
        } else {
            foreach ($matchedWorkers as $match) {
                $notification = $this->createNotificationForWorker($match['worker'], $rule, $match['days']);
                if ($notification) {
                    $notifications[] = $notification;
                }
            }
        }

        return $notifications;
    }

    /**
     * Create grouped notification for workers
     */
    protected function createGroupedWorkerNotification(array $matchedWorkers, NotificationRule $rule): ?SystemNotification
    {
        $lines = [];
        foreach ($matchedWorkers as $match) {
            $worker = $match['worker'];
            $days = $match['days'];
            $worker->load(['currentAssignment.hotel', 'currentWorkAssignment.workPlace']);
            
            $hotelName = $worker->currentAssignment?->hotel?->name ?? __('Not specified');
            $workPlaceName = $worker->currentWorkAssignment?->workPlace?->name ?? __('Not specified');
            
            $line = $worker->first_name . ' ' . $worker->last_name;
            
            foreach ($rule->conditions as $condition) {
                $field = $condition['field'] ?? '';
                $part = match($field) {
                    'is_employed' => ' - ' . __('employed at ":place"', ['place' => $workPlaceName]),
                    'not_employed' => ' - ' . __('not employed'),
                    'is_housed' => ' - ' . __('staying at hotel ":hotel"', ['hotel' => $hotelName]),
                    'not_housed' => ' - ' . __('not staying at hotel'),
                    default => '',
                };
                $line .= $part;
            }
            
            if ($days > 0) {
                $line .= ' (' . $days . ' ' . __('d.') . ')';
            }
            
            $lines[] = $line;
        }

        return SystemNotification::create([
            'type' => 'custom_rule_' . $rule->id,
            'title' => $rule->name . ' (' . count($matchedWorkers) . ')',
            'message' => implode("\n", $lines),
            'severity' => $rule->severity,
            'data' => [
                'rule_id' => $rule->id,
                'count' => count($matchedWorkers),
                'grouped' => true,
            ],
            'link' => route('worker.index'),
            'created_by' => $this->userId,
        ]);
    }

    /**
     * Check if worker matches all conditions
     */
    protected function workerMatchesConditions(Worker $worker, array $conditions): bool
    {
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $value = $condition['value'] ?? null;

            // Reload relationships to ensure fresh data
            $hasWork = $worker->currentWorkAssignment()->exists();
            $hasRoom = $worker->currentAssignment()->exists();

            $matches = match($field) {
                'is_employed' => $hasWork,
                'not_employed' => !$hasWork,
                'is_housed' => $hasRoom,
                'not_housed' => !$hasRoom,
                'no_assignment' => !$hasWork && !$hasRoom,
                default => true,
            };

            if (!$matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate days for worker based on conditions
     */
    protected function calculateWorkerDays(Worker $worker, array $conditions): int
    {
        // Reload to get fresh data
        $worker->load(['currentAssignment', 'currentWorkAssignment']);
        
        // If worker is housed but not employed, calculate days since check-in
        if ($worker->currentAssignment && !$worker->currentWorkAssignment) {
            $checkInDate = $worker->currentAssignment->check_in_date;
            return (int) abs(now()->diffInDays($checkInDate));
        }

        // Default to days since registration
        return (int) abs(now()->diffInDays($worker->created_at));
    }

    /**
     * Create notification for worker
     */
    protected function createNotificationForWorker(Worker $worker, NotificationRule $rule, int $days): ?SystemNotification
    {
        // Reload relationships to get fresh data
        $worker->load(['currentAssignment.hotel', 'currentWorkAssignment.workPlace']);
        
        $hotelName = $worker->currentAssignment?->hotel?->name ?? '';
        $workPlaceName = $worker->currentWorkAssignment?->workPlace?->name ?? '';

        $message = $this->buildWorkerMessage($worker, $rule, $days, $hotelName, $workPlaceName);

        return SystemNotification::create([
            'type' => 'custom_rule_' . $rule->id,
            'title' => $rule->name,
            'message' => $message,
            'severity' => $rule->severity,
            'data' => [
                'rule_id' => $rule->id,
                'worker_id' => $worker->id,
                'worker_name' => $worker->first_name . ' ' . $worker->last_name,
                'conditions' => $rule->conditions,
                'hotel_name' => $hotelName,
                'work_place_name' => $workPlaceName,
                'days' => $days,
            ],
            'link' => route('worker.show', $worker->id),
            'created_by' => $this->userId,
        ]);
    }

    /**
     * Build message for worker notification
     */
    protected function buildWorkerMessage(Worker $worker, NotificationRule $rule, int $days, string $hotelName, string $workPlaceName): string
    {
        $name = $worker->first_name . ' ' . $worker->last_name;
        $conditions = $rule->conditions ?? [];
        
        $parts = [$name];

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            
            $part = match($field) {
                'is_employed' => __('employed at ":place"', ['place' => $workPlaceName]),
                'not_employed' => __('not employed'),
                'is_housed' => __('staying at hotel ":hotel"', ['hotel' => $hotelName]),
                'not_housed' => __('not staying at hotel'),
                'no_assignment' => __('without assignment'),
                default => null,
            };

            if ($part) {
                $parts[] = $part;
            }
        }

        if ($days > 0) {
            $parts[] = __(':days days', ['days' => $days]);
        }

        return implode(' - ', $parts);
    }

    /**
     * Process room-based rules
     */
    protected function processRoomRule(NotificationRule $rule): array
    {
        $notifications = [];
        $conditions = $rule->conditions ?? [];
        $matchedRooms = [];

        $rooms = Room::where('created_by', $this->userId)
            ->with(['hotel', 'currentAssignments'])
            ->get();

        foreach ($rooms as $room) {
            if (!$this->roomMatchesConditions($room, $conditions)) {
                continue;
            }

            $matchedRooms[] = $room;
        }

        if (empty($matchedRooms)) {
            return $notifications;
        }

        if ($rule->is_grouped) {
            $notification = $this->createGroupedRoomNotification($matchedRooms, $rule);
            if ($notification) {
                $notifications[] = $notification;
            }
        } else {
            foreach ($matchedRooms as $room) {
                $notification = $this->createNotificationForRoom($room, $rule);
                if ($notification) {
                    $notifications[] = $notification;
                }
            }
        }

        return $notifications;
    }

    /**
     * Create grouped notification for rooms
     */
    protected function createGroupedRoomNotification(array $matchedRooms, NotificationRule $rule): ?SystemNotification
    {
        $lines = [];
        foreach ($matchedRooms as $room) {
            $occupied = $room->currentAssignments->count();
            $occupancyPercent = $room->capacity > 0 ? round(($occupied / $room->capacity) * 100) : 0;
            
            $line = __('Room') . ' ' . $room->room_number . ' (' . $room->hotel->name . ') - ' . $occupied . '/' . $room->capacity . ' (' . $occupancyPercent . '%)';
            $lines[] = $line;
        }

        return SystemNotification::create([
            'type' => 'custom_rule_' . $rule->id,
            'title' => $rule->name . ' (' . count($matchedRooms) . ')',
            'message' => implode("\n", $lines),
            'severity' => $rule->severity,
            'data' => [
                'rule_id' => $rule->id,
                'count' => count($matchedRooms),
                'grouped' => true,
            ],
            'link' => route('hotel.index'),
            'created_by' => $this->userId,
        ]);
    }

    /**
     * Check if room matches conditions
     */
    protected function roomMatchesConditions(Room $room, array $conditions): bool
    {
        $occupied = $room->currentAssignments->count();
        $capacity = $room->capacity;
        $occupancyPercent = $capacity > 0 ? round(($occupied / $capacity) * 100) : 0;

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $value = (int) ($condition['value'] ?? 0);

            $matches = match($field) {
                'is_full' => $occupied >= $capacity,
                'is_empty' => $occupied === 0,
                'is_partial' => $occupied > 0 && $occupied < $capacity,
                'occupancy_above' => $occupancyPercent > $value,
                'occupancy_below' => $occupancyPercent < $value,
                default => true,
            };

            if (!$matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create notification for room
     */
    protected function createNotificationForRoom(Room $room, NotificationRule $rule): ?SystemNotification
    {
        $occupied = $room->currentAssignments->count();
        $occupancyPercent = $room->capacity > 0 ? round(($occupied / $room->capacity) * 100) : 0;

        return SystemNotification::create([
            'type' => 'custom_rule_' . $rule->id,
            'title' => $rule->name,
            'message' => __('Room :number at hotel ":hotel" - :occupied/:capacity (:percent%)', [
                'number' => $room->room_number,
                'hotel' => $room->hotel->name ?? 'N/A',
                'occupied' => $occupied,
                'capacity' => $room->capacity,
                'percent' => $occupancyPercent,
            ]),
            'severity' => $rule->severity,
            'data' => [
                'rule_id' => $rule->id,
                'room_id' => $room->id,
                'occupancy_percent' => $occupancyPercent,
            ],
            'link' => route('hotel.rooms', $room->hotel_id),
            'created_by' => $this->userId,
        ]);
    }

    /**
     * Process hotel-based rules
     */
    protected function processHotelRule(NotificationRule $rule): array
    {
        $notifications = [];
        $conditions = $rule->conditions ?? [];
        $matchedHotels = [];

        $hotels = Hotel::where('created_by', $this->userId)->with('rooms')->get();

        foreach ($hotels as $hotel) {
            $stats = $this->calculateHotelStats($hotel);
            
            if (!$this->hotelMatchesConditions($stats, $conditions)) {
                continue;
            }

            $matchedHotels[] = ['hotel' => $hotel, 'stats' => $stats];
        }

        if (empty($matchedHotels)) {
            return $notifications;
        }

        if ($rule->is_grouped) {
            $notification = $this->createGroupedHotelNotification($matchedHotels, $rule);
            if ($notification) {
                $notifications[] = $notification;
            }
        } else {
            foreach ($matchedHotels as $match) {
                $notification = $this->createNotificationForHotel($match['hotel'], $rule, $match['stats']);
                if ($notification) {
                    $notifications[] = $notification;
                }
            }
        }

        return $notifications;
    }

    /**
     * Create grouped notification for hotels
     */
    protected function createGroupedHotelNotification(array $matchedHotels, NotificationRule $rule): ?SystemNotification
    {
        $lines = [];
        foreach ($matchedHotels as $match) {
            $hotel = $match['hotel'];
            $stats = $match['stats'];
            
            $line = $hotel->name . ' - ' . $stats['occupied'] . '/' . $stats['capacity'] . ' ' . __('spots') . ' (' . $stats['percent'] . '%)';
            $lines[] = $line;
        }

        return SystemNotification::create([
            'type' => 'custom_rule_' . $rule->id,
            'title' => $rule->name . ' (' . count($matchedHotels) . ')',
            'message' => implode("\n", $lines),
            'severity' => $rule->severity,
            'data' => [
                'rule_id' => $rule->id,
                'count' => count($matchedHotels),
                'grouped' => true,
            ],
            'link' => route('hotel.index'),
            'created_by' => $this->userId,
        ]);
    }

    /**
     * Calculate hotel statistics
     */
    protected function calculateHotelStats(Hotel $hotel): array
    {
        $totalCapacity = 0;
        $totalOccupied = 0;
        $emptyRooms = 0;

        foreach ($hotel->rooms as $room) {
            $totalCapacity += $room->capacity;
            $occupied = $room->currentAssignments()->count();
            $totalOccupied += $occupied;
            
            if ($occupied === 0) {
                $emptyRooms++;
            }
        }

        return [
            'capacity' => $totalCapacity,
            'occupied' => $totalOccupied,
            'percent' => $totalCapacity > 0 ? round(($totalOccupied / $totalCapacity) * 100) : 0,
            'empty_rooms' => $emptyRooms,
        ];
    }

    /**
     * Check if hotel matches conditions
     */
    protected function hotelMatchesConditions(array $stats, array $conditions): bool
    {
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $value = (int) ($condition['value'] ?? 0);

            $matches = match($field) {
                'occupancy_above' => $stats['percent'] > $value,
                'occupancy_below' => $stats['percent'] < $value,
                'has_empty_rooms' => $stats['empty_rooms'] > 0,
                'no_empty_rooms' => $stats['empty_rooms'] === 0,
                default => true,
            };

            if (!$matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create notification for hotel
     */
    protected function createNotificationForHotel(Hotel $hotel, NotificationRule $rule, array $stats): ?SystemNotification
    {
        return SystemNotification::create([
            'type' => 'custom_rule_' . $rule->id,
            'title' => $rule->name,
            'message' => __('Hotel ":name" - :occupied/:capacity spots (:percent%)', [
                'name' => $hotel->name,
                'occupied' => $stats['occupied'],
                'capacity' => $stats['capacity'],
                'percent' => $stats['percent'],
            ]),
            'severity' => $rule->severity,
            'data' => [
                'rule_id' => $rule->id,
                'hotel_id' => $hotel->id,
                'stats' => $stats,
            ],
            'link' => route('hotel.rooms', $hotel->id),
            'created_by' => $this->userId,
        ]);
    }

    /**
     * Process work place-based rules
     */
    protected function processWorkPlaceRule(NotificationRule $rule): array
    {
        $notifications = [];
        $conditions = $rule->conditions ?? [];
        $matchedPlaces = [];

        $workPlaces = WorkPlace::where('created_by', $this->userId)
            ->with('currentAssignments')
            ->get();

        foreach ($workPlaces as $workPlace) {
            $workerCount = $workPlace->currentAssignments->count();
            
            if (!$this->workPlaceMatchesConditions($workerCount, $conditions)) {
                continue;
            }

            $matchedPlaces[] = ['workPlace' => $workPlace, 'count' => $workerCount];
        }

        if (empty($matchedPlaces)) {
            return $notifications;
        }

        if ($rule->is_grouped) {
            $notification = $this->createGroupedWorkPlaceNotification($matchedPlaces, $rule);
            if ($notification) {
                $notifications[] = $notification;
            }
        } else {
            foreach ($matchedPlaces as $match) {
                $notification = $this->createNotificationForWorkPlace($match['workPlace'], $rule, $match['count']);
                if ($notification) {
                    $notifications[] = $notification;
                }
            }
        }

        return $notifications;
    }

    /**
     * Create grouped notification for work places
     */
    protected function createGroupedWorkPlaceNotification(array $matchedPlaces, NotificationRule $rule): ?SystemNotification
    {
        $lines = [];
        foreach ($matchedPlaces as $match) {
            $workPlace = $match['workPlace'];
            $count = $match['count'];
            
            $line = $workPlace->name . ' - ' . $count . ' ' . __('employees');
            $lines[] = $line;
        }

        return SystemNotification::create([
            'type' => 'custom_rule_' . $rule->id,
            'title' => $rule->name . ' (' . count($matchedPlaces) . ')',
            'message' => implode("\n", $lines),
            'severity' => $rule->severity,
            'data' => [
                'rule_id' => $rule->id,
                'count' => count($matchedPlaces),
                'grouped' => true,
            ],
            'link' => route('work-place.index'),
            'created_by' => $this->userId,
        ]);
    }

    /**
     * Check if work place matches conditions
     */
    protected function workPlaceMatchesConditions(int $workerCount, array $conditions): bool
    {
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $value = (int) ($condition['value'] ?? 0);

            $matches = match($field) {
                'has_no_workers' => $workerCount === 0,
                'workers_below' => $workerCount < $value,
                'workers_above' => $workerCount > $value,
                default => true,
            };

            if (!$matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create notification for work place
     */
    protected function createNotificationForWorkPlace(WorkPlace $workPlace, NotificationRule $rule, int $workerCount): ?SystemNotification
    {
        return SystemNotification::create([
            'type' => 'custom_rule_' . $rule->id,
            'title' => $rule->name,
            'message' => __('Work place ":name" - :count employees', [
                'name' => $workPlace->name,
                'count' => $workerCount,
            ]),
            'severity' => $rule->severity,
            'data' => [
                'rule_id' => $rule->id,
                'work_place_id' => $workPlace->id,
                'worker_count' => $workerCount,
            ],
            'link' => route('work-place.workers', $workPlace->id),
            'created_by' => $this->userId,
        ]);
    }

    public function getPollInterval(): int
    {
        return (int) $this->getSetting('notification_poll_interval', 1) * 60 * 1000;
    }

    public static function getSettings(): array
    {
        return DB::table('settings')
            ->where('created_by', 1)
            ->whereIn('name', [
                'notifications_enabled',
                'notification_poll_interval',
                'notification_create_interval',
            ])
            ->pluck('value', 'name')
            ->toArray();
    }
}
