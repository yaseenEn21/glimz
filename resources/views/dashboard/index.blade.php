@extends('base.layout.app')

@push('custom-style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-main: #F8F9FA;
            --card-bg: #FFFFFF;
            --text-primary: #1A1D1F;
            --text-secondary: #6C757D;
            --border-color: #E9ECEF;
            --accent-blue: #4A90E2;
            --accent-green: #27AE60;
            --accent-orange: #F39C12;
            --accent-red: #E74C3C;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        body {
            background: var(--bg-main);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-primary);
        }

        /* Date Filter */
        .date-filter-section {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 16px 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .filter-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .date-input {
            padding: 10px 16px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 14px;
            min-width: 260px;
            cursor: pointer;
            transition: border-color 0.2s;
        }

        .date-input:hover,
        .date-input:focus {
            border-color: var(--accent-blue);
            outline: none;
        }

        .filter-btn {
            padding: 10px 24px;
            background: var(--accent-blue);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            background: #3a7bc8;
            transform: translateY(-1px);
        }

        .filter-btn:disabled {
            background: var(--border-color);
            cursor: not-allowed;
            transform: none;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-box {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-box:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .stat-footer {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .progress-bar {
            flex: 1;
            height: 4px;
            background: var(--border-color);
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--accent-red);
            transition: width 0.3s;
        }

        .trend-positive {
            color: var(--accent-green);
        }

        .trend-negative {
            color: var(--accent-red);
        }

        /* Main Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Schedule Card */
        .schedule-box {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }

        .schedule-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .schedule-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .schedule-subtitle {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        /* Calendar */
        .calendar-strip {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 12px;
            margin-bottom: 24px;
        }

        .calendar-strip::-webkit-scrollbar {
            height: 4px;
        }

        .calendar-strip::-webkit-scrollbar-track {
            background: var(--border-color);
            border-radius: 2px;
        }

        .calendar-strip::-webkit-scrollbar-thumb {
            background: var(--text-secondary);
            border-radius: 2px;
        }

        .day-cell {
            min-width: 56px;
            padding: 12px 8px;
            text-align: center;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            background: #F8F9FA;
        }

        .day-cell:hover {
            background: #E9ECEF;
        }

        .day-cell.today {
            background: var(--accent-red);
            color: white;
        }

        .day-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            opacity: 0.7;
            margin-bottom: 4px;
        }

        .day-date {
            font-size: 18px;
            font-weight: 700;
        }

        /* Bookings */
        .booking-item {
            display: flex;
            align-items: flex-start;
            padding: 16px;
            margin-bottom: 12px;
            transition: all 0.2s;
            border-right: 3px solid transparent;
        }

        .booking-item.pending {
            background: #FFF8DD;
            border-right-color: #FFA800;
        }

        .booking-item.confirmed {
            background: #E1F0FF;
            border-right-color: #009EF7;
        }

        .booking-item.moving {
            background: #F1E6FF;
            border-right-color: #7239EA;
        }

        .booking-item.arrived {
            background: #D6F5F5;
            border-right-color: #00A3A1;
        }

        .booking-item.completed {
            background: #E8FFF3;
            border-right-color: #50CD89;
        }

        .booking-item.cancelled {
            background: #FFE2E5;
            border-right-color: #F1416C;
        }

        .booking-info {
            flex: 1;
        }

        .booking-time {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .booking-time .period {
            font-size: 11px;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .booking-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .booking-client {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .booking-btn {
            padding: 8px 16px;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .booking-btn:hover {
            background: var(--accent-blue);
            color: white;
            border-color: var(--accent-blue);
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: var(--text-secondary);
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        /* Sidebar */
        .sidebar-box {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--shadow-sm);
        }

        .sidebar-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 20px;
        }

        .metric-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .metric-row:last-child {
            border-bottom: none;
        }

        .metric-label {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .metric-value {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* Loading */
        .loading {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(4px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading.show {
            display: flex;
        }

        .loader {
            width: 40px;
            height: 40px;
            border: 3px solid var(--border-color);
            border-top-color: var(--accent-blue);
            border-radius: 50%;
            animation: rotate 0.8s linear infinite;
        }

        @keyframes rotate {
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-number {
                font-size: 28px;
            }

            .date-filter-section {
                flex-direction: column;
                align-items: stretch;
            }

            .date-input {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="loading" id="loading">
        <div class="loader"></div>
    </div>

    <div>
        <!-- Date Filter -->
        <div class="date-filter-section">
            <span class="filter-label">الفترة:</span>
            <input type="text" id="dateRange" class="date-input" placeholder="اختر الفترة" readonly>
            <button id="applyFilter" class="filter-btn">تطبيق</button>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <!-- Active Bookings -->
            <div class="stat-box">
                <div class="stat-number" id="activeCount">—</div>
                <div class="stat-label">الحجوزات النشطة</div>
                <div class="stat-footer">
                    <div class="progress-bar">
                        <div class="progress-fill" id="activeProgress" style="width: 0%"></div>
                    </div>
                    <span id="activePercent">0%</span>
                </div>
            </div>

            <!-- Revenue -->
            <div class="stat-box">
                <div class="stat-number" id="revenue">
                    <span style="font-size: 18px; color: var(--text-secondary)">SR</span> —
                </div>
                <div class="stat-label">إيرادات الفترة</div>
                <div class="stat-footer">
                    <span id="revenueTrend">—</span>
                    <span>من الفترة السابقة</span>
                </div>
            </div>

            <!-- Active Customers -->
            <div class="stat-box">
                <div class="stat-number" id="activeCustomers">—</div>
                <div class="stat-label">المستخدمون النشطون</div>
                <div class="stat-footer">
                    <span>لديهم على الأقل حجز واحد خلال الفترة</span>
                </div>
            </div>

            <!-- Average Rating -->
            <div class="stat-box">
                <div class="stat-number" id="avgRating">—<span
                        style="font-size: 18px; color: var(--text-secondary)">/5</span></div>
                <div class="stat-label">متوسط التقييم</div>
                <div class="stat-footer">
                    <span id="ratingTrend">—</span>
                    <span>من الفترة السابقة</span>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-grid">
            <!-- Schedule -->
            <div class="schedule-box">
                <div class="schedule-header">
                    <div>
                        <div class="schedule-title">الحجوزات القادمة</div>
                        <div class="schedule-subtitle" id="scheduleSubtitle">جاري التحميل...</div>
                    </div>
                </div>

                <!-- Calendar -->
                <div class="calendar-strip" id="calendar"></div>

                <!-- Bookings -->
                <div id="bookings"></div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar-box">
                <div class="sidebar-title">ملخص اليوم</div>

                <div class="metric-row">
                    <span class="metric-label">مكتملة</span>
                    <span class="metric-value" id="dailyCompleted">—</span>
                </div>

                <div class="metric-row">
                    <span class="metric-label">ملغاة</span>
                    <span class="metric-value" id="dailyCancelled">—</span>
                </div>

                <div class="metric-row">
                    <span class="metric-label">معلقة</span>
                    <span class="metric-value" id="dailyPending">—</span>
                </div>

                <div class="metric-row">
                    <span class="metric-label">الإيرادات</span>
                    <span class="metric-value" id="dailyRevenue">—</span>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('custom-script')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ar.js"></script>
    <script>
        (function() {
            const loading = document.getElementById('loading');
            let selectedDate = null;
            let calendarDates = [];
            let currentDateRange = {
                from: null,
                to: null
            };

            function show() {
                loading.classList.add('show');
            }

            function hide() {
                loading.classList.remove('show');
            }

            // Initialize Flatpickr
            const fp = flatpickr("#dateRange", {
                mode: "range",
                dateFormat: "Y-m-d",
                locale: "ar",
                defaultDate: [
                    new Date(new Date().getFullYear(), new Date().getMonth(), 1),
                    new Date()
                ],
                onChange: function(selectedDates) {
                    if (selectedDates.length === 2) {
                        currentDateRange.from = formatDate(selectedDates[0]);
                        currentDateRange.to = formatDate(selectedDates[1]);
                    }
                }
            });

            // Set initial date range
            const fpDates = fp.selectedDates;
            currentDateRange.from = formatDate(fpDates[0]);
            currentDateRange.to = formatDate(fpDates[1]);

            // Format date to YYYY-MM-DD
            function formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            // Generate calendar
            function generateCalendar() {
                const days = ['أحد', 'إثن', 'ثلا', 'أرب', 'خمي', 'جمع', 'سبت'];
                const calendar = document.getElementById('calendar');
                const today = new Date();
                calendar.innerHTML = '';
                calendarDates = [];

                for (let i = 0; i < 11; i++) {
                    const d = new Date(today);
                    d.setDate(today.getDate() + i);
                    const dateStr = formatDate(d);
                    calendarDates.push(dateStr);

                    const cell = document.createElement('div');
                    cell.className = 'day-cell' + (i === 0 ? ' today' : '');
                    cell.dataset.date = dateStr;
                    cell.innerHTML = `
                        <div class="day-label">${days[d.getDay()]}</div>
                        <div class="day-date">${d.getDate()}</div>
                    `;

                    cell.addEventListener('click', function() {
                        document.querySelectorAll('.day-cell').forEach(c => c.classList.remove('today'));
                        this.classList.add('today');
                        selectedDate = this.dataset.date;
                        loadBookingsByDate(selectedDate);
                    });

                    calendar.appendChild(cell);
                }

                selectedDate = calendarDates[0];
            }

            // Load stats
            async function loadStats() {
                show();
                try {
                    const res = await fetch(
                        `/dashboard/stats?from=${currentDateRange.from}&to=${currentDateRange.to}`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });

                    if (!res.ok) throw new Error();

                    const data = await res.json();
                    updateStatsCards(data);
                } catch (e) {
                    console.error('Error loading stats:', e);
                } finally {
                    hide();
                }
            }

            // Update stats cards
            function updateStatsCards(data) {
                
                data = data.data;
                
                // Active Bookings
                document.getElementById('activeCount').textContent = data.active_bookings || 0;
                const percent = data.active_bookings_percent || 0;
                document.getElementById('activePercent').textContent = percent + '%';
                document.getElementById('activeProgress').style.width = percent + '%';

                // Revenue
                const revenue = data.revenue || 0;
                document.getElementById('revenue').innerHTML =
                    `<span style="font-size: 18px; color: var(--text-secondary)">SR</span> ${revenue.toLocaleString()}`;

                const revenueTrend = data.revenue_trend || 0;
                const revenueTrendClass = revenueTrend >= 0 ? 'trend-positive' : 'trend-negative';
                const revenueTrendIcon = revenueTrend >= 0 ? '↗' : '↘';
                document.getElementById('revenueTrend').innerHTML =
                    `<span class="${revenueTrendClass}">${revenueTrendIcon} ${Math.abs(revenueTrend).toFixed(1)}%</span>`;

                // Active Customers
                const customers = data.active_customers || 0;
                document.getElementById('activeCustomers').textContent = customers;
                document.getElementById('customersMore').textContent = customers > 4 ? `+${customers - 4}` : '';

                // Average Rating
                const rating = data.avg_rating || 0;
                document.getElementById('avgRating').innerHTML =
                    `${rating}<span style="font-size: 18px; color: var(--text-secondary)">/5</span>`;

                const ratingTrend = data.rating_trend || 0;
                const ratingTrendClass = ratingTrend >= 0 ? 'trend-positive' : 'trend-negative';
                const ratingTrendIcon = ratingTrend >= 0 ? '↗' : '↘';
                document.getElementById('ratingTrend').innerHTML =
                    `<span class="${ratingTrendClass}">${ratingTrendIcon} ${Math.abs(ratingTrend).toFixed(1)}</span>`;
            }

            // Load bookings by date
            async function loadBookingsByDate(date) {
                show();
                try {
                    const res = await fetch(`/dashboard/upcoming-bookings?date=${date}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    if (!res.ok) throw new Error();

                    const data = await res.json();
                    render(data.bookings || []);
                    updateDailyStats(data.stats || {});

                    const dateObj = new Date(date);
                    const formatted = dateObj.toLocaleDateString('ar-SA', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                    document.getElementById('scheduleSubtitle').textContent = `حجوزات يوم ${formatted}`;
                } catch (e) {
                    console.error('Error loading bookings:', e);
                    render([]);
                } finally {
                    hide();
                }
            }

            // Load initial bookings (today's bookings)
            async function loadInitialBookings() {
                const todayDate = formatDate(new Date());
                await loadBookingsByDate(todayDate);
            }

            function render(list) {
                const container = document.getElementById('bookings');

                if (!list || list.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-state-icon">📅</div>
                            <div>لا توجد حجوزات في هذا اليوم</div>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = list.map(b => `
                    <div class="booking-item ${b.status}">
                        <div class="booking-info">
                            <div class="booking-time">
                                ${b.time} <span class="period">${b.period}</span>
                            </div>
                            <div class="booking-title">${b.title}</div>
                            <div class="booking-client">العميل: ${b.meta}</div>
                        </div>
                        <a href="/dashboard/bookings/${b.id}" class="booking-btn">
                            عرض
                        </a>
                    </div>
                `).join('');
            }

            function updateDailyStats(s) {
                console.log(s.revenue);
                
                document.getElementById('dailyCompleted').textContent = s.completed || '0';
                document.getElementById('dailyCancelled').textContent = s.cancelled || '0';
                document.getElementById('dailyPending').textContent = s.pending || '0';
                document.getElementById('dailyRevenue').textContent = s.revenue || 'SR 0';
            }

            // Apply filter button
            document.getElementById('applyFilter').addEventListener('click', function() {
                loadStats();
            });

            // Initialize
            generateCalendar();
            loadStats();
            loadInitialBookings();
        })();
    </script>
@endpush
