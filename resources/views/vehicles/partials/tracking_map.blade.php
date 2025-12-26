{{-- GPS Tracking Map Component --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    .track-marker .marker-icon {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
        font-weight: bold;
        border: 2px solid white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    }
    .track-marker .marker-icon.bg-success { background-color: #198754; }
    .track-marker .marker-icon.bg-danger { background-color: #dc3545; }
    .track-marker .marker-icon.bg-primary { background-color: #0d6efd; }
    .track-marker.current .marker-icon.pulse {
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(13, 110, 253, 0); }
        100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); }
    }
    #tracking-map {
        z-index: 1;
        background: #f0f0f0;
    }
    #trip-selector {
        min-width: 180px;
        padding-right: 30px;
    }
</style>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">{{ __('Route Map') }}</h5>
        <div class="d-flex align-items-center gap-2">
            <select id="trip-selector" class="form-select form-select-sm" style="display: none;">
            </select>
            <input type="date" id="track-date" class="form-control form-control-sm w-auto" 
                   value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
        </div>
    </div>
    <div class="card-body p-0"  style="min-height: 165px;">
        <div id="tracking-map" style="height: 400px;"></div>
        <div id="no-track-data" class="text-center py-5 d-none">
            <i class="ti ti-map-off" style="font-size: 48px; color: #6c757d;"></i>
            <p class="text-muted mt-2">{{ __('No tracking data for this day') }}</p>
        </div>
    </div>
    <div class="card-footer" id="track-info" style="display: none;">
        <div class="row text-center">
            <div class="col-4">
                <small class="text-muted">{{ __('Start') }}</small>
                <div id="trip-start-time">-</div>
            </div>
            <div class="col-4">
                <small class="text-muted">{{ __('End') }}</small>
                <div id="trip-end-time">-</div>
            </div>
            <div class="col-4">
                <small class="text-muted">{{ __('Distance') }}</small>
                <div id="trip-distance">-</div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function() {
    const vehicleId = {{ $vehicle->id }};
    let map = null;
    let trackLayers = [];
    let markers = [];
    let currentTrips = [];
    let selectedTripId = null;

    function initMap() {
        const mapEl = document.getElementById('tracking-map');
        if (!mapEl || typeof L === 'undefined') {
            console.error('Map element or Leaflet not found');
            return false;
        }
        
        map = L.map('tracking-map').setView([48.46, 35.05], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        return true;
    }

    function clearTrack() {
        if (!map) return;
        trackLayers.forEach(layer => map.removeLayer(layer));
        markers.forEach(marker => map.removeLayer(marker));
        trackLayers = [];
        markers = [];
    }

    function drawSegment(points, color, dashed) {
        const latlngs = points.map(p => [p.lat, p.lng]);
        const options = {
            color: color,
            weight: 4,
            opacity: 0.8
        };
        if (dashed) {
            options.dashArray = '10, 10';
        }
        const polyline = L.polyline(latlngs, options).addTo(map);
        trackLayers.push(polyline);
        return polyline;
    }

    function addMarker(point, type, popupText) {
        let iconHtml, className;
        
        if (type === 'start') {
            iconHtml = '<div class="marker-icon bg-success">▶</div>';
            className = 'track-marker start';
        } else if (type === 'end') {
            iconHtml = '<div class="marker-icon bg-danger">■</div>';
            className = 'track-marker end';
        } else {
            iconHtml = '<div class="marker-icon bg-primary pulse">●</div>';
            className = 'track-marker current';
        }

        const marker = L.marker([point.lat, point.lng], {
            icon: L.divIcon({
                className: className,
                html: iconHtml,
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            })
        }).addTo(map);
        
        if (popupText) {
            marker.bindPopup(popupText);
        }
        
        markers.push(marker);
        return marker;
    }

    function drawTrack(points, trip) {
        if (!points || points.length === 0) return;

        let normalSegment = [];
        
        points.forEach((point, index) => {
            if (point.is_gap && normalSegment.length > 0) {
                drawSegment(normalSegment, '#3388ff', false);
                const gapSegment = [normalSegment[normalSegment.length - 1], point];
                drawSegment(gapSegment, '#ff0000', true);
                normalSegment = [point];
            } else {
                normalSegment.push(point);
            }
        });
        
        if (normalSegment.length > 0) {
            drawSegment(normalSegment, '#3388ff', false);
        }
        
        const startTime = new Date(trip.started_at).toLocaleTimeString('uk-UA', {hour: '2-digit', minute: '2-digit'});
        addMarker(points[0], 'start', '{{ __("Start") }}: ' + startTime);
        
        if (trip.ended_at) {
            const endTime = new Date(trip.ended_at).toLocaleTimeString('uk-UA', {hour: '2-digit', minute: '2-digit'});
            addMarker(points[points.length - 1], 'end', '{{ __("End") }}: ' + endTime);
        } else {
            addMarker(points[points.length - 1], 'current', '{{ __("Current position") }}');
        }
        
        const bounds = points.map(p => [p.lat, p.lng]);
        map.fitBounds(bounds, { padding: [30, 30] });
    }

    function updateTripSelector(trips, currentTripId) {
        const selector = document.getElementById('trip-selector');
        currentTrips = trips;
        
        if (!trips || trips.length <= 1) {
            selector.style.display = 'none';
            return;
        }
        
        selector.innerHTML = '';
        trips.forEach((trip, index) => {
            const option = document.createElement('option');
            option.value = trip.id;
            const time = new Date(trip.started_at).toLocaleTimeString('uk-UA', {hour: '2-digit', minute: '2-digit'});
            option.textContent = trip.label + ' (' + time + ')';
            if (trip.is_active) {
                option.textContent += ' 🟢';
            }
            selector.appendChild(option);
        });
        
        selector.style.display = 'block';
        
        // Set selected value after populating options
        if (currentTripId) {
            selector.value = currentTripId;
        }
    }

    function updateTripInfo(trip) {
        const infoEl = document.getElementById('track-info');
        
        if (!trip) {
            infoEl.style.display = 'none';
            return;
        }
        
        infoEl.style.display = 'block';
        
        const startTime = new Date(trip.started_at).toLocaleTimeString('uk-UA', {hour: '2-digit', minute: '2-digit'});
        document.getElementById('trip-start-time').textContent = startTime;
        
        if (trip.ended_at) {
            const endTime = new Date(trip.ended_at).toLocaleTimeString('uk-UA', {hour: '2-digit', minute: '2-digit'});
            document.getElementById('trip-end-time').textContent = endTime;
        } else {
            document.getElementById('trip-end-time').innerHTML = '<span class="badge bg-success">{{ __("Active") }}</span>';
        }
        
        if (trip.total_distance_km) {
            document.getElementById('trip-distance').textContent = trip.total_distance_km + ' km';
        } else {
            document.getElementById('trip-distance').textContent = '-';
        }
    }

    function showNoData() {
        document.getElementById('tracking-map').classList.add('d-none');
        document.getElementById('no-track-data').classList.remove('d-none');
        document.getElementById('track-info').style.display = 'none';
        document.getElementById('trip-selector').style.display = 'none';
        
        // Dispatch event with empty trips for fuel consumption
        window.dispatchEvent(new CustomEvent('tripsDataLoaded', {
            detail: { trips: [], date: document.getElementById('track-date').value }
        }));
    }

    function showMap() {
        document.getElementById('tracking-map').classList.remove('d-none');
        document.getElementById('no-track-data').classList.add('d-none');
        if (map) map.invalidateSize();
    }

    async function loadTrack(date, tripId = null) {
        try {
            let url = `/vehicles/${vehicleId}/track?date=${date}`;
            if (tripId) {
                url += `&trip_id=${tripId}`;
            }
            
            console.log('Loading track:', url);
            const response = await fetch(url);
            
            if (!response.ok) {
                console.error('Response not ok:', response.status);
                showNoData();
                return;
            }
            
            const data = await response.json();
            console.log('Track data:', data);
            
            clearTrack();
            
            if (!data.points || data.points.length === 0) {
                console.log('No points found');
                showNoData();
                return;
            }
            
            selectedTripId = data.trip.id;
            
            // Update trip selector with current trip ID
            updateTripSelector(data.trips, selectedTripId);
            
            showMap();
            drawTrack(data.points, data.trip);
            updateTripInfo(data.trip);
            
            // Dispatch event for fuel consumption component
            window.dispatchEvent(new CustomEvent('tripsDataLoaded', {
                detail: { trips: data.trips, date: date }
            }));
            
        } catch (error) {
            console.error('Error loading track:', error);
            showNoData();
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        console.log('Initializing tracking map...');
        
        if (!initMap()) {
            console.error('Failed to initialize map');
            return;
        }
        
        const dateInput = document.getElementById('track-date');
        const tripSelector = document.getElementById('trip-selector');
        
        dateInput.addEventListener('change', function() {
            selectedTripId = null; // Reset trip selection on date change
            loadTrack(this.value);
        });
        
        tripSelector.addEventListener('change', function() {
            const date = dateInput.value;
            loadTrack(date, this.value);
        });
        
        // Load today's track
        loadTrack(dateInput.value);
    }
})();
</script>
