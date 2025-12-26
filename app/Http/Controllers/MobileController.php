<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\WorkPlace;
use App\Models\Vehicle;
use App\Models\CashPeriod;
use App\Models\DocumentTemplate;
use App\Models\Notification;
use App\Models\SystemNotification;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileController extends Controller
{
    /**
     * Get the creator ID for the current user
     */
    protected function getCreatorId()
    {
        return Auth::user()->creatorId();
    }

    // ==================== WORKERS ====================

    /**
     * Display mobile workers list
     */
    public function workers(Request $request)
    {
        $query = Worker::where('created_by', $this->getCreatorId())
            ->with(['currentWorkAssignment.workPlace', 'currentAssignment.hotel', 'currentAssignment.room']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('nationality', 'like', "%{$search}%");
            });
        }

        // Filter by hotel
        if ($request->filled('hotel_id')) {
            $hotelId = $request->hotel_id;
            $query->whereHas('currentAssignment', function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId);
            });
        }

        // Filter by workplace
        if ($request->filled('workplace_id')) {
            $workplaceId = $request->workplace_id;
            $query->whereHas('currentWorkAssignment', function ($q) use ($workplaceId) {
                $q->where('work_place_id', $workplaceId);
            });
        }

        // Filter by nationality
        if ($request->filled('nationality')) {
            $query->where('nationality', $request->nationality);
        }

        // Filter by gender
        if ($request->filled('gender')) {
            $genders = is_array($request->gender) ? $request->gender : [$request->gender];
            $query->whereIn('gender', $genders);
        }

        $workers = $query->orderBy('first_name')->paginate(20);

        // Get filter options
        $hotels = Hotel::where('created_by', $this->getCreatorId())->orderBy('name')->get();
        $workplaces = WorkPlace::where('created_by', $this->getCreatorId())->orderBy('name')->get();
        $nationalities = Worker::where('created_by', $this->getCreatorId())
            ->whereNotNull('nationality')
            ->where('nationality', '!=', '')
            ->distinct()
            ->pluck('nationality')
            ->sort();

        return view('mobile.workers.index', compact('workers', 'hotels', 'workplaces', 'nationalities'));
    }

    /**
     * Display mobile worker detail
     */
    public function workerShow($id)
    {
        $worker = Worker::where('created_by', $this->getCreatorId())
            ->with([
                'currentWorkAssignment.workPlace',
                'currentWorkAssignment.position',
                'currentAssignment.hotel',
                'currentAssignment.room'
            ])
            ->findOrFail($id);

        return view('mobile.workers.show', compact('worker'));
    }

    // ==================== HOTELS ====================

    /**
     * Display mobile hotels list
     */
    public function hotels()
    {
        $hotels = Hotel::where('created_by', $this->getCreatorId())
            ->with(['rooms.currentAssignments'])
            ->get();

        return view('mobile.hotels.index', compact('hotels'));
    }

    /**
     * Display mobile hotel rooms
     */
    public function hotelRooms($id)
    {
        $hotel = Hotel::where('created_by', $this->getCreatorId())
            ->with(['rooms.currentAssignments.worker'])
            ->findOrFail($id);

        return view('mobile.hotels.rooms', compact('hotel'));
    }

    /**
     * Display mobile room detail
     */
    public function roomShow($id)
    {
        $room = Room::whereHas('hotel', function ($q) {
            $q->where('created_by', $this->getCreatorId());
        })->with(['hotel', 'currentAssignments.worker'])->findOrFail($id);

        return view('mobile.rooms.show', compact('room'));
    }

    // ==================== WORKPLACES ====================

    /**
     * Display mobile workplaces list
     */
    public function workplaces()
    {
        $workplaces = WorkPlace::where('created_by', $this->getCreatorId())
            ->withCount(['currentAssignments', 'positions'])
            ->get();

        return view('mobile.workplaces.index', compact('workplaces'));
    }

    /**
     * Display mobile workplace detail
     */
    public function workplaceShow($id)
    {
        $workplace = WorkPlace::where('created_by', $this->getCreatorId())
            ->with(['currentAssignments.worker', 'positions'])
            ->findOrFail($id);

        return view('mobile.workplaces.show', compact('workplace'));
    }

    // ==================== VEHICLES ====================

    /**
     * Display mobile vehicles list
     */
    public function vehicles()
    {
        $vehicles = Vehicle::where('created_by', $this->getCreatorId())
            ->with(['latestInspection', 'assignedPerson'])
            ->orderBy('license_plate')
            ->get();

        return view('mobile.vehicles.index', compact('vehicles'));
    }

    /**
     * Display mobile vehicle detail
     */
    public function vehicleShow($id)
    {
        $vehicle = Vehicle::where('created_by', $this->getCreatorId())
            ->with(['inspections', 'assignedPerson', 'latestInspection'])
            ->findOrFail($id);

        return view('mobile.vehicles.show', compact('vehicle'));
    }

    /**
     * Display mobile vehicle create form
     */
    public function vehicleCreate()
    {
        $workers = Worker::where('created_by', $this->getCreatorId())->get();
        $users = User::where('created_by', $this->getCreatorId())->get();

        return view('mobile.vehicles.create', compact('workers', 'users'));
    }

    /**
     * Display mobile vehicle edit form
     */
    public function vehicleEdit($id)
    {
        $vehicle = Vehicle::where('created_by', $this->getCreatorId())->findOrFail($id);
        $workers = Worker::where('created_by', $this->getCreatorId())->get();
        $users = User::where('created_by', $this->getCreatorId())->get();

        return view('mobile.vehicles.edit', compact('vehicle', 'workers', 'users'));
    }

    // ==================== CASHBOX ====================

    /**
     * Display mobile cashbox periods list
     */
    public function cashbox()
    {
        $user = Auth::user();
        $companyId = $this->getCreatorId();
        
        $periods = CashPeriod::where('created_by', $companyId)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        // Get or create current period
        $cashboxService = app(\App\Services\CashboxService::class);
        $hierarchyService = app(\App\Services\CashHierarchyService::class);
        
        $currentPeriod = $cashboxService->getOrCreateCurrentPeriod($companyId);
        $balance = $cashboxService->getBalance($currentPeriod, $user);
        $recipients = $hierarchyService->getAvailableRecipients($user, $companyId);
        
        // Get refundable transactions
        $refundableTransactions = \App\Models\CashTransaction::where('cash_period_id', $currentPeriod->id)
            ->where('recipient_id', $user->id)
            ->where('recipient_type', User::class)
            ->where('type', \App\Models\CashTransaction::TYPE_DISTRIBUTION)
            ->where(function($query) {
                $query->where('distribution_type', \App\Models\CashTransaction::DISTRIBUTION_TYPE_TRANSFER)
                      ->orWhereNull('distribution_type');
            })
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('mobile.cashbox.index', compact(
            'periods', 
            'currentPeriod', 
            'balance', 
            'recipients',
            'refundableTransactions'
        ));
    }

    /**
     * Display mobile cashbox period detail
     */
    public function cashboxShow($id)
    {
        $user = Auth::user();
        $companyId = $this->getCreatorId();
        
        $period = CashPeriod::where('created_by', $companyId)
            ->findOrFail($id);

        $cashboxService = app(\App\Services\CashboxService::class);
        $hierarchyService = app(\App\Services\CashHierarchyService::class);
        
        $userRole = $hierarchyService->getUserCashboxRole($user);
        $balance = $cashboxService->getBalance($period, $user);
        $recipients = $hierarchyService->getAvailableRecipients($user, $companyId);
        
        // Permissions
        $canDeposit = $user->type === 'company' || $user->can('cashbox_deposit');
        $canDistribute = $user->type === 'company' || $user->can('cashbox_distribute');
        $canRefund = $user->type === 'company' || $user->can('cashbox_refund');
        $canSelfSalary = ($user->type === 'company' || $user->can('cashbox_self_salary')) && $hierarchyService->canDistributeToSelf($user);
        $hasSelfSalaryThisPeriod = $canSelfSalary ? $cashboxService->hasSelfSalaryThisPeriod($period, $user) : false;
        
        // View permission level
        $viewLevel = $hierarchyService->getViewPermissionLevel($user);
        $canViewTotalDeposited = $viewLevel === 'boss' || $user->can('cashbox_view_boss');
        
        // Get transactions
        $transactions = \App\Models\CashTransaction::where('cash_period_id', $period->id)
            ->where(function($query) use ($user) {
                $query->where('sender_id', $user->id)
                      ->orWhere(function($q) use ($user) {
                          $q->where('recipient_id', $user->id)
                            ->where('recipient_type', User::class);
                      });
            })
            ->with(['sender', 'recipient'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Refundable transactions
        $refundableTransactions = \App\Models\CashTransaction::where('cash_period_id', $period->id)
            ->where('recipient_id', $user->id)
            ->where('recipient_type', User::class)
            ->where('type', \App\Models\CashTransaction::TYPE_DISTRIBUTION)
            ->where(function($query) {
                $query->where('distribution_type', \App\Models\CashTransaction::DISTRIBUTION_TYPE_TRANSFER)
                      ->orWhereNull('distribution_type');
            })
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('mobile.cashbox.show', compact(
            'period',
            'userRole',
            'balance',
            'recipients',
            'canDeposit',
            'canDistribute',
            'canRefund',
            'canSelfSalary',
            'hasSelfSalaryThisPeriod',
            'canViewTotalDeposited',
            'transactions',
            'refundableTransactions'
        ));
    }

    // ==================== DOCUMENTS ====================

    /**
     * Display mobile documents/templates list
     */
    public function documents()
    {
        $templates = DocumentTemplate::where('created_by', $this->getCreatorId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $workers = Worker::where('created_by', $this->getCreatorId())->get();

        return view('mobile.documents.index', compact('templates', 'workers'));
    }

    // ==================== CALENDAR ====================

    /**
     * Display mobile calendar
     */
    public function calendar(Request $request)
    {
        return view('mobile.calendar.index');
    }

    // ==================== AUDIT ====================

    /**
     * Display mobile audit log
     */
    public function audit(Request $request)
    {
        $logs = AuditLog::where('created_by', $this->getCreatorId())
            ->with(['user', 'subject'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return view('mobile.audit.index', compact('logs'));
    }

    // ==================== NOTIFICATIONS ====================

    /**
     * Display mobile notifications
     */
    public function notifications()
    {
        // Try SystemNotification first, fallback to Notification
        $notifications = SystemNotification::forCurrentUser()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('mobile.notifications.index', compact('notifications'));
    }

    // ==================== PROFILE ====================

    /**
     * Display mobile user profile
     */
    public function profile()
    {
        $user = Auth::user();
        return view('mobile.profile.index', compact('user'));
    }
}
