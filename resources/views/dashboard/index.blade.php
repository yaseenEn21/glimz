@extends('base.layout.app')

@push('custom-style')
<style>
  #chart_status, #chart_trend, #chart_bikers, #chart_services {
      direction: ltr;
  }
</style>
@endpush


@section('content')
    <div class="card mb-6">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-4">
            <div>
                <h3 class="mb-1 fw-bold">{{ __('dashboard.kpi.title') }}</h3>
                <div class="text-muted">{{ __('dashboard.kpi.subtitle') }}</div>
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <input id="kpi_range" class="form-control form-control-sm" style="width:260px" />
                <button id="kpi_apply" class="btn btn-sm btn-primary">{{ __('dashboard.kpi.actions.refresh') }}</button>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8">
        @php
            $cards = [
                ['id' => 'total_bookings', 'title' => __('dashboard.kpi.cards.total_bookings')],
                ['id' => 'active_bookings', 'title' => __('dashboard.kpi.cards.active_bookings')],
                ['id' => 'completed', 'title' => __('dashboard.kpi.cards.completed')],
                ['id' => 'cancelled', 'title' => __('dashboard.kpi.cards.cancelled')],
                ['id' => 'cancel_rate', 'title' => __('dashboard.kpi.cards.cancel_rate')],
                ['id' => 'package_bookings', 'title' => __('dashboard.kpi.cards.package_bookings')],
                ['id' => 'gross', 'title' => __('dashboard.kpi.cards.gross')],
                ['id' => 'paid', 'title' => __('dashboard.kpi.cards.paid')],
                ['id' => 'unpaid', 'title' => __('dashboard.kpi.cards.unpaid')],
                ['id' => 'avg_ticket', 'title' => __('dashboard.kpi.cards.avg_ticket')],
            ];
        @endphp

        @foreach ($cards as $c)
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card card-flush h-100">
                    <div class="card-body">
                        <div class="text-muted fw-semibold mb-1">{{ $c['title'] }}</div>
                        <div class="fs-2hx fw-bold" id="kpi_{{ $c['id'] }}">—</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-5 g-xl-8 mt-2">
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title fw-bold">{{ __('dashboard.kpi.sections.status_distribution') }}</div>
                </div>
                <div class="card-body">
                    <div id="chart_status" style="height: 320px;"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title fw-bold">{{ __('dashboard.kpi.sections.trend_daily') }}</div>
                </div>
                <div class="card-body">
                    <div id="chart_trend" style="height: 320px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-8 mt-2">
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title fw-bold">{{ __('dashboard.kpi.sections.top_bikers') }}</div>
                </div>
                <div class="card-body">
                    <div id="chart_bikers" style="height: 320px;"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title fw-bold">{{ __('dashboard.kpi.sections.top_services') }}</div>
                </div>
                <div class="card-body">
                    <div id="chart_services" style="height: 320px;"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@php
    $kpiI18n = [
        'series' => [
            'bookings' => __('dashboard.kpi.charts.series.bookings'),
            'paid' => __('dashboard.kpi.charts.series.paid'),
        ],
        'bars' => [
            'bookings_count' => __('dashboard.kpi.charts.bars.bookings_count'),
        ],
    ];
@endphp

@push('custom-script')
    <script>
        const i18n = @json($kpiI18n);
    </script>
    <script>
        (function() {
            const fromDefault = @json($from);
            const toDefault = @json($to);

            // flatpickr range
            const fp = flatpickr("#kpi_range", {
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: [fromDefault, toDefault],
            });

            let statusChart, trendChart, bikersChart, servicesChart, zonesChart;

            async function loadKPI() {
                const dates = fp.selectedDates || [];
                let from = fromDefault,
                    to = toDefault;

                if (dates.length === 2) {
                    from = dates[0].toISOString().slice(0, 10);
                    to = dates[1].toISOString().slice(0, 10);
                }

                const url = new URL(@json(route('dashboard.kpi')));
                url.searchParams.set('from', from);
                url.searchParams.set('to', to);

                const res = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const json = await res.json();
                if (!json || !json.success) return;

                const d = json.data;

                // cards
                const c = d.cards;
                const cur = c.currency || 'SAR';

                const set = (id, val) => {
                    const el = document.getElementById('kpi_' + id);
                    if (!el) return;
                    el.textContent = val;
                };

                set('total_bookings', c.total_bookings);
                set('active_bookings', c.active_bookings);
                set('completed', c.completed);
                set('cancelled', c.cancelled);
                set('cancel_rate', c.cancel_rate);
                set('package_bookings', c.package_bookings);

                const f = d.finance;
                set('gross', `${f.system_invoiced} ${cur}`);
                set('paid', `${f.system_paid} ${cur}`);
                set('unpaid', `${f.system_unpaid} ${cur}`);
                set('avg_ticket', `${c.avg_ticket} ${cur}`);

                // charts
                renderStatus(d.charts.status);
                renderTrend(d.charts.trend);

                renderTopBars(
                    'chart_bikers',
                    d.tops.bikers,
                    'name',
                    'total',
                    (chart) => bikersChart = chart,
                    bikersChart,
                    i18n.bars.bookings_count
                );

                renderTopBars(
                    'chart_services',
                    d.tops.services,
                    'name',
                    'total',
                    (chart) => servicesChart = chart,
                    servicesChart,
                    i18n.bars.bookings_count
                );
            }

            function renderStatus(rows) {
                const labels = rows.map(r => r.label || r.status || r.key);
                const series = rows.map(r => Number(r.total || 0));

                const opts = {
                    chart: {
                        type: 'donut',
                        height: 320
                    },
                    labels,
                    series,
                    legend: {
                        position: 'bottom'
                    }
                };

                if (statusChart) {
                    statusChart.updateOptions(opts);
                    statusChart.updateSeries(series);
                    return;
                }

                statusChart = new ApexCharts(document.querySelector("#chart_status"), opts);
                statusChart.render();
            }

            function renderTrend(trend) {
                const opts = {
                    chart: {
                        type: 'line',
                        height: 320,
                        toolbar: {
                            show: false
                        }
                    },
                    xaxis: {
                        categories: trend.labels
                    },
                    stroke: {
                        width: 3
                    },
                    series: [{
                            name: i18n.series.bookings,
                            data: trend.series.bookings
                        },
                        {
                            name: i18n.series.paid,
                            data: trend.series.paid
                        }
                    ],
                    legend: {
                        position: 'bottom'
                    }
                };

                if (trendChart) {
                    trendChart.updateOptions(opts);
                    return;
                }

                trendChart = new ApexCharts(document.querySelector("#chart_trend"), opts);
                trendChart.render();
            }

            function renderTopBars(containerId, rows, labelKey, valueKey, setChart, chartRef, seriesName) {
                const labels = rows.map(r => r[labelKey] || '-');
                const values = rows.map(r => Number(r[valueKey] || 0));

                const opts = {
                    chart: {
                        type: 'bar',
                        height: 320,
                        toolbar: {
                            show: false
                        }
                    },
                    xaxis: {
                        categories: labels
                    },
                    series: [{
                        name: seriesName,
                        data: values
                    }],
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            barHeight: '70%'
                        }
                    },
                    dataLabels: {
                        enabled: false
                    }
                };

                if (chartRef) {
                    chartRef.updateOptions(opts);
                    return;
                }

                const ch = new ApexCharts(document.querySelector('#' + containerId), opts);
                ch.render();
                setChart(ch);
            }

            document.getElementById('kpi_apply').addEventListener('click', loadKPI);

            // initial
            loadKPI();
        })();
    </script>
@endpush
