<div class="result-signatures">
    @foreach($signatureView['roles'] as $role)
        <div class="result-signature">
            @if($role === 'Principal/Head Master' && !empty($signatureView['principalSignatureUrl']))
                <img src="{{ $signatureView['principalSignatureUrl'] }}" alt="Principal Signature" class="result-signature-image">
            @endif
            <div class="result-signature-line"></div>
            <div>{{ $role }}</div>
            <small>Signature</small>
        </div>
    @endforeach
</div>
