@props([
    'action',
    'confirm' => 'Are you sure you want to delete this item?',
    'title' => 'Delete',
])

<form method="POST" action="{{ $action }}" class="d-inline" style="display:inline-block;"
      onsubmit="return confirm(@js($confirm));">
    @csrf
    @method('DELETE')
    <button type="submit" {{ $attributes->merge(['class' => 'btn btn-link p-0 border-0 align-baseline']) }} title="{{ $title }}">
        {{ $slot }}
    </button>
</form>
