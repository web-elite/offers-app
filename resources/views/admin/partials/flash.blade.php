@if(session('status'))
    <div class="alert alert--ok">{{ session('status') }}</div>
@endif

@if(session('error'))
    <div class="alert alert--err">{{ session('error') }}</div>
@endif

@if($errors->any())
    <div class="alert alert--err">
        <strong>خطا:</strong>
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
