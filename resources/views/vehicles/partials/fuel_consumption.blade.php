{{-- Fuel Consumption Card - synced with tracking map date --}}
<style>
    .fuel-trip-list {
        max-height: 180px;
        overflow-y: auto;
    }
    .fuel-trip-item {
        display: flex;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
        font-size: 13px;
    }
    .fuel-trip-item:last-child {
        border-bottom: none;
    }
    .fuel-trip-num {
        width: 24px;
        height: 24px;
        background: #f8f9fa;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 11px;
        color: #6c757d;
        margin-right: 10px;
        flex-shrink: 0;
    }
    .fuel-trip-num.active {
        background: #198754;
        color: white;
    }
    .fuel-trip-time {
        color: #6c757d;
        min-width: 90px;
    }
    .fuel-trip-stats {
        margin-left: auto;
        display: flex;
        gap: 15px;
        text-align: right;
        padding-right: 15px;
    }
    .fuel-trip-stat {
        min-width: 55px;
    }
    .fuel-trip-stat-value {
        font-weight: 500;
        color: #212529;
    }
    .fuel-trip-stat-unit {
        color: #adb5bd;
        font-size: 11px;
    }
    .fuel-totals {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 12px;
        margin-top: 12px;
    }
    .fuel-total-item {
        text-align: center;
    }
    .fuel-total-value {
        font-size: 18px;
        font-weight: 600;
        color: #212529;
    }
    .fuel-total-label {
        font-size: 11px;
        color: #6c757d;
        text-transform: uppercase;
    }
</style>

<div class="card" id="fuel-consumption-card">
    <div class="card-header">
        <h5 class="mb-0">{{ __('Fuel Consumption') }}</h5>
    </div>
    <div class="card-body py-2">
        <div id="fuel-consumption-table">
            <div class="text-center text-muted py-3">
                <i class="ti ti-gas-station" style="font-size: 32px;"></i>
                <p class="mt-2 mb-0">{{ __('Select date on map to see fuel consumption') }}</p>
            </div>
        </div>
        <div id="fuel-consumption-total" style="display: none;">
            <div class="fuel-totals">
                <div class="row">
                    <div class="col-4 fuel-total-item">
                        <div class="fuel-total-value" id="fuel-total-distance">-</div>
                        <div class="fuel-total-label">{{ __('Distance') }}</div>
                    </div>
                    <div class="col-4 fuel-total-item">
                        <div class="fuel-total-value" id="fuel-total-liters">-</div>
                        <div class="fuel-total-label">{{ __('Fuel') }}</div>
                    </div>
                    <div class="col-4 fuel-total-item">
                        <div class="fuel-total-value" id="fuel-total-trips">-</div>
                        <div class="fuel-total-label">{{ __('Trips') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const fuelConsumption = {{ $vehicle->fuel_consumption ?? 0 }};
    const unitKm = '{{ __("km") }}';
    const unitL = '{{ __("l") }}';
    
    window.addEventListener('tripsDataLoaded', function(e) {
        updateFuelConsumption(e.detail.trips, e.detail.date);
    });
    
    function updateFuelConsumption(trips, date) {
        const tableEl = document.getElementById('fuel-consumption-table');
        const totalEl = document.getElementById('fuel-consumption-total');
        
        if (!trips || trips.length === 0) {
            tableEl.innerHTML = `
                <div class="text-center text-muted py-3">
                    <i class="ti ti-gas-station" style="font-size: 32px;"></i>
                    <p class="mt-2 mb-0">{{ __('No trips for this day') }}</p>
                </div>
            `;
            totalEl.style.display = 'none';
            return;
        }
        
        let totalDistance = 0;
        let totalFuel = 0;
        let validTripsCount = 0;
        
        let html = '<div class="fuel-trip-list">';
        
        trips.forEach((trip, index) => {
            const startTime = new Date(trip.started_at).toLocaleTimeString('uk-UA', {hour: '2-digit', minute: '2-digit'});
            const endTime = trip.ended_at 
                ? new Date(trip.ended_at).toLocaleTimeString('uk-UA', {hour: '2-digit', minute: '2-digit'})
                : '...';
            
            const distance = parseFloat(trip.total_distance_km) || 0;
            const fuel = fuelConsumption > 0 ? (distance * fuelConsumption / 100) : 0;
            
            // Only count trips with actual distance
            if (distance > 0) {
                validTripsCount++;
            }
            
            totalDistance += distance;
            totalFuel += fuel;
            
            const numClass = trip.is_active ? 'fuel-trip-num active' : 'fuel-trip-num';
            const emptyClass = distance === 0 ? ' text-muted' : '';
            
            html += `
                <div class="fuel-trip-item${emptyClass}">
                    <div class="${numClass}">${index + 1}</div>
                    <div class="fuel-trip-time">${startTime} - ${endTime}</div>
                    <div class="fuel-trip-stats">
                        <div class="fuel-trip-stat">
                            <span class="fuel-trip-stat-value">${distance.toFixed(1)}</span>
                            <span class="fuel-trip-stat-unit">${unitKm}</span>
                        </div>
                        <div class="fuel-trip-stat">
                            <span class="fuel-trip-stat-value">${fuel.toFixed(1)}</span>
                            <span class="fuel-trip-stat-unit">${unitL}</span>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        
        tableEl.innerHTML = html;
        
        document.getElementById('fuel-total-distance').textContent = totalDistance.toFixed(1) + ' ' + unitKm;
        document.getElementById('fuel-total-liters').textContent = totalFuel.toFixed(1) + ' ' + unitL;
        document.getElementById('fuel-total-trips').textContent = trips.length + (validTripsCount < trips.length ? ` (${validTripsCount} {{ __('with data') }})` : '');
        totalEl.style.display = 'block';
    }
})();
</script>
