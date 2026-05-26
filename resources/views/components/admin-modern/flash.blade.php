@php
    $types = [
        'success' => 'is-success',
        'error' => 'is-error',
        'warning' => 'is-warning',
        'info' => 'is-info',
    ];
@endphp

@foreach($types as $key => $class)
    @if(session()->has($key))
        <div class="am-flash {{ $class }}" role="alert" data-am-flash>
            <span>{{ session($key) }}</span>
            <button type="button" class="am-flash-close" data-am-flash-close aria-label="Close">x</button>
        </div>
    @endif
@endforeach
