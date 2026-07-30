<form method="GET" action="{{ $filterAction }}" class="result-filter-form row g-2 align-items-end no-print d-print-none">
    @include('result.partials.result-filters')
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">Show Result</button>
    </div>
    @if($showCompactOption ?? false)
        <div class="col-12">
            <label>
                <input type="checkbox" name="compact" value="1" {{ $compactMode ? 'checked' : '' }}>
                Compact per-student subjects
            </label>
        </div>
    @endif
</form>
