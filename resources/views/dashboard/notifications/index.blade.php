@extends('base.layout.app')

@section('title', $title ?? 'إشعارات النظام')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold mb-0">{{ $page_title ?? 'سجل الإشعارات' }}</h2>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="notifications-table" class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>العنوان</th>
                        <th>النص</th>
                        <th>الحالة</th>
                        <th>تاريخ الإرسال</th>
                        <th>الإجراء</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@push('custom-script')
    <script>
        $(function() {

            $('#notifications-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('dashboard.notifications.index') }}',
                columns: [{
                        data: 'title',
                        name: 'title'
                    },
                    {
                        data: 'body',
                        name: 'body',
                        render: function(data) {
                            return data ? data.substring(0, 120) : '';
                        }
                    },
                    {
                        data: 'is_read_badge',
                        name: 'is_read',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'created_at_formatted',
                        name: 'created_at'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    },
                ]
            });
        });
    </script>
    <script>
        $(document).on('click', '.btn-mark-read', function(e) {
            e.preventDefault();

            const btn = $(this);
            const url = btn.data('mark-url');
            const row = btn.closest('tr');
            const badge = row.find('[data-notification-status]');

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'PATCH',
                },
                success: function(res) {
                    if (!res.success) return;

                    // حدّث البادج
                    badge
                        .removeClass('bg-warning')
                        .addClass('bg-light text-muted')
                        .text('مقروء');

                    // احذف الزر
                    btn.remove();

                    // حدّث عدّاد الجرس لو رجّعت unread_count من الكنترولر
                    if (res.data && typeof res.data.unread_count !== 'undefined') {
                        const badgeIcon = document.querySelector('#notification-icon .badge');
                        if (badgeIcon) {
                            const n = parseInt(res.data.unread_count, 10) || 0;
                            if (n > 0) {
                                badgeIcon.textContent = n;
                                badgeIcon.classList.remove('d-none');
                            } else {
                                badgeIcon.textContent = '';
                                badgeIcon.classList.add('d-none');
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
