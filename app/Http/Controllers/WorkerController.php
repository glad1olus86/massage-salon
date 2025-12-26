<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use App\Services\DocumentScannerService;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WorkerController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->can('manage worker')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        // Get filter data for dropdowns (filtered by visibility)
        $hotels = \App\Models\Hotel::where('created_by', Auth::user()->creatorId())
            ->visibleToUser(Auth::user())
            ->get();
        $workplaces = \App\Models\WorkPlace::where('created_by', Auth::user()->creatorId())
            ->visibleToUser(Auth::user())
            ->get();
        $nationalities = Worker::where('created_by', Auth::user()->creatorId())
            ->visibleToUser(Auth::user())
            ->whereNotNull('nationality')
            ->distinct()
            ->pluck('nationality')
            ->sort();

        // Get total count for display (filtered by visibility)
        $totalWorkers = Worker::where('created_by', Auth::user()->creatorId())
            ->visibleToUser(Auth::user())
            ->count();

        return view('worker.index', compact('hotels', 'workplaces', 'nationalities', 'totalWorkers'));
    }

    /**
     * AJAX endpoint for server-side search and filtering
     */
    public function search(Request $request)
    {
        if (!Auth::user()->can('manage worker')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $query = Worker::where('created_by', Auth::user()->creatorId())
            ->visibleToUser(Auth::user())
            ->with(['currentWorkAssignment.workPlace', 'currentAssignment.hotel', 'currentAssignment.room']);

        // Search by name or nationality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('nationality', 'LIKE', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        // Filter by hotel
        if ($request->filled('hotel_id')) {
            $hotelId = $request->hotel_id;
            $query->whereHas('currentAssignment', function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId)->whereNull('check_out_date');
            });
        }

        // Filter by workplace
        if ($request->filled('workplace_id')) {
            $workplaceId = $request->workplace_id;
            $query->whereHas('currentWorkAssignment', function ($q) use ($workplaceId) {
                $q->where('work_place_id', $workplaceId)->whereNull('ended_at');
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

        // Filter by date of birth range
        if ($request->filled('dob_from')) {
            $query->where('dob', '>=', $request->dob_from);
        }
        if ($request->filled('dob_to')) {
            $query->where('dob', '<=', $request->dob_to);
        }

        // Filter by registration date range
        if ($request->filled('reg_from')) {
            $query->where('registration_date', '>=', $request->reg_from);
        }
        if ($request->filled('reg_to')) {
            $query->where('registration_date', '<=', $request->reg_to);
        }

        // Filter by accommodation payment type
        if ($request->filled('accommodation_payment')) {
            $paymentType = $request->accommodation_payment;
            if ($paymentType === 'not_housed') {
                // Workers without active room assignment
                $query->whereDoesntHave('currentAssignment');
            } elseif ($paymentType === 'agency') {
                // Workers where agency pays
                $query->whereHas('currentAssignment', function ($q) {
                    $q->whereNull('check_out_date')->where('payment_type', 'agency');
                });
            } elseif ($paymentType === 'worker') {
                // Workers who pay themselves
                $query->whereHas('currentAssignment', function ($q) {
                    $q->whereNull('check_out_date')->where('payment_type', 'worker');
                });
            }
        }

        // Get total filtered count before pagination
        $totalFiltered = $query->count();

        // Sorting
        $sortField = $request->get('sort', 'first_name');
        $sortDir = $request->get('dir', 'asc');
        $allowedSorts = ['first_name', 'last_name', 'dob', 'gender', 'nationality', 'registration_date'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        // Pagination
        $perPage = min((int) $request->get('per_page', 50), 100);
        $page = max((int) $request->get('page', 1), 1);
        $workers = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        // Format response
        $data = $workers->map(function ($worker) {
            return [
                'id' => $worker->id,
                'first_name' => $worker->first_name,
                'last_name' => $worker->last_name,
                'dob' => $worker->dob ? Auth::user()->dateFormat($worker->dob) : '-',
                'dob_raw' => $worker->dob ? $worker->dob->format('Y-m-d') : '',
                'gender' => $worker->gender,
                'gender_label' => $worker->gender == 'male' ? __('Male') : __('Female'),
                'nationality' => $worker->nationality ? __($worker->nationality) : '',
                'nationality_raw' => $worker->nationality,
                'nationality_flag' => \App\Services\NationalityFlagService::getFlagHtml($worker->nationality, 18),
                'registration_date' => $worker->registration_date ? Auth::user()->dateFormat($worker->registration_date) : '-',
                'registration_date_raw' => $worker->registration_date ? $worker->registration_date->format('Y-m-d') : '',
                'is_working' => $worker->currentWorkAssignment ? true : false,
                'work_place' => $worker->currentWorkAssignment?->workPlace?->name ?? '',
                'work_place_id' => $worker->currentWorkAssignment?->work_place_id,
                'is_housed' => $worker->currentAssignment ? true : false,
                'hotel' => $worker->currentAssignment?->hotel?->name ?? '',
                'hotel_id' => $worker->currentAssignment?->hotel_id,
                'show_url' => route('worker.show', $worker->id),
                'edit_url' => route('worker.edit', $worker->id),
                'delete_url' => route('worker.destroy', $worker->id),
            ];
        });

        return response()->json([
            'data' => $data,
            'total' => $totalFiltered,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($totalFiltered / $perPage),
        ]);
    }

    /**
     * Get all worker IDs matching current filters (for bulk operations)
     */
    public function getFilteredIds(Request $request)
    {
        if (!Auth::user()->can('manage worker')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $query = Worker::where('created_by', Auth::user()->creatorId());

        // Apply same filters as search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('nationality', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('hotel_id')) {
            $hotelId = $request->hotel_id;
            $query->whereHas('currentAssignment', function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId)->whereNull('check_out_date');
            });
        }

        if ($request->filled('workplace_id')) {
            $workplaceId = $request->workplace_id;
            $query->whereHas('currentWorkAssignment', function ($q) use ($workplaceId) {
                $q->where('work_place_id', $workplaceId)->whereNull('ended_at');
            });
        }

        if ($request->filled('nationality')) {
            $query->where('nationality', $request->nationality);
        }

        if ($request->filled('gender')) {
            $genders = is_array($request->gender) ? $request->gender : [$request->gender];
            $query->whereIn('gender', $genders);
        }

        if ($request->filled('dob_from')) {
            $query->where('dob', '>=', $request->dob_from);
        }
        if ($request->filled('dob_to')) {
            $query->where('dob', '<=', $request->dob_to);
        }

        if ($request->filled('reg_from')) {
            $query->where('registration_date', '>=', $request->reg_from);
        }
        if ($request->filled('reg_to')) {
            $query->where('registration_date', '<=', $request->reg_to);
        }

        $ids = $query->pluck('id');

        return response()->json([
            'ids' => $ids,
            'count' => $ids->count(),
        ]);
    }

    public function show(Worker $worker)
    {
        if (Auth::user()->can('manage worker')) {
            if ($worker->created_by == Auth::user()->creatorId()) {
                $worker->load('currentAssignment', 'currentWorkAssignment.workPlace', 'responsible');
                
                // Filter hotels by visibility (coordinators see only their assigned hotels)
                $hotels = \App\Models\Hotel::where('created_by', Auth::user()->creatorId())
                    ->visibleToUser(Auth::user())
                    ->get()
                    ->pluck('name', 'id');

                // Load work places for assignment modal (filtered by visibility)
                $workPlaces = \App\Models\WorkPlace::where('created_by', Auth::user()->creatorId())
                    ->visibleToUser(Auth::user())
                    ->get()
                    ->pluck('name', 'id');

                // Load recent audit events for this worker
                $recentEvents = \App\Models\AuditLog::where('subject_type', 'App\Models\Worker')
                    ->where('subject_id', $worker->id)
                    ->where('created_by', Auth::user()->creatorId())
                    ->latest()
                    ->limit(10)
                    ->get();

                return view('worker.show', compact('worker', 'hotels', 'workPlaces', 'recentEvents'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (Auth::user()->can('create worker')) {
            return view('worker.create');
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (Auth::user()->can('create worker')) {
            // Check plan limit
            if (!PlanLimitService::canCreateWorker()) {
                return redirect()->back()->with('error', __('Worker limit reached for your plan.'));
            }

            $validator = Validator::make(
                $request->all(),
                [
                    'first_name' => 'required',
                    'last_name' => 'required',
                    'dob' => 'required|date',
                    'gender' => 'required',
                    'nationality' => 'required',
                    'registration_date' => 'required|date',
                    'document_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                    'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $worker = new Worker();
            $worker->first_name = $request->first_name;
            $worker->last_name = $request->last_name;
            $worker->dob = $request->dob;
            $worker->gender = $request->gender;
            $worker->nationality = $request->nationality;
            $worker->registration_date = $request->registration_date;
            $worker->phone = $request->phone;
            $worker->email = $request->email;
            $worker->created_by = Auth::user()->creatorId();
            $worker->responsible_id = Auth::id(); // Auto-assign creator as responsible

            // Check if we have a pre-scanned document from the scanner
            if ($request->filled('scanned_document_path')) {
                $scannedFile = $request->scanned_document_path;
                // Verify file exists in uploads folder
                if (file_exists(public_path('uploads/worker_documents/' . $scannedFile))) {
                    $worker->document_photo = $scannedFile;
                }
            }
            
            // If user uploaded a new document, it overrides the scanned one
            if ($request->hasFile('document_photo')) {
                $fileName = time() . '_doc_' . $request->document_photo->getClientOriginalName();
                $request->document_photo->move(public_path('uploads/worker_documents'), $fileName);
                $worker->document_photo = $fileName;
            }

            if ($request->hasFile('photo')) {
                $fileName = time() . '_photo_' . $request->photo->getClientOriginalName();
                $request->photo->move(public_path('uploads/worker_photos'), $fileName);
                $worker->photo = $fileName;
            }

            $worker->save();

            if ($request->input('redirect_to') === 'mobile') {
                return redirect()->route('mobile.workers.index')->with('success', __('Worker successfully created.'));
            }

            return redirect()->route('worker.index')->with('success', __('Worker successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit(Worker $worker)
    {
        if (Auth::user()->can('edit worker')) {
            if ($worker->created_by == Auth::user()->creatorId()) {
                $responsibleService = new \App\Services\ResponsibleService();
                $canAssignResponsible = $responsibleService->canAssignResponsible();
                $assignableUsers = $canAssignResponsible ? $responsibleService->getAssignableUsers() : collect();
                
                return view('worker.edit', compact('worker', 'canAssignResponsible', 'assignableUsers'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, Worker $worker)
    {
        if (Auth::user()->can('edit worker')) {
            if ($worker->created_by == Auth::user()->creatorId()) {
                $validator = Validator::make(
                    $request->all(),
                    [
                        'first_name' => 'required',
                        'last_name' => 'required',
                        'dob' => 'required|date',
                        'gender' => 'required',
                        'nationality' => 'required',
                        'registration_date' => 'required|date',
                        'document_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                        'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                    ]
                );

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }

                $worker->first_name = $request->first_name;
                $worker->last_name = $request->last_name;
                $worker->dob = $request->dob;
                $worker->gender = $request->gender;
                $worker->nationality = $request->nationality;
                $worker->registration_date = $request->registration_date;
                $worker->phone = $request->phone;
                $worker->email = $request->email;

                if ($request->hasFile('document_photo')) {
                    $fileName = time() . '_doc_' . $request->document_photo->getClientOriginalName();
                    $request->document_photo->move(public_path('uploads/worker_documents'), $fileName);
                    $worker->document_photo = $fileName;
                }

                if ($request->hasFile('photo')) {
                    $fileName = time() . '_photo_' . $request->photo->getClientOriginalName();
                    $request->photo->move(public_path('uploads/worker_photos'), $fileName);
                    $worker->photo = $fileName;
                }

                // Handle responsible assignment
                if ($request->filled('responsible_id')) {
                    $responsibleService = new \App\Services\ResponsibleService();
                    if ($responsibleService->canAssignResponsible()) {
                        try {
                            $responsibleService->assignResponsible($worker, $request->responsible_id);
                        } catch (\Exception $e) {
                            return redirect()->back()->with('error', $e->getMessage());
                        }
                    }
                } else {
                    $worker->save();
                }

                if ($request->input('redirect_to') === 'mobile') {
                    return redirect()->route('mobile.workers.show', $worker->id)->with('success', __('Worker successfully updated.'));
                }

                return redirect()->route('worker.index')->with('success', __('Worker successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(Request $request, Worker $worker)
    {
        if (Auth::user()->can('delete worker')) {
            if ($worker->created_by == Auth::user()->creatorId()) {
                $worker->delete();
                
                if ($request->input('redirect_to') === 'mobile') {
                    return redirect()->route('mobile.workers.index')->with('success', __('Worker successfully deleted.'));
                }
                
                return redirect()->route('worker.index')->with('success', __('Worker successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Check if worker with same name already exists
     * Returns list of potential duplicates
     */
    public function checkDuplicate(Request $request)
    {
        if (!Auth::user()->can('create worker')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|min:2',
            'last_name' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $firstName = trim($request->first_name);
        $lastName = trim($request->last_name);

        // Find workers with same first and last name (case insensitive)
        $duplicates = Worker::where('created_by', Auth::user()->creatorId())
            ->whereRaw('LOWER(first_name) = ?', [strtolower($firstName)])
            ->whereRaw('LOWER(last_name) = ?', [strtolower($lastName)])
            ->get(['id', 'first_name', 'last_name', 'dob', 'nationality', 'created_at']);

        if ($duplicates->count() > 0) {
            return response()->json([
                'has_duplicates' => true,
                'duplicates' => $duplicates->map(function ($worker) {
                    return [
                        'id' => $worker->id,
                        'name' => $worker->first_name . ' ' . $worker->last_name,
                        'dob' => $worker->dob ? $worker->dob->format('d.m.Y') : null,
                        'nationality' => $worker->nationality,
                        'created_at' => $worker->created_at->format('d.m.Y'),
                    ];
                }),
                'message' => __('Worker with this first and last name already exists!'),
            ]);
        }

        return response()->json([
            'has_duplicates' => false,
        ]);
    }

    /**
     * Scan document image and extract worker data using Gemini API
     * Also saves the scanned document for later attachment to worker
     */
    public function scanDocument(Request $request)
    {
        if (!Auth::user()->can('create worker')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $validator = Validator::make($request->all(), [
            'document_image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $image = $request->file('document_image');
            $imagePath = $image->getRealPath();

            $scanner = new DocumentScannerService();
            $data = $scanner->scanDocument($imagePath);

            // Save the scanned document to uploads folder
            $fileName = time() . '_scan_' . $image->getClientOriginalName();
            $image->move(public_path('uploads/worker_documents'), $fileName);

            return response()->json([
                'success' => true,
                'data' => $data,
                'scanned_document' => $fileName  // Return saved filename
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => __('Scan error: ') . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk assign workers to a work place
     */
    public function bulkAssign(Request $request)
    {
        if (!Auth::user()->can('manage work place')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = Validator::make($request->all(), [
            'worker_ids' => 'required|string',
            'work_place_id' => 'required|exists:work_places,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        $workerIds = explode(',', $request->worker_ids);
        $workPlace = \App\Models\WorkPlace::find($request->work_place_id);

        if ($workPlace->created_by != Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $assigned = 0;
        $skipped = 0;

        foreach ($workerIds as $workerId) {
            $worker = Worker::where('id', $workerId)
                ->where('created_by', Auth::user()->creatorId())
                ->first();

            if (!$worker) continue;

            // Skip if already has active work assignment
            if ($worker->currentWorkAssignment) {
                $skipped++;
                continue;
            }

            $assignment = new \App\Models\WorkAssignment();
            $assignment->worker_id = $worker->id;
            $assignment->work_place_id = $workPlace->id;
            $assignment->started_at = now();
            $assignment->created_by = Auth::user()->creatorId();
            $assignment->save();
            $assigned++;
        }

        $message = __('Workers assigned: :assigned', ['assigned' => $assigned]);
        if ($skipped > 0) {
            $message .= '. ' . __('Skipped (already working): :skipped', ['skipped' => $skipped]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Bulk dismiss workers from their work places
     */
    public function bulkDismiss(Request $request)
    {
        if (!Auth::user()->can('manage work place')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = Validator::make($request->all(), [
            'worker_ids' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        $workerIds = explode(',', $request->worker_ids);
        $dismissed = 0;

        foreach ($workerIds as $workerId) {
            $worker = Worker::where('id', $workerId)
                ->where('created_by', Auth::user()->creatorId())
                ->first();

            if (!$worker) continue;

            $assignment = $worker->currentWorkAssignment;
            if ($assignment) {
                $assignment->ended_at = now();
                $assignment->save();
                $dismissed++;
            }
        }

        return redirect()->back()->with('success', __('Workers dismissed: :count', ['count' => $dismissed]));
    }

    /**
     * Bulk checkout workers from their rooms
     */
    public function bulkCheckout(Request $request)
    {
        if (!Auth::user()->can('manage worker')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = Validator::make($request->all(), [
            'worker_ids' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        $workerIds = explode(',', $request->worker_ids);
        $checkedOut = 0;

        foreach ($workerIds as $workerId) {
            $worker = Worker::where('id', $workerId)
                ->where('created_by', Auth::user()->creatorId())
                ->first();

            if (!$worker) continue;

            $assignment = $worker->currentAssignment;
            if ($assignment) {
                $assignment->check_out_date = now();
                $assignment->save();
                $checkedOut++;
            }
        }

        return redirect()->back()->with('success', __('Workers checked out: :count', ['count' => $checkedOut]));
    }

    /**
     * Bulk assign responsible person to workers
     */
    public function bulkAssignResponsible(Request $request)
    {
        $user = Auth::user();
        
        // Only managers can assign responsible
        if (!$user->isManager()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = Validator::make($request->all(), [
            'worker_ids' => 'required|string',
            'responsible_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        // Verify the responsible person is one of manager's curators
        $responsibleId = $request->responsible_id;
        $curatorIds = $user->assignedCurators->pluck('id')->toArray();
        
        if (!in_array($responsibleId, $curatorIds)) {
            return redirect()->back()->with('error', __('Invalid responsible person selected.'));
        }

        $workerIds = explode(',', $request->worker_ids);
        $updated = 0;

        foreach ($workerIds as $workerId) {
            $worker = Worker::where('id', $workerId)
                ->where('created_by', $user->creatorId())
                ->first();

            if (!$worker) continue;

            $worker->responsible_id = $responsibleId;
            $worker->save();
            $updated++;
        }

        return redirect()->back()->with('success', __('Responsible assigned to :count workers', ['count' => $updated]));
    }
}
