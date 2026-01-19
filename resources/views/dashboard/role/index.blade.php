@extends('base.layout.app')

@push('custom-style')
@endpush

@section('content')
    <div class="card">
        @section('top-btns')
            @can('roles.create')
                <a href="{{ route('dashboard.roles.create') }}" class="btn btn-primary">
                    {{ t('roles.create_new') }}
                </a>
            @endcan
        @endsection

        <div class="card-body">
            <div class="row mb-5">
                <div class="col-md-4">
                    <input type="text" id="search_name" class="form-control" placeholder="{{ t('messages.search') }}">
                </div>
            </div>

            <div class="table-responsive">
                <table id="roles_table" class="table table-row-bordered gy-5 w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ t('roles.table.name') }}</th>
                            <th>{{ t('roles.table.permissions_count') }}</th>
                            <th>{{ t('roles.table.created_at') }}</th>
                            <th>{{ t('roles.table.actions') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('custom-script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        (function() {
            const locale = @json(app()->getLocale());
            const dtLangUrl = (locale === 'ar')
                ? "https://cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json"
                : "https://cdn.datatables.net/plug-ins/1.13.6/i18n/en-GB.json";

            const table = $('#roles_table').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                ajax: {
                    url: '{{ route('dashboard.roles.index') }}',
                    data: function(d) {
                        d.search_name = $('#search_name').val();
                    }
                },
                columns: [{
                        data: 'id',
                        name: 'id',
                        title: "{{ t('datatable.lbl_id') }}"
                    },
                    {
                        data: 'name',
                        name: 'name',
                        title: "{{ t('roles.table.name') }}"
                    },
                    {
                        data: 'permissions_count',
                        name: 'permissions_count',
                        title: "{{ t('roles.table.permissions_count') }}"
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        title: "{{ t('roles.table.created_at') }}"
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        title: "{{ t('roles.table.actions') }}",
                        orderable: false,
                        searchable: false
                    },
                ],
                language: {
                    url: dtLangUrl
                },
                order: [
                    [0, 'desc']
                ]
            });

            // Search (debounce)
            let timer = null;
            $('#search_name').on('keyup change', function() {
                clearTimeout(timer);
                timer = setTimeout(() => table.draw(), 300);
            });

            // Delete confirm
            $(document).on('click', '.js-delete', function() {
                const url = this.dataset.url;
                const name = this.dataset.name || '';

                Swal.fire({
                    title: @json(t('roles.delete_confirm_title')),
                    text: name
                        ? @json(t('roles.delete_confirm_text_with_name')).replace(':name', name)
                        : @json(t('roles.delete_confirm_text')),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: @json(t('roles.delete_confirm_yes')),
                    cancelButtonText: @json(t('roles.delete_confirm_cancel')),
                    reverseButtons: true
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            body: new URLSearchParams({
                                _method: 'DELETE'
                            })
                        })
                        .then(async (res) => {
                            const data = await res.json().catch(() => ({}));
                            if (!res.ok) throw new Error(data.message || @json(t('roles.delete_failed')));
                            Swal.fire({
                                icon: 'success',
                                title: @json(t('roles.deleted_title')),
                                text: data.message || @json(t('roles.deleted_successfully')),
                                timer: 1500,
                                showConfirmButton: false
                            });
                            table.ajax.reload(null, false);
                        })
                        .catch((err) => {
                            Swal.fire({
                                icon: 'error',
                                title: @json(t('roles.error_title')),
                                text: err.message || @json(t('roles.unexpected_error'))
                            });
                        });
                });
            });

            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: @json(t('roles.done_title')),
                    text: @json(session('success')),
                    timer: 1500,
                    showConfirmButton: false
                });
            @endif
            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: @json(t('roles.error_title')),
                    text: @json(session('error'))
                });
            @endif
        })();
    </script>
@endpush