<div class="id-card-shell">
    <!-- Front Face (Portrait design to match reference) -->
    <div class="id-face" style="padding:0;">
        <!-- Top header: deep green gradient with curved accents -->
        <div style="background: linear-gradient(135deg, #0b5f34 0%, #0e7a45 60%, #0b6139 100%); color:#fff; padding:10px 12px 18px; text-align:center; position:relative; overflow:hidden;">
            <div style="display:flex; justify-content:center; align-items:center; gap:10px;">
                <div class="logo-wrap" style="background:rgba(255,255,255,.1);">
                    @if(!empty($branding['logoUrl']))
                        <img class="logo-img" src="{{ $branding['logoUrl'] }}" alt="Logo">
                    @else
                        <div class="logo-fallback">{{ strtoupper(substr($branding['name'] ?? 'SB',0,2)) }}</div>
                    @endif
                </div>
            </div>
            <div style="font-weight:800; font-size:1.06rem; letter-spacing:.5px; margin-top:6px; color:#ffd644; text-shadow:0 1px 1px rgba(0,0,0,.25);">{{ strtoupper($branding['name'] ?? 'SONAR BANGLA COLLEGE') }}</div>
            @if(!empty($branding['address']))
                <div style="font-size:.82rem; opacity:.9;">{{ $branding['address'] }}</div>
            @endif
            <!-- Curved accents SVG to mimic reference waves -->
            <svg viewBox="0 0 400 80" preserveAspectRatio="none" style="position:absolute; bottom:-12px; left:0; width:100%; height:80px; opacity:.35;">
                <path d="M0,50 C80,10 160,90 240,40 C300,10 340,60 400,30 L400,80 L0,80 Z" fill="#0b5f34" />
            </svg>
            <svg viewBox="0 0 400 80" preserveAspectRatio="none" style="position:absolute; bottom:-20px; left:0; width:100%; height:80px; opacity:.20;">
                <path d="M0,60 C90,20 170,100 250,50 C320,20 360,70 400,40 L400,80 L0,80 Z" fill="#0e7a45" />
            </svg>
        </div>

        <!-- Red ribbon "Student ID Card" -->
        <div style="display:flex; justify-content:center; margin-top:8px;">
            <div style="background:#c41e3a; color:#fff; padding:6px 12px; border-radius:4px; font-weight:800; letter-spacing:.4px; text-transform:uppercase;">Student ID Card</div>
        </div>

        <!-- Photo centered -->
        <div class="photo-box" style="height: 160px; width: 130px; margin:8px auto 6px; border:2px solid #22c55e; box-shadow:0 4px 10px rgba(34,197,94,.25);">
            <img class="photo" src="{{ $card['photoUrl'] }}" alt="{{ $card['studentId'] }}">
        </div>

        <!-- Name prominent -->
        <div class="info" style="padding: 0 12px 6px;">
            <div class="student-name" style="text-align:center; font-weight:900; font-size:1.08rem; color:#1f3a8a; letter-spacing:.3px;">{{ strtoupper($card['name']) }}</div>

            <!-- Detail grid matching reference labels -->
            <div style="margin-top:6px; display:grid; grid-template-columns: 1fr 1fr; gap:6px 12px; font-size:.95rem;">
                <div>
                    <span class="fw-bold">Class :</span>
                    <span style="font-weight:700;">{{ $card['class'] }}</span>
                </div>
                <div>
                    <span class="fw-bold">Roll No :</span>
                    <span style="font-weight:700;">{{ $card['roll'] }}</span>
                </div>
                <div style="grid-column: 1 / -1;">
                    <span class="fw-bold">Group :</span>
                    <span style="font-weight:700;">{{ $card['department'] }}</span>
                </div>
                <div style="grid-column: 1 / -1;">
                    <span class="fw-bold">Session :</span>
                    <span style="font-weight:800;">{{ $card['sessionText'] }}</span>
                </div>
                <div style="grid-column: 1 / -1;">
                    <span class="fw-bold">Validity :</span>
                    <span style="font-weight:700;">{{ $card['validity'] }}</span>
                </div>
            </div>

            <!-- Principal signature line -->
            <div style="margin-top:10px; padding: 0 6px 14px; position:relative;">
                @if(!empty($branding['principalSignUrl']))
                    <div style="text-align:center;">
                        <img src="{{ $branding['principalSignUrl'] }}" alt="Principal Sign" style="max-height:38px; width:auto;">
                    </div>
                @endif
                <div style="margin-top:4px; border-top:1px solid var(--id-border); text-align:center; padding-top:6px; font-size:.85rem; color:var(--id-muted);">Signature of the Principal</div>
                <!-- Decorative corner element -->
                <div style="position:absolute; left:0; bottom:0; width:40px; height:16px; background:#16a34a; clip-path: polygon(0 0, 80% 0, 60% 100%, 0% 100%);"></div>
            </div>
        </div>
    </div>

    <!-- Back Face (Portrait layout to match reference) -->
    <div class="id-face back" style="padding:12px;">
        <div style="text-align:center;">
            @php
                $qrText = ($branding['name'] ?? 'Institute')." | ID:".$card['studentId'];
                $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . urlencode($qrText);
            @endphp
            <img src="{{ $qrUrl }}" alt="QR" style="width:120px; height:120px; border:1px solid var(--id-border); border-radius:6px;">
        </div>
        <div style="text-align:center; margin-top:8px; font-size:.9rem; color:#374151;">If found please inform</div>
        <div style="text-align:center; font-weight:900; letter-spacing:.3px; margin-bottom:6px;">{{ strtoupper($branding['name'] ?? 'SONAR BANGLA COLLEGE') }}</div>
        <div style="border-top:1px solid var(--id-border); margin:8px 0;"></div>
        <div style="text-align:center; font-size:.9rem; color:#374151; line-height:1.35;">
            @if(!empty($branding['address']))
                <div>{{ $branding['address'] }}</div>
            @endif
            @if(!empty($branding['email']))
                <div>e-mail: {{ $branding['email'] }}</div>
            @endif
            @if(!empty($branding['phone']))
                <div><span style="font-weight:700;">Phone:</span> {{ $branding['phone'] }}</div>
            @endif
        </div>
    </div>
</div>
