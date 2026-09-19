@extends('admin.layouts.app')

@section('title', $title)
@section('heading', $title)

@section('content')
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
        @csrf
        @if($item->exists)
            @method('PUT')
        @endif

        <div class="card">
            <div class="form-grid">
                @foreach($fields as $field)
                    @include('admin.crud.field', ['field' => $field, 'item' => $item])
                @endforeach
            </div>
        </div>

        <div class="form-actions">
            <button class="btn btn--primary" type="submit">ذخیره</button>
            <a class="btn btn--ghost" href="{{ route($routeName.'.index') }}">بازگشت</a>
        </div>
    </form>

    @if($extrasView)
        @include($extrasView, $extrasData)
    @endif
@endsection
