@extends('base.layout.app')

@section('title', __('customers.create_title'))

@section('content')

@section('top-btns')
    <a href="{{ route('dashboard.customers.index') }}" class="btn btn-light">{{ __('customers.back_to_list') }}</a>
@endsection

<div class="card">
    <form action="{{ route('dashboard.customers.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="card-body">
            @include('dashboard.customers._form', ['groups' => $groups])
        </div>

        <div class="card-footer d-flex justify-content-end gap-3">
            <a href="{{ route('dashboard.customers.index') }}" class="btn btn-light">{{ __('customers.cancel') }}</a>
            <button type="submit" class="btn btn-primary">{{ __('customers.save') }}</button>
        </div>
    </form>
</div>
@endsection