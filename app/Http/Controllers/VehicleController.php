<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Worker;
use App\Models\User;
use App\Services\VehicleDocumentScannerService;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    public function index()
    {
        if (!Auth::user()->can('vehicle_read')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }

        $vehicles = Vehicle::forCurrentUser()
            ->with('latestInspection', 'assignedPerson')
            ->orderBy('brand')
            ->paginate(15);

        return view('vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        if (!Auth::user()->can('vehicle_create')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }

        $workers = Worker::where('created_by', Auth::user()->creatorId())
            ->orderBy('first_name')
            ->get();
        
        $users = User::where('created_by', Auth::user()->creatorId())
            ->orWhere('id', Auth::user()->creatorId())
            ->orderBy('name')
            ->get();

        return view('vehicles.create', compact('workers', 'users'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('vehicle_create')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }

        // Check plan limit
        if (!PlanLimitService::canCreateVehicle()) {
            return redirect()->back()->with('error', __('Vehicle limit reached for your plan.'));
        }

        $request->validate([
            'license_plate' => 'required|string|max:20',
            'brand' => 'required|string|max:100',
            'color' => 'nullable|string|max:50',
            'vin_code' => 'nullable|string|max:30',
            'registration_date' => 'nullable|date',
            'engine_volume' => 'nullable|integer|min:0|max:99999',
            'passport_fuel_consumption' => 'nullable|numeric|min:0|max:99.9',
            'fuel_consumption' => 'nullable|numeric|min:0|max:99.99',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'assigned_type' => 'nullable|in:worker,user',
            'assigned_id' => 'nullable|integer',
        ]);

        $data = $request->only(['license_plate', 'brand', 'color', 'vin_code', 'registration_date', 'engine_volume', 'passport_fuel_consumption', 'fuel_consumption']);
        $data['created_by'] = Auth::user()->creatorId();

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $fileName = time() . '_' . $request->photo->getClientOriginalName();
            $request->photo->move(public_path('uploads/vehicle_photos'), $fileName);
            $data['photo'] = $fileName;
        }

        // Handle scanned tech passport photos (from scanner)
        if ($request->filled('scanned_tech_passport_front')) {
            $scannedFile = $request->scanned_tech_passport_front;
            if (file_exists(public_path('uploads/vehicle_documents/' . $scannedFile))) {
                $data['tech_passport_front'] = $scannedFile;
            }
        }
        if ($request->filled('scanned_tech_passport_back')) {
            $scannedFile = $request->scanned_tech_passport_back;
            if (file_exists(public_path('uploads/vehicle_documents/' . $scannedFile))) {
                $data['tech_passport_back'] = $scannedFile;
            }
        }

        // Handle assigned person
        if ($request->filled('assigned_type') && $request->filled('assigned_id')) {
            $data['assigned_type'] = $request->assigned_type === 'worker' ? Worker::class : User::class;
            $data['assigned_id'] = $request->assigned_id;
        }

        Vehicle::create($data);

        if ($request->input('redirect_to') === 'mobile') {
            return redirect()->route('mobile.vehicles.index')
                ->with('success', __('Vehicle successfully added'));
        }

        return redirect()->route('vehicles.index')
            ->with('success', __('Vehicle successfully added'));
    }

    public function show(Vehicle $vehicle)
    {
        if (!Auth::user()->can('vehicle_read')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }

        if ($vehicle->created_by !== Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Vehicle not found'));
        }

        $vehicle->load(['inspections', 'assignedPerson']);

        return view('vehicles.show', compact('vehicle'));
    }

    public function edit(Vehicle $vehicle)
    {
        if (!Auth::user()->can('vehicle_edit')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }

        if ($vehicle->created_by !== Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Vehicle not found'));
        }

        $workers = Worker::where('created_by', Auth::user()->creatorId())
            ->orderBy('first_name')
            ->get();
        
        $users = User::where('created_by', Auth::user()->creatorId())
            ->orWhere('id', Auth::user()->creatorId())
            ->orderBy('name')
            ->get();

        return view('vehicles.edit', compact('vehicle', 'workers', 'users'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        if (!Auth::user()->can('vehicle_edit')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }

        if ($vehicle->created_by !== Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Vehicle not found'));
        }

        $request->validate([
            'license_plate' => 'required|string|max:20',
            'brand' => 'required|string|max:100',
            'color' => 'nullable|string|max:50',
            'vin_code' => 'nullable|string|max:30',
            'registration_date' => 'nullable|date',
            'engine_volume' => 'nullable|integer|min:0|max:99999',
            'passport_fuel_consumption' => 'nullable|numeric|min:0|max:99.9',
            'fuel_consumption' => 'nullable|numeric|min:0|max:99.99',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'assigned_type' => 'nullable|in:worker,user',
            'assigned_id' => 'nullable|integer',
        ]);

        $data = $request->only(['license_plate', 'brand', 'color', 'vin_code', 'registration_date', 'engine_volume', 'passport_fuel_consumption', 'fuel_consumption']);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo
            if ($vehicle->photo && file_exists(public_path('uploads/vehicle_photos/' . $vehicle->photo))) {
                unlink(public_path('uploads/vehicle_photos/' . $vehicle->photo));
            }
            $fileName = time() . '_' . $request->photo->getClientOriginalName();
            $request->photo->move(public_path('uploads/vehicle_photos'), $fileName);
            $data['photo'] = $fileName;
        }

        // Handle assigned person
        if ($request->filled('assigned_type') && $request->filled('assigned_id')) {
            $data['assigned_type'] = $request->assigned_type === 'worker' ? Worker::class : User::class;
            $data['assigned_id'] = $request->assigned_id;
        } else {
            $data['assigned_type'] = null;
            $data['assigned_id'] = null;
        }

        $vehicle->update($data);

        if ($request->input('redirect_to') === 'mobile') {
            return redirect()->route('mobile.vehicles.show', $vehicle->id)
                ->with('success', __('Vehicle successfully updated'));
        }

        return redirect()->route('vehicles.index')
            ->with('success', __('Vehicle successfully updated'));
    }

    public function destroy(Request $request, Vehicle $vehicle)
    {
        if (!Auth::user()->can('vehicle_delete')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }

        if ($vehicle->created_by !== Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Vehicle not found'));
        }

        // Delete photo
        if ($vehicle->photo && file_exists(public_path('uploads/vehicle_photos/' . $vehicle->photo))) {
            unlink(public_path('uploads/vehicle_photos/' . $vehicle->photo));
        }

        $vehicle->delete();

        if ($request->input('redirect_to') === 'mobile') {
            return redirect()->route('mobile.vehicles.index')
                ->with('success', __('Vehicle successfully deleted'));
        }

        return redirect()->route('vehicles.index')
            ->with('success', __('Vehicle successfully deleted'));
    }

    /**
     * Scan vehicle registration document (tech passport) using AI
     * Supports multiple images (front and back sides)
     * Also saves scanned documents for later attachment to vehicle
     */
    public function scanDocument(Request $request)
    {
        if (!Auth::user()->can('vehicle_create')) {
            return response()->json(['error' => __('Insufficient permissions')], 403);
        }

        $validator = Validator::make($request->all(), [
            'document_images' => 'required|array|min:1|max:4',
            'document_images.*' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $imagePaths = [];
            $uploadedFiles = $request->file('document_images');
            
            foreach ($uploadedFiles as $image) {
                $imagePaths[] = $image->getRealPath();
            }

            $scanner = new VehicleDocumentScannerService();
            $data = $scanner->scanDocument($imagePaths);

            if (isset($data['error'])) {
                return response()->json(['error' => $data['error']], 422);
            }

            // Save scanned documents to uploads folder
            $savedFiles = [];
            $uploadDir = public_path('uploads/vehicle_documents');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            foreach ($uploadedFiles as $index => $image) {
                $fileName = time() . '_' . ($index + 1) . '_scan_' . $image->getClientOriginalName();
                $image->move($uploadDir, $fileName);
                $savedFiles[] = $fileName;
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'scanned_documents' => [
                    'front' => $savedFiles[0] ?? null,
                    'back' => $savedFiles[1] ?? null,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => __('Scan error: ') . $e->getMessage()
            ], 500);
        }
    }
}
