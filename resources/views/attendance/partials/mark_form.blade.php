<div class="modal fade" id="markModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('attendance.mark') }}" method="POST" id="markForm">
                @csrf
                <input type="hidden" name="worker_id" id="markWorkerId">
                <input type="hidden" name="date" value="{{ $date }}">
                
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Mark Attendance') }}: <span id="markWorkerName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3">
                        <small>{{ __('Planned shift') }}: <strong id="plannedShift"></strong></small>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">{{ __('Check In') }}</label>
                            <input type="time" name="check_in" id="markCheckIn" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">{{ __('Check Out') }}</label>
                            <input type="time" name="check_out" id="markCheckOut" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Worked Hours') }}</label>
                        <div class="input-group">
                            <input type="text" id="calculatedHours" class="form-control" readonly>
                            <span class="input-group-text">{{ __('hours') }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Status') }}</label>
                        <div class="d-flex gap-2 flex-wrap">
                            <div class="form-check">
                                <input type="radio" name="status" value="present" class="form-check-input" id="statusPresent" checked>
                                <label class="form-check-label" for="statusPresent">
                                    <span class="badge bg-success">{{ __('Present') }}</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="status" value="late" class="form-check-input" id="statusLate">
                                <label class="form-check-label" for="statusLate">
                                    <span class="badge bg-warning text-dark">{{ __('Late') }}</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="status" value="absent" class="form-check-input" id="statusAbsent">
                                <label class="form-check-label" for="statusAbsent">
                                    <span class="badge bg-danger">{{ __('Absent') }}</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="status" value="sick" class="form-check-input" id="statusSick">
                                <label class="form-check-label" for="statusSick">
                                    <span class="badge bg-info">{{ __('Sick') }}</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="status" value="vacation" class="form-check-input" id="statusVacation">
                                <label class="form-check-label" for="statusVacation">
                                    <span class="badge bg-secondary">{{ __('Vacation') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <textarea name="notes" id="markNotes" class="form-control" rows="2" 
                                  placeholder="{{ __('Optional notes...') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check"></i> {{ __('Save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script-page')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('markModal');
    var checkInInput = document.getElementById('markCheckIn');
    var checkOutInput = document.getElementById('markCheckOut');
    var calculatedHours = document.getElementById('calculatedHours');
    var statusRadios = document.querySelectorAll('input[name="status"]');

    modal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        
        document.getElementById('markWorkerId').value = button.dataset.workerId;
        document.getElementById('markWorkerName').textContent = button.dataset.workerName;
        document.getElementById('plannedShift').textContent = button.dataset.shiftStart + ' - ' + button.dataset.shiftEnd;
        
        checkInInput.value = button.dataset.checkIn || button.dataset.shiftStart;
        checkOutInput.value = button.dataset.checkOut || button.dataset.shiftEnd;
        document.getElementById('markNotes').value = button.dataset.notes || '';
        
        var status = button.dataset.status || 'present';
        document.querySelector('input[name="status"][value="' + status + '"]').checked = true;
        
        calculateHours();
        toggleTimeFields(status);
    });

    function calculateHours() {
        var checkIn = checkInInput.value;
        var checkOut = checkOutInput.value;
        
        if (checkIn && checkOut) {
            var start = new Date('2000-01-01 ' + checkIn);
            var end = new Date('2000-01-01 ' + checkOut);
            
            if (end < start) {
                end.setDate(end.getDate() + 1);
            }
            
            var diff = (end - start) / 1000 / 60 / 60;
            calculatedHours.value = diff.toFixed(1);
        } else {
            calculatedHours.value = '';
        }
    }

    function toggleTimeFields(status) {
        var isDisabled = (status === 'sick' || status === 'vacation' || status === 'absent');
        
        checkInInput.disabled = isDisabled;
        checkOutInput.disabled = isDisabled;
        
        if (isDisabled) {
            checkInInput.value = '';
            checkOutInput.value = '';
            calculatedHours.value = '0';
        }
    }

    checkInInput.addEventListener('change', calculateHours);
    checkOutInput.addEventListener('change', calculateHours);
    
    statusRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            toggleTimeFields(this.value);
            if (this.value !== 'sick' && this.value !== 'vacation' && this.value !== 'absent') {
                var plannedShift = document.getElementById('plannedShift').textContent;
                var times = plannedShift.split(' - ');
                if (times.length === 2) {
                    checkInInput.value = times[0].trim();
                    checkOutInput.value = times[1].trim();
                    calculateHours();
                }
            }
        });
    });
});
</script>
@endpush
