@extends('base.layout.app')

@section('title', __('customers.edit_title'))

@section('content')

@section('top-btns')
    <a href="{{ route('dashboard.customers.show', $customer->id) }}" class="btn btn-light">{{ __('customers.back_to_profile') }}</a>
@endsection

<div class="card">
    <form action="{{ route('dashboard.customers.update', $customer->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card-body">
            @include('dashboard.customers._form', ['customer' => $customer, 'groups' => $groups])
        </div>

        <div class="card-footer d-flex justify-content-end gap-3">
            <a href="{{ route('dashboard.customers.show', $customer->id) }}" class="btn btn-light">{{ __('customers.cancel') }}</a>
            <button type="submit" class="btn btn-primary">{{ __('customers.save_changes') }}</button>
        </div>
    </form>
</div>
@endsection
