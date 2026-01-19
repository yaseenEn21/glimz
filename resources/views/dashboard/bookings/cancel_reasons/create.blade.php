@extends('base.layout.app')

@section('title', __('bookings.cancel_reasons.create'))

@section('content')

@section('top-btns')
    <a href="{{ route('dashboard.bookings.cancel-reasons.index') }}" class="btn btn-light">
        {{ __('bookings.cancel_reasons.back') }}
    </a>
@endsection

<div class="card">
    <form id="cancel_reason_form" action="{{ route('dashboard.bookings.cancel-reasons.store') }}" method="POST">
        <div class="card-body">
            @include('dashboard.bookings.cancel_reasons._form', ['reason' => []])
        </div>

        <div class="card-footer d-flex justify-content-end gap-3">
            <a href="{{ route('dashboard.bookings.cancel-reasons.index') }}" class="btn btn-light">
                {{ __('bookings.cancel_reasons.cancel') }}
            </a>
            <button class="btn btn-primary" type="submit">{{ __('bookings.cancel_reasons.save') }}</button>
        </div>
    </form>
</div>
@endsection

@push('custom-script')
<script>
(function(){
    const $f = $('#cancel_reason_form');
    $f.on('submit', function(e){
        e.preventDefault();
        $.ajax({
            url: $f.attr('action'),
            method: 'POST',
            data: $f.serialize(),
            success: function(res){
                Swal.fire({icon:'success', title:"{{ __('bookings.cancel_reasons.done') }}", text: res.message});
                if(res.redirect) window.location.href = res.redirect;
            },
            error: function(xhr){
                Swal.fire('Error', xhr.responseJSON?.message || 'Validation error', 'error');
            }
        });
    });
})();
</script>
@endpush