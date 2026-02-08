@php
    $qrPayload = [
        'student_id' => $card['studentId'] ?? '',
        'name' => $card['name'] ?? '',
        'institute' => $branding['name'] ?? 'Institute',
        'guardian_name' => $card['guardianName'] ?? '',
        'guardian_mobile' => $card['guardianPhone'] ?? '',
        'guardian_relation' => $card['guardianRelation'] ?? '',
        'class' => $card['class'] ?? '',
        'section' => $card['section'] ?? '',
        'roll' => $card['roll'] ?? '',
    ];
    $qrText = json_encode($qrPayload, JSON_UNESCAPED_SLASHES);
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . urlencode($qrText);
    $phone = $branding['phone'] ?? 'N/A';
    $website = $branding['website'] ?? 'cultivationapp.com';
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
            </div>
        </div>
        <div class="id-front-body portrait">
            <div class="id-card-label">Student ID Card</div>
            <div class="id-photo-wrap portrait">
                <img src="{{ $card['photoUrl'] }}" alt="{{ $card['studentId'] }}">
            </div>
            <div class="id-info">
                <div class="id-name">{{ $card['name'] }}</div>
                <div class="id-number">ID {{ $card['studentId'] }}</div>
                <div class="id-portrait-grid">
                    <div>Class <span>{{ $card['class'] }}</span></div>
                    <div>Roll <span>{{ $card['roll'] }}</span></div>
                    <div>Section <span>{{ $card['section'] }}</span></div>
                </div>
            </div>
        </div>
        <div class="id-front-footer">
            <div>Web: {{ $website }}</div>
        </div>
    </div>

    <div class="id-face id-back">
        <div class="id-back-top"></div>
        <div class="id-back-body">
            <div class="id-back-left">
                <div class="id-back-title">Instructions:</div>
                <ol class="id-back-list">
                    <li>This card is property of the institution.</li>
                    <li>If found, please return to the office.</li>
                </ol>
                <div class="id-back-note">VALID: <span>{{ $card['validity'] }}</span></div>
                <div class="id-guardian-box">
                    <div class="id-back-title">Guardian Details:</div>
                    <div class="id-guardian-row">Name <span>{{ $card['guardianName'] ?? '-' }}</span></div>
                    <div class="id-guardian-row">Mobile <span>{{ $card['guardianPhone'] ?? '-' }}</span></div></div>
            </div>
            <div class="id-barcode-box id-back-qr">
                <img class="id-back-qr-img" src="{{ $qrUrl }}" alt="QR">
                <div class="id-card-no">Card No {{ $card['studentId'] }}</div>
                <div class="id-sign">Authorized Signature</div>
            </div>
        </div>
        <div class="id-back-footer-bar">
            <div>If found, please contact: <span>{{ $phone }}</span></div>
        </div>
    </div>
</div>
