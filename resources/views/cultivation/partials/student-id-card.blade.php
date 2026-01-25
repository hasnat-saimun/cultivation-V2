<div class="id-card-shell">
    <!-- Front Face -->
    <div class="id-face">
        <div class="id-header">
            <div class="logo-wrap">
                @if(!empty($branding['logoUrl']))
                    <img class="logo-img" src="{{ $branding['logoUrl'] }}" alt="Logo">
                @else
                    <div class="logo-fallback">{{ strtoupper(substr($branding['name'] ?? 'SB',0,2)) }}</div>
                @endif
            </div>
            <div class="ins-meta">
                <div class="ins-name">{{ $branding['name'] ?? 'SONAR BANGLA COLLEGE' }}</div>
                @if(!empty($branding['address']))
                    <div class="ins-sub">{{ $branding['address'] }}</div>
                @endif
            </div>
            <div class="badge-chip">Student ID Card</div>
        </div>
        <div class="id-body">
            <div class="photo-box">
                <img class="photo" src="{{ $card['photoUrl'] }}" alt="{{ $card['studentId'] }}">
            </div>
            <div class="info">
                <div class="student-name">{{ $card['name'] }}</div>
                <div class="meta">Roll: <strong>{{ $card['roll'] }}</strong></div>
                <div class="pill-row">
                    <div class="pill">Class: {{ $card['class'] }}</div>
                    @if(!empty($card['section']) && $card['section'] !== '-')
                    <div class="pill">Section: {{ $card['section'] }}</div>
                    @endif
                    @if(!empty($card['department']) && $card['department'] !== '-')
                    <div class="pill">Group: {{ $card['department'] }}</div>
                    @endif
                </div>
                <div class="meta muted">Session: <strong>{{ $card['sessionText'] }}</strong></div>
                <div class="id-footer">
                    <div class="foot-col">
                        <div class="label">Student ID</div>
                        <div class="value">{{ $card['studentId'] }}</div>
                    </div>
                    <div class="foot-col">
                        <div class="label">Validity</div>
                        <div class="value">{{ $card['validity'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="id-signatures">
            @if(!empty($branding['principalSignUrl']))
                <div class="sig-line">
                    <img src="{{ $branding['principalSignUrl'] }}" alt="Principal Sign" style="max-height:42px; width:auto;">
                    <div>Signature of the Principal</div>
                </div>
            @else
                <div class="sig-line">Signature of the Principal</div>
            @endif
            <div class="sig-line">Student Signature</div>
        </div>
    </div>

    <!-- Back Face -->
    <div class="id-face back">
        <div class="back-title">If found please inform</div>
        <div style="font-weight:800; font-size:0.95rem;">{{ $branding['name'] ?? 'SONAR BANGLA COLLEGE' }}</div>
        <div class="d-flex" style="gap:12px; align-items:flex-start;">
            <div>
                @php
                    $qrText = ($branding['name'] ?? 'Institute')." | ID:".$card['studentId'];
                    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' . urlencode($qrText);
                @endphp
                <img src="{{ $qrUrl }}" alt="QR" style="width:120px; height:120px; border:1px solid var(--id-border); border-radius:8px;">
            </div>
            <div style="flex:1; min-width:0;">
                <div class="back-grid">
                    @if(!empty($branding['address']))
                        <div>
                            <div class="label">Address</div>
                            <div class="value">{{ $branding['address'] }}</div>
                        </div>
                    @endif
                    @if(!empty($branding['email']))
                        <div>
                            <div class="label">Email</div>
                            <div class="value">{{ $branding['email'] }}</div>
                        </div>
                    @endif
                    @if(!empty($branding['phone']))
                        <div>
                            <div class="label">Phone</div>
                            <div class="value">{{ $branding['phone'] }}</div>
                        </div>
                    @endif
                </div>
                <div class="id-back-footer" style="margin-top:10px;">
                    <div>Carry this card at all times.</div>
                    <div>Unauthorized use is prohibited.</div>
                </div>
            </div>
        </div>
    </div>
</div>
