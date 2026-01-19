@extends('base.layout.app')

@section('content')
<div class="card">
  <form action="{{ route('dashboard.roles.store') }}" method="POST">
    @csrf
    <div class="card-body">
      <div class="mb-5">
        <label class="form-label">{{ t('roles.fields.name') }}</label>
        <input name="name" class="form-control" required placeholder="{{ t('roles.placeholders.name') }}">
      </div>

      @foreach($permissions as $module => $list)
        <div class="mb-4 p-4 border rounded-3">
          <div class="fw-bold mb-2">{{ t('module.'.$module) }}</div>

          <div class="row g-3">
            @foreach($list as $perm)
              <div class="col-md-3">
                <label class="form-check form-check-sm form-check-custom">
                  <input class="form-check-input" type="checkbox" name="permissions[]"
                         value="{{ $perm->name }}">
                  <span class="form-check-label">{{ t(key: 'roles.'.explode('.',$perm->name)[1]) }}</span>
                </label>
              </div>
            @endforeach
          </div>
        </div>
      @endforeach
    </div>

    <div class="card-footer d-flex gap-2">
      <button class="btn btn-primary">{{ t('roles.save') }}</button>
      <a href="{{ route('dashboard.roles.index') }}" class="btn btn-light">{{ t('roles.cancel') }}</a>
    </div>
  </form>
</div>
@endsection