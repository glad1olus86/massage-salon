// Global calendar state and functions
console.log('[AuditCalendar] Script loaded');

window.AuditCalendar = {
    currentYear: new Date().getFullYear(),
    currentMonth: new Date().getMonth() + 1,
    initialized: false,
    fetchCalendarData: null,
    
    load: function() {
        console.log('[AuditCalendar] load() called, fetchCalendarData:', !!window.AuditCalendar.fetchCalendarData);
        if (window.AuditCalendar.fetchCalendarData) {
            window.AuditCalendar.fetchCalendarData(
                window.AuditCalendar.currentYear, 
                window.AuditCalendar.currentMonth
            );
        } else {
            console.log('[AuditCalendar] fetchCalendarData not available, trying init first');
            window.AuditCalendar.init();
            if (window.AuditCalendar.fetchCalendarData) {
                window.AuditCalendar.fetchCalendarData(
                    window.AuditCalendar.currentYear, 
                    window.AuditCalendar.currentMonth
                );
            }
        }
    },
    
    init: function() {
        console.log('[AuditCalendar] init() called, initialized:', window.AuditCalendar.initialized);
        if (window.AuditCalendar.initialized) return;
        
        const calendarGrid = document.getElementById('calendar-grid');
        console.log('[AuditCalendar] calendar-grid element:', calendarGrid);
        if (!calendarGrid) {
            console.log('[AuditCalendar] calendar-grid not found!');
            return;
        }
        
        window.AuditCalendar.initialized = true;
        initCalendar();
    }
};

function initCalendar() {
    console.log('[AuditCalendar] initCalendar() called');
    
    const calendarGrid = document.getElementById('calendar-grid');
    const monthYearTitle = document.getElementById('calendar-month-year');
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');
    
    console.log('[AuditCalendar] Elements found:', {
        calendarGrid: !!calendarGrid,
        monthYearTitle: !!monthYearTitle,
        prevMonthBtn: !!prevMonthBtn,
        nextMonthBtn: !!nextMonthBtn
    });
    
    // Check if elements exist
    if (!calendarGrid) {
        console.log('[AuditCalendar] calendarGrid not found, aborting');
        return;
    }
    
    const dayDetailsModalEl = document.getElementById('day-details-modal');
    console.log('[AuditCalendar] dayDetailsModalEl:', !!dayDetailsModalEl);
    if (!dayDetailsModalEl) {
        console.log('[AuditCalendar] dayDetailsModalEl not found, aborting');
        return;
    }
    
    const dayDetailsModal = new bootstrap.Modal(dayDetailsModalEl);
    const dayDetailsBody = document.getElementById('day-details-body');
    const dayDetailsTitle = document.getElementById('day-details-title');

    let currentYear = window.AuditCalendar.currentYear;
    let currentMonth = window.AuditCalendar.currentMonth;
    
    console.log('[AuditCalendar] Calendar initialized for', currentYear, currentMonth);

    // Event colors mapping
    const eventColors = {
        'worker.created': '#28a745',
        'worker.updated': '#17a2b8',
        'worker.deleted': '#6c757d',
        'worker.checked_in': '#007bff',
        'worker.checked_out': '#fd7e14',
        'worker.hired': '#6f42c1',
        'worker.dismissed': '#dc3545',
        'room.created': '#20c997',
        'room.updated': '#17a2b8',
        'room.deleted': '#6c757d',
        'work_place.created': '#20c997',
        'work_place.updated': '#17a2b8',
        'work_place.deleted': '#6c757d',
        'hotel.created': '#28a745',
        'hotel.updated': '#17a2b8',
        'hotel.deleted': '#6c757d',
    };

    function fetchCalendarData(year, month) {
        console.log('[AuditCalendar] fetchCalendarData called:', year, month);
        // Show loading state
        calendarGrid.style.opacity = '0.5';

        fetch(`/audit/calendar/${year}/${month}`)
            .then(response => {
                console.log('[AuditCalendar] Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('[AuditCalendar] Data received:', data);
                renderCalendar(data);
                calendarGrid.style.opacity = '1';
                console.log('[AuditCalendar] Calendar rendered');
            })
            .catch(error => {
                console.error('[AuditCalendar] Error fetching calendar data:', error);
                calendarGrid.style.opacity = '1';
            });
    }
    
    // Make fetchCalendarData available globally
    window.AuditCalendar.fetchCalendarData = fetchCalendarData;

    function renderCalendar(data) {
        calendarGrid.innerHTML = '';

        const date = new Date(data.year, data.month - 1, 1);
        const monthName = date.toLocaleString('ru-RU', { month: 'long', year: 'numeric' });
        monthYearTitle.textContent = monthName;

        // Day headers
        const daysOfWeek = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        daysOfWeek.forEach(day => {
            const header = document.createElement('div');
            header.className = 'calendar-day-header';
            header.textContent = day;
            calendarGrid.appendChild(header);
        });

        // Empty cells before first day
        let firstDay = date.getDay(); // 0 (Sun) - 6 (Sat)
        firstDay = firstDay === 0 ? 6 : firstDay - 1; // Convert to Mon (0) - Sun (6)

        for (let i = 0; i < firstDay; i++) {
            const emptyCell = document.createElement('div');
            emptyCell.className = 'calendar-day empty';
            calendarGrid.appendChild(emptyCell);
        }

        // Days
        const daysInMonth = new Date(data.year, data.month, 0).getDate();

        for (let i = 1; i <= daysInMonth; i++) {
            const dayCell = document.createElement('div');
            dayCell.className = 'calendar-day';

            // Check if today
            const today = new Date();
            if (today.getDate() === i && today.getMonth() + 1 === data.month && today.getFullYear() === data.year) {
                dayCell.classList.add('today');
            }

            const dayNumber = document.createElement('span');
            dayNumber.className = 'day-number';
            dayNumber.textContent = i;
            dayCell.appendChild(dayNumber);

            // Events dots
            if (data.days[i]) {
                const eventsContainer = document.createElement('div');
                eventsContainer.className = 'event-dots';

                let dotCount = 0;
                const maxDots = 10;

                for (const [eventType, count] of Object.entries(data.days[i].events)) {
                    for (let j = 0; j < count; j++) {
                        if (dotCount < maxDots) {
                            const dot = document.createElement('span');
                            dot.className = 'event-dot';
                            dot.style.backgroundColor = eventColors[eventType] || '#6c757d';
                            dot.title = eventType;
                            eventsContainer.appendChild(dot);
                            dotCount++;
                        }
                    }
                }

                if (data.days[i].total > maxDots) {
                    const more = document.createElement('span');
                    more.className = 'more-events';
                    more.textContent = `+${data.days[i].total - maxDots}`;
                    eventsContainer.appendChild(more);
                }

                dayCell.appendChild(eventsContainer);
            }

            // Click handler
            dayCell.addEventListener('click', () => {
                openDayDetails(data.year, data.month, i);
            });

            calendarGrid.appendChild(dayCell);
        }
    }

    function openDayDetails(year, month, day) {
        const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const formattedDate = new Date(year, month - 1, day).toLocaleDateString('ru-RU', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });

        dayDetailsTitle.textContent = `События за ${formattedDate}`;
        dayDetailsBody.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

        dayDetailsModal.show();

        fetch(`/audit/day/${dateStr}`)
            .then(response => response.text())
            .then(html => {
                dayDetailsBody.innerHTML = html;
            })
            .catch(error => {
                console.error('Error fetching day details:', error);
                dayDetailsBody.innerHTML = '<div class="alert alert-danger">Ошибка загрузки данных</div>';
            });
    }

    // Navigation handlers
    prevMonthBtn.addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 1) {
            currentMonth = 12;
            currentYear--;
        }
        fetchCalendarData(currentYear, currentMonth);
    });

    nextMonthBtn.addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 12) {
            currentMonth = 1;
            currentYear++;
        }
        fetchCalendarData(currentYear, currentMonth);
    });

    // Initial load - always load calendar data when initCalendar is called
    // The visibility is controlled by the tab switching logic in index.blade.php
    console.log('[AuditCalendar] Initial load - fetching calendar data');
    fetchCalendarData(currentYear, currentMonth);
}

// Don't auto-initialize - let the page control when to init
// This prevents double initialization issues
console.log('[AuditCalendar] Script ready, waiting for init() call');
