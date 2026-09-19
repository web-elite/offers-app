@extends('admin.layouts.app')

@section('title', $title)
@section('heading', $title)

@section('content')
    <div class="card">
        <div class="toolbar">
            @if($searchable)
                <form class="toolbar__search" method="GET" action="{{ route($routeName.'.index') }}">
                    @foreach(request()->query() as $key => $value)
                        @if($key !== 'q' && !is_array($value))
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <input type="search" name="q" value="{{ $searchTerm }}" placeholder="جستجو…" aria-label="جستجو">
                    <button class="btn" type="submit">جستجو</button>
                    @if($searchTerm !== '')
                        <a class="btn btn--ghost" href="{{ route($routeName.'.index') }}">پاک کردن</a>
                    @endif
                </form>
            @endif

            @if(!empty($filters))
                <form class="toolbar__filters" method="GET" action="{{ route($routeName.'.index') }}">
                    @if($searchTerm !== '')
                        <input type="hidden" name="q" value="{{ $searchTerm }}">
                    @endif
                    @foreach($filters as $filter)
                        <select name="{{ $filter['name'] }}" onchange="this.form.submit()" aria-label="{{ $filter['label'] }}">
                            <option value="">{{ $filter['label'] }}: همه</option>
                            @foreach($filter['options'] as $value => $label)
                                <option value="{{ $value }}" @selected((string) request($filter['name']) === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @endforeach
                </form>
            @endif

            <div class="toolbar__spacer"></div>

            <a class="btn btn--primary" href="{{ route($routeName.'.create') }}">افزودن {{ $singular }}</a>
        </div>

        @if($bulkUrl)
            {{-- Standalone bulk form; row checkboxes are associated via the HTML5 form attribute. --}}
            <form id="bulk-form" method="POST" action="{{ $bulkUrl }}">
                @csrf
            </form>
            <div class="bulkbar">
                <button class="btn" type="submit" form="bulk-form" name="action" value="mark_expired"
                        onclick="return confirm('آفرهای انتخاب‌شده منقضی علامت بخورند؟')">{{ $bulkLabel }}</button>
                <span class="muted">ابتدا ردیف‌های مورد نظر را تیک بزنید.</span>
            </div>
        @endif

        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    @if($bulkUrl)<th class="table__check"></th>@endif
                    @foreach($columns as $column)
                        <th>{{ $column['label'] }}</th>
                    @endforeach
                    <th class="table__actions">عملیات</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        @if($bulkUrl)
                            <td class="table__check">
                                <input type="checkbox" name="ids[]" value="{{ $row['id'] }}" form="bulk-form" aria-label="انتخاب ردیف">
                            </td>
                        @endif
                        @foreach($row['cells'] as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                        <td class="table__actions">
                            <a class="btn btn--sm" href="{{ $row['editUrl'] }}">ویرایش</a>
                            @if($row['deletable'])
                                <form method="POST" action="{{ $row['deleteUrl'] }}" onsubmit="return confirm('حذف شود؟ این عمل بازگشت‌پذیر نیست.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn--sm btn--danger" type="submit">حذف</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columns) + 2 }}" class="table__empty">موردی یافت نشد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pager">{{ $items->links() }}</div>
    </div>
@endsection
