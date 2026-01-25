<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cultivation – Client Offer</title>
    <style>
        :root { --primary: {{ $brandColor ?? '#0f62fe' }}; --text:#1a1a1a; --muted:#6b7280; --bg:#ffffff; --border:#e5e7eb; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; color:var(--text); background:var(--bg); }
        .container { max-width: 1000px; margin: 24px auto; padding: 0 20px; }
        header { display:flex; align-items:center; justify-content: space-between; gap:20px; border-bottom: 3px solid var(--border); padding: 18px 0; }
        header img { height:72px; width:auto; }
        .brand .title { font-size: 30px; font-weight: 800; letter-spacing: .3px; }
        .brand .subtitle { color: var(--muted); margin-top:4px; font-size: 14px; font-weight: 600; letter-spacing: .4px; text-transform: uppercase; }
        .brand .meta { color: var(--muted); margin-top: 6px; font-size: 13px; }
        .meta-item { display:flex; align-items:center; gap:8px; margin: 4px 0; }
        .badge { display:inline-block; background: var(--primary); color:#fff; padding:6px 12px; border-radius:999px; font-size: 13px; margin-top:10px; font-weight:600; }
        .section { margin: 28px 0; }
        .section h2 { font-size: 20px; margin-bottom: 12px; }
        .grid { display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 14px; }
        .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px; background:#fff; }
        .card h3 { font-size:16px; margin:0 0 8px; display:flex; align-items:center; gap:8px; }
        .icon { width:18px; height:18px; color: var(--primary); flex: 0 0 auto; }
        .list { list-style: none; padding:0; margin:0; }
        .list li { display:flex; align-items:center; gap:8px; margin: 6px 0; }
        .cta { display:flex; align-items:center; justify-content: space-between; gap:16px; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 16px; }
        .cta .actions { display:flex; gap:10px; }
        .btn { border: 1px solid var(--primary); color:#fff; background: var(--primary); padding:10px 14px; border-radius:8px; text-decoration:none; font-weight:600; }
        .btn.secondary { background:#fff; color: var(--primary); }
        footer { margin-top: 28px; padding-top: 16px; border-top: 1px solid #e5e7eb; color: var(--muted); font-size: 14px; }
        .print-note { font-size: 12px; color: var(--muted); margin-top: 6px; }
        @media (max-width: 840px) { .grid { grid-template-columns: 1fr; } }
        @media print { .btn, .print-note { display:none !important; } body { background:#fff; } .container { margin:0; max-width: auto; } }
    </style>
    <meta name="robots" content="noindex, nofollow" />
</head>
<body>
    <div class="container">
        <header>
            <div class="brand">
                <img src="{{ asset('docs/images/logo.png') }}" alt="Cultivation Logo">
                <div class="subtitle"><strong>The Education Manager</strong></div>
                <div class="meta">
                   <div class="meta-item">
                       <svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M3 4h18v4H3V4zm0 6h10v10H3V10zm12 0h6v10h-6V10z"/></svg>
                       <strong>Powered by: Virtual IT Professional</strong>
                   </div>
                   <div class="meta-item">
                       <svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M6.6 10.2l2.2-2.2 4.4 4.4-2.2 2.2c-.4.4-1 .5-1.5.3-1.6-.7-3-1.9-4-3.4-.3-.5-.3-1.1.1-1.5zM14.3 4.7l2.1-2.1c.4-.4 1-.4 1.5 0 2.1 2.1 3.4 4.8 3.8 7.7.1.5-.2 1-.7 1.1l-2.9.6c-.5.1-1-.2-1.2-.7-.4-1.2-1-2.4-1.8-3.4-.3-.5-.3-1.1.2-1.5z"/></svg>
                       Mobile: +880 1836 994770 
                       <svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M2 6l10 7 10-7v12H2V6zm10-2l10 7-10 7-10-7 10-7z"/></svg>
                       Email: virtualitprofessional@gmail.com
                       <svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 2c1.3 0 2.5.3 3.6.9-1.1 1.7-2.7 2.9-4.6 3.3V4zm-2 0v3.2C8.1 6.8 6.5 5.6 5.4 3.9A9.9 9.9 0 0110 4zm-6.2 4.2A8 8 0 004 12h4c0-1.9.6-3.7 1.6-5.2-2-.4-3.8-1.5-5.2-2.6zM4 14a8 8 0 003.4 5.8c1.2-1.8 3-3 5.1-3.4V14H4zm8 0v2.4c2.1.4 3.9 1.6 5.1 3.4A8 8 0 0020 14h-8zm0-4c1.3-.3 2.5-1 3.4-2 1 1.5 1.6 3.3 1.6 5h4a8 8 0 00-2.2-5.6c-2 1.6-4.5 2.6-6.8 2.6z"/></svg>
                       Web: www.virtualitprofessional.com
                   </div>
                </div>
                <span class="badge">Modern, complete & easy to use</span>
            </div>
        </header>

        <section class="section">
            <h2>Highlights</h2>
            <div class="grid">
                <div class="card">
                    <h3>
                        <svg class="icon" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M3 6.75A2.25 2.25 0 015.25 4.5h10.5A2.25 2.25 0 0118 6.75v10.5a.75.75 0 01-1.093.66L12 15.9l-4.907 2.01A.75.75 0 016 17.25V6.75A2.25 2.25 0 013 6.75z"/></svg>
                        Academics
                    </h3>
                    <ul class="list">
                        <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M6 4h12v2H6V4zm0 4h12v2H6V8zm0 4h12v2H6v-2zm0 4h8v2H6v-2z"/></svg>Class & Exam Routine</li>
                        <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4V6zm0 4h12v2H4v-2zm0 4h10v2H4v-2z"/></svg>Syllabus & Semester Plan</li>
                        <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M9 4h10v16H5V8h4V4zm2 2v2h-2V6h2zm-2 4h8v2H9v-2zm0 4h8v2H9v-2z"/></svg>Internal Result & Marksheet</li>
                    </ul>
                </div>
                <div class="card">
                    <h3>
                        <svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 100-10 5 5 0 000 10zm-9 9a9 9 0 0118 0v1H3v-1z"/></svg>
                        Admissions & Profiles
                    </h3>
                    <ul class="list">
                        <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11a4 4 0 10-8 0 4 4 0 008 0zm-9 7a7 7 0 0114 0v1H7v-1z"/></svg>Student/Teacher/Staff Management</li>
                        <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M4 5h16v2H4V5zm0 4h10v2H4V9zm0 4h12v2H4v-2zm0 4h8v2H4v-2z"/></svg>Bulk Uploads & Photo Tools</li>
                        <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M7 4h10a2 2 0 012 2v12H5V6a2 2 0 012-2zm1 3v2h8V7H8zm0 4v2h8v-2H8z"/></svg>Professional ID Cards</li>
                    </ul>
                </div>
                <div class="card">
                    <h3>
                        <svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M7 2h10v2h3a1 1 0 011 1v16a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1h3V2zm0 4H5v14h14V6h-2v2H7V6zm2 5h2v2H9v-2zm0 4h2v2H9v-2z"/></svg>
                        Attendance & Accounts
                    </h3>
                    <ul class="list">
                        <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M7 10h5v5H7v-5zm6-6h5v5h-5V4zM7 4h5v5H7V4zm6 6h5v5h-5v-5z"/></svg>Daily & Monthly Attendance</li>
                        <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M3 7h18v2H3V7zm0 4h12v2H3v-2zm0 4h15v2H3v-2z"/></svg>Fees & Cash Reports</li>
                        <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v12H4V6zm2 2v8h12V8H6z"/></svg>Transparent Records</li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="section">
            <h2>Certificates & Results</h2>
            <div class="grid">
                <div class="card">
                    <h3>
                        <svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H7a2 2 0 00-2 2v14l5-3 5 3V5a2 2 0 00-2-2h4z"/></svg>
                        Testimonials & Transfer Certificates
                    </h3>
                    <ul class="list">
                        <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M5 4h14v2H5V4zm0 4h10v2H5V8zm0 4h12v2H5v-2zm0 4h8v2H5v-2z"/></svg>Print-ready layouts with watermark</li>
                        <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3 6 6 .5-4.5 4 1.5 6-6-3.5-6 3.5 1.5-6L3 8.5 9 8l3-6z"/></svg>Auto reference/SL generation</li>
                        <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v12H4V6zm2 2v8h12V8H6z"/></svg>Clean, official formatting</li>
                    </ul>
                </div>
                <div class="card">
                    <h3>
                        <svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M6 3h9l5 5v13H6V3zm9 1.5V8h4.5L15 4.5z"/></svg>
                        Result Archive & Transcripts
                    </h3>
                    <ul class="list">
                        <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4V6zm0 4h12v2H4v-2zm0 4h10v2H4v-2z"/></svg>Historical result browsing</li>
                        <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M5 5h14v4H5V5zm0 6h14v2H5v-2zm0 4h10v2H5v-2z"/></svg>Bulk transcript generation</li>
                        <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M6 2h9l5 5v15H6V2zm9 2.5V8h4.5L15 4.5z"/></svg>Share-ready PDFs</li>
                    </ul>
                </div>
                <div class="card">
                    <h3>
                        <svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M4 5h16v4H4V5zm0 6h7v8H4v-8zm9 6h7v2h-7v-2zm0-4h7v2h-7v-2z"/></svg>
                        Institute CMS
                    </h3>
                    <ul class="list">
                        <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4V6zm0 4h12v2H4v-2zm0 4h10v2H4v-2z"/></svg>Sliders, galleries & pages</li>
                        <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l9 4-9 4-9-4 9-4zm-9 8l9 4 9-4v8l-9 4-9-4v-8z"/></svg>Principal speech & committees</li>
                        <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M3 7h18v2H3V7zm0 4h14v2H3v-2zm0 4h10v2H3v-2z"/></svg>Public-facing updates</li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="section">
            <h2>Client Offer</h2>
            <div class="card">
                <ul class="list">
                    <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.2l-3.5-3.5 1.4-1.4L9 13.4l8.1-8.1 1.4 1.4L9 16.2z"/></svg>Guided onboarding and configuration</li>
                    <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.2l-3.5-3.5 1.4-1.4L9 13.4l8.1-8.1 1.4 1.4L9 16.2z"/></svg>Data import support (students, teachers, staff)</li>
                    <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.2l-3.5-3.5 1.4-1.4L9 13.4l8.1-8.1 1.4 1.4L9 16.2z"/></svg>Training for operators and administrators</li>
                    <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.2l-3.5-3.5 1.4-1.4L9 13.4l8.1-8.1 1.4 1.4L9 16.2z"/></svg>Customization options for branding & documents</li>
                    <li><svg class="icon" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.2l-3.5-3.5 1.4-1.4L9 13.4l8.1-8.1 1.4 1.4L9 16.2z"/></svg>Ongoing updates and priority support</li>
                </ul>
            </div>
        </section>

        <section class="section">
            <div class="cta">
                <div>
                    <strong>Ready to modernize your institute?</strong>
                    <div class="print-note">Tip: Use Background graphics ON when printing.</div>
                </div>
                <div class="actions">
                    <a class="btn" href="#" onclick="window.print(); return false;">Print Brochure</a>
                    <a class="btn secondary" href="{{ route('adminProfile') }}">Contact Admin</a>
                </div>
            </div>
        </section>

        <footer>
            <div>Version: {{ config('app.version') ?? 'v' . trim(@file_get_contents(base_path('VERSION'))) }}</div>
            <div>Updated: {{ now()->format('Y-m-d') }}</div>
        </footer>
    </div>
</body>
</html>
