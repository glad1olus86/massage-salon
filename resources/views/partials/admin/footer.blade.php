@php
    use App\Models\Utility;
    $setting = \App\Models\Utility::settings();
    $setting_arr = Utility::file_validate();
@endphp
<!-- [ Main Content ] end -->
<footer class="dash-footer">
    <div class="footer-wrapper">
        <div class="py-1">
            <p class="mb-0 text-muted"> &copy;
                {{ date('Y') }} {{ $setting['footer_text'] ? $setting['footer_text'] : config('app.name', 'ERPGo') }}
            </p>
        </div>
    </div>
</footer>


<!-- Warning Section Ends -->
<!-- Required Js -->

<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src="{{ asset('js/jquery.form.js') }}"></script>
<script src="{{ asset('assets/js/plugins/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/perfect-scrollbar.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/simplebar.min.js') }}"></script>


<script src="{{ asset('assets/js/plugins/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/feather.min.js') }}"></script>
<script src="{{ asset('assets/js/dash.js') }}"></script>
<script src="{{ asset('js/moment.min.js') }}"></script>

<script src="{{ asset('assets/js/plugins/bootstrap-switch-button.min.js') }}"></script>

<script src="{{ asset('assets/js/plugins/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/simple-datatables.js') }}"></script>

<!-- Apex Chart -->
<script src="{{ asset('assets/js/plugins/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/main.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/choices.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/flatpickr.min.js') }}"></script>

<script src="{{ asset('js/jscolor.js') }}"></script>

<script src="{{ asset('js/popper.min.js') }}"></script>


<script>
    var file_size = "{{ $setting_arr['max_size'] }}";
    var file_types = "{{ $setting_arr['types'] }}";
    var type_err = "{{ __('Invalid file type. Please select a valid file ('.$setting_arr['types'].').') }}";
    var size_err = "{{ __('File size exceeds the maximum limit of '. $setting_arr['max_size'] / 1024 .'MB.') }}";
</script>
<script>
    var site_currency_symbol_position = '{{ $setting['site_currency_symbol_position'] }}';
    var site_currency_symbol = '{{ $setting['site_currency_symbol'] }}';

</script>
<script>
    // DataTables translations
    var dtLabels = {
        placeholder: "{{ __('Search...') }}",
        perPage: "{{ __('entries per page') }}",
        noRows: "{{ __('No entries found') }}",
        info: "{{ __('Showing {start} to {end} of {rows} entries') }}"
    };
</script>
<script src="{{ asset('js/custom.js') }}?v={{ time() }}"></script>

@if($message = Session::get('success'))
    <script>
        show_toastr('success', '{!! $message !!}');
    </script>
@endif
@if($message = Session::get('error'))
    <script>
        show_toastr('error', '{!! $message !!}');
    </script>
@endif
@if($setting['enable_cookie'] == 'on')
    @include('layouts.cookie_consent')
@endif
@stack('script-page')

@stack('old-datatable-js')

{{-- System Notifications Polling --}}
@if(Auth::check() && Auth::user()->type != 'client')
@php
    $notifSettings = \App\Services\NotificationService::getSettings();
    $pollIntervalMs = ((int)($notifSettings['notification_poll_interval'] ?? 5)) * 60 * 1000;
@endphp
<script>
(function() {
    var notificationBadge = document.getElementById('notification-badge');
    var notificationList = document.getElementById('notification-list');
    var markAllReadBtn = document.getElementById('mark-all-read');
    var pollInterval = {{ $pollIntervalMs }}; // From settings
    var pollTimer = null;
    var checkUrl = '{{ route("notifications.check") }}';
    var markAllUrl = '{{ route("notifications.read.all") }}';
    var csrfToken = '{{ csrf_token() }}';

    // Start polling with dynamic interval
    function startPolling() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(checkNotifications, pollInterval);
        console.log('Notification polling started: every ' + (pollInterval/1000) + ' seconds');
    }

    function updateNotifications(data) {
        if (!notificationBadge || !notificationList) return;

        // Update badge
        if (data.count > 0) {
            notificationBadge.textContent = data.count > 99 ? '99+' : data.count;
            notificationBadge.style.display = 'inline-block';
        } else {
            notificationBadge.style.display = 'none';
        }

        // Update poll interval from server if changed
        if (data.poll_interval && data.poll_interval !== pollInterval) {
            pollInterval = data.poll_interval;
            startPolling(); // Restart with new interval
            console.log('Poll interval updated to: ' + (pollInterval/1000) + ' seconds');
        }

        // Update notification list
        if (data.notifications && data.notifications.length > 0) {
            var html = '';
            data.notifications.forEach(function(n) {
                html += '<a href="' + n.link + '" class="dropdown-item py-2 border-bottom">';
                html += '<div class="d-flex align-items-start">';
                html += '<span class="avatar avatar-sm bg-white border me-2">';
                html += '<i class="' + n.icon + ' text-' + n.color + '"></i></span>';
                html += '<div class="flex-grow-1">';
                html += '<p class="mb-0 fw-medium">' + n.title + '</p>';
                html += '<small class="text-muted" style="white-space: pre-line; display: block;">' + n.message + '</small>';
                html += '<small class="text-muted">' + n.time + '</small>';
                html += '</div></div></a>';
            });
            notificationList.innerHTML = html;
        } else {
            notificationList.innerHTML = '<div class="text-center py-3 text-muted"><i class="ti ti-bell-off"></i> {{ __("No notifications") }}</div>';
        }
    }

    function checkNotifications() {
        fetch(checkUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                updateNotifications(data);
            }
        })
        .catch(function(error) {
            console.log('Notification check error:', error);
        });
    }

    // Mark all as read
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            fetch(markAllUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    checkNotifications();
                }
            });
        });
    }

    // Initial check immediately on page load
    checkNotifications();
    startPolling();
})();
</script>
@endif

<script>




    feather.replace();
    var pctoggle = document.querySelector("#pct-toggler");
    if (pctoggle) {
        pctoggle.addEventListener("click", function () {
            if (
                !document.querySelector(".pct-customizer").classList.contains("active")
            ) {
                document.querySelector(".pct-customizer").classList.add("active");
            } else {
                document.querySelector(".pct-customizer").classList.remove("active");
            }
        });
    }

    var themescolors = document.querySelectorAll(".themes-color > a");
    for (var h = 0; h < themescolors.length; h++) {
        var c = themescolors[h];

        c.addEventListener("click", function (event) {
            var targetElement = event.target;
            if (targetElement.tagName == "SPAN") {
                targetElement = targetElement.parentNode;
            }
            var temp = targetElement.getAttribute("data-value");
            removeClassByPrefix(document.querySelector("body"), "theme-");
            document.querySelector("body").classList.add(temp);
        });
    }

    if ($('#cust-theme-bg').length > 0) {
        var custthemebg = document.querySelector("#cust-theme-bg");
        custthemebg.addEventListener("click", function () {
            if (custthemebg.checked) {
                document.querySelector(".dash-sidebar").classList.add("transprent-bg");
                document
                    .querySelector(".dash-header:not(.dash-mob-header)")
                    .classList.add("transprent-bg");
            } else {
                document.querySelector(".dash-sidebar").classList.remove("transprent-bg");
                document
                    .querySelector(".dash-header:not(.dash-mob-header)")
                    .classList.remove("transprent-bg");
            }
        });
    }




    function removeClassByPrefix(node, prefix) {
        for (let i = 0; i < node.classList.length; i++) {
            let value = node.classList[i];
            if (value.startsWith(prefix)) {
                node.classList.remove(value);
            }
        }
    }
</script>


