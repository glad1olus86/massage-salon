<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Models\VehicleTrip;
use App\Models\VehicleTrackPoint;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateTestGpsData extends Command
{
    protected $signature = 'gps:generate-test-data 
                            {--company= : Company ID (created_by)}
                            {--vehicle= : Vehicle ID (optional, uses first vehicle of company)}
                            {--user= : User ID (optional, uses first user of company)}';

    protected $description = 'Generate test GPS tracking data around Dnipro city';

    // Dnipro city center and surrounding points
    private array $dniproRoute = [
        // Start: Central Railway Station
        ['lat' => 48.4647, 'lng' => 35.0462],
        // Karl Marx Avenue (now Dmytro Yavornytsky)
        ['lat' => 48.4620, 'lng' => 35.0480],
        ['lat' => 48.4590, 'lng' => 35.0510],
        // European Square
        ['lat' => 48.4565, 'lng' => 35.0540],
        ['lat' => 48.4540, 'lng' => 35.0570],
        // Towards Monastery Island
        ['lat' => 48.4510, 'lng' => 35.0600],
        ['lat' => 48.4480, 'lng' => 35.0650],
        // Along the river
        ['lat' => 48.4450, 'lng' => 35.0700],
        ['lat' => 48.4420, 'lng' => 35.0750],
        ['lat' => 48.4400, 'lng' => 35.0800],
        // Turn towards Gagarin Avenue
        ['lat' => 48.4380, 'lng' => 35.0750],
        ['lat' => 48.4360, 'lng' => 35.0700],
        ['lat' => 48.4340, 'lng' => 35.0650],
        // Gagarin Avenue
        ['lat' => 48.4320, 'lng' => 35.0600],
        ['lat' => 48.4300, 'lng' => 35.0550],
        ['lat' => 48.4280, 'lng' => 35.0500],
        // Towards Pobeda district
        ['lat' => 48.4260, 'lng' => 35.0450],
        ['lat' => 48.4240, 'lng' => 35.0400],
        ['lat' => 48.4220, 'lng' => 35.0350],
        // Loop back
        ['lat' => 48.4250, 'lng' => 35.0300],
        ['lat' => 48.4300, 'lng' => 35.0280],
        ['lat' => 48.4350, 'lng' => 35.0300],
        ['lat' => 48.4400, 'lng' => 35.0350],
        ['lat' => 48.4450, 'lng' => 35.0400],
        // Back to center
        ['lat' => 48.4500, 'lng' => 35.0420],
        ['lat' => 48.4550, 'lng' => 35.0450],
        ['lat' => 48.4600, 'lng' => 35.0470],
        // End near station
        ['lat' => 48.4640, 'lng' => 35.0465],
    ];

    public function handle(): int
    {
        $companyId = $this->option('company');
        
        if (!$companyId) {
            $this->error('Please provide --company option');
            return 1;
        }

        // Find vehicle
        $vehicleId = $this->option('vehicle');
        if ($vehicleId) {
            $vehicle = Vehicle::find($vehicleId);
        } else {
            $vehicle = Vehicle::where('created_by', $companyId)->first();
        }

        if (!$vehicle) {
            $this->error("No vehicle found for company {$companyId}");
            return 1;
        }

        // Find user
        $userId = $this->option('user');
        if ($userId) {
            $user = User::find($userId);
        } else {
            $user = User::where('created_by', $companyId)->first();
            if (!$user) {
                $user = User::find($companyId); // Maybe company owner
            }
        }

        if (!$user) {
            $this->error("No user found for company {$companyId}");
            return 1;
        }

        $this->info("Generating test GPS data...");
        $this->info("Company: {$companyId}");
        $this->info("Vehicle: {$vehicle->id} ({$vehicle->license_plate})");
        $this->info("User: {$user->id} ({$user->name})");

        // Create trip for today
        $startTime = Carbon::today()->setHour(8)->setMinute(30);
        $endTime = Carbon::today()->setHour(10)->setMinute(15);

        $trip = VehicleTrip::create([
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'started_at' => $startTime,
            'ended_at' => $endTime,
            'total_distance_km' => 0,
            'created_by' => $companyId,
        ]);

        $this->info("Created trip ID: {$trip->id}");

        // Generate track points
        $points = [];
        $currentTime = $startTime->copy();
        $totalPoints = count($this->dniproRoute);
        $timePerPoint = $endTime->diffInSeconds($startTime) / $totalPoints;

        foreach ($this->dniproRoute as $index => $point) {
            // Add some randomness to coordinates (GPS noise)
            $lat = $point['lat'] + (rand(-50, 50) / 100000);
            $lng = $point['lng'] + (rand(-50, 50) / 100000);
            
            // Random speed between 20-60 km/h
            $speed = rand(20, 60);
            
            // Random accuracy 5-15 meters
            $accuracy = rand(5, 15);

            $points[] = [
                'trip_id' => $trip->id,
                'latitude' => round($lat, 7),
                'longitude' => round($lng, 7),
                'speed' => $speed,
                'accuracy' => $accuracy,
                'recorded_at' => $currentTime->copy(),
                'synced_at' => $currentTime->copy()->addSeconds(rand(1, 5)),
                'created_at' => now(),
            ];

            // Add time gap for one point (to test gap visualization)
            if ($index === 15) {
                $currentTime->addMinutes(5); // 5 min gap
            } else {
                $currentTime->addSeconds($timePerPoint + rand(-10, 10));
            }
        }

        // Bulk insert
        DB::table('vehicle_track_points')->insert($points);

        // Calculate and update distance
        $distance = $trip->calculateDistance();
        $trip->update(['total_distance_km' => $distance]);

        $this->info("Created " . count($points) . " track points");
        $this->info("Total distance: {$distance} km");
        $this->info("Done! Check vehicle page: /vehicles/{$vehicle->id}");

        return 0;
    }
}
