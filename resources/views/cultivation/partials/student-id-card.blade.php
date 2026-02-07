@php
    $qrText = ($branding['name'] ?? 'Institute') . ' | ID:' . $card['studentId'];
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' . urlencode($qrText);
    $phone = $branding['phone'] ?? 'N/A';
    $website = $branding['website'] ?? ($branding['email'] ?? 'cultivation.com');
@endphp

<div class="id-card-shell">
    <div class="id-face id-front">
        <div class="id-front-top">
            <div class="id-top-row">
                <div class="id-brand">
                    <div class="id-logo">
                        @if(!empty($branding['logoUrl']))
                            <img src="{{ $branding['logoUrl'] }}" alt="Logo">
                        @else
                            <div class="logo-fallback">{{ strtoupper(substr($branding['name'] ?? 'CS', 0, 2)) }}</div>
                        @endif
                    </div>
                    <div>
                        <div class="id-school-name">{{ $branding['name'] ?? 'CULTIVATION SCHOOL' }}</div>
                        <div class="id-school-tag">{{ $branding['tagline'] ?? 'Excellence in Education' }}</div>
                    </div>
                </div>
                <div class="id-card-label">Student ID Card</div>
            </div>
        </div>
        <div class="id-front-body landscape">
            <div class="id-photo-wrap">
                <img src="{{ $card['photoUrl'] }}" alt="{{ $card['studentId'] }}">
            </div>
            <div class="id-info">
                <div class="id-name">{{ $card['name'] }}</div>
                <div class="id-row">ID: <span>{{ $card['studentId'] }}</span></div>
                <div class="id-row">Class: <span>{{ $card['class'] }}</span></div>
                <div class="id-row">Section: <span>{{ $card['section'] }}</span></div>
                <div class="id-row">Roll: <span>{{ $card['roll'] }}</span></div>
                <div class="id-row">Session: <span>{{ $card['sessionText'] }}</span></div>
            </div>
            <div class="id-qr">
                <img src="{{ $qrUrl }}" alt="QR">
            </div>
        </div>
        <div class="id-front-footer">
            <div>VALID: <span>{{ $card['validity'] }}</span></div>
            <div>{{ $phone }}</div>
        </div>
    </div>

    <div class="id-face id-back">
        <div class="id-back-top"></div>
        <div class="id-back-body landscape">
            <div>
                <div class="id-back-title">Instructions:</div>
                <ol class="id-back-list">
                    <li>This card is property of the institution.</li>
                    <li>If found, please return to the office.</li>
                    <li>Misuse may result in disciplinary action.</li>
                </ol>
                <div class="id-back-note"><strong>Note:</strong> Carry this ID during campus hours.</div>
                <div class="id-sign">Authorized Signature</div>
            </div>
            <div class="id-barcode-box">
                <div class="id-barcode"></div>
                <div class="id-card-no">Card No {{ $card['studentId'] }}</div>
            </div>
        </div>
        <div class="id-back-footer-bar">
            <div>Emergency Contact: <span>{{ $phone }}</span></div>
            <div>{{ $website }}</div>
        </div>
    </div>
</div>
