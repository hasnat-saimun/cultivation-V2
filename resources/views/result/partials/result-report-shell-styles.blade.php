<style>
    @page { size: A4 landscape; margin: {{ $printMargin ?? '5mm' }}; }
    .result-page-header { margin-bottom: 14px; }
    .header-surface,
    .header-meta-row {
        background: #fff;
        border: 1px solid #d7dde7;
        border-radius: 10px;
    }
    .header-surface {
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        gap: 14px;
        padding: 12px 14px;
    }
    .header-logo-wrap {
        width: 66px;
        height: 66px;
        border: 1px solid #d7dde7;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        flex-shrink: 0;
    }
    .header-logo-image { max-width: 54px; max-height: 54px; object-fit: contain; }
    .header-logo-fallback {
        font-size: 11px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: .06em;
    }
    .header-identity { min-width: 0; }
    .header-kicker,
    .header-report-label,
    .header-meta-label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #6b7280;
    }
    .header-kicker { margin-bottom: 2px; }
    .header-institute-name {
        margin: 0;
        font-size: 24px;
        line-height: 1.1;
        font-weight: 800;
        color: #111827;
    }
    .header-contact { margin-top: 4px; font-size: 12px; line-height: 1.35; color: #4b5563; }
    .header-title-block { text-align: right; min-width: 220px; }
    .header-report-label { margin-bottom: 3px; }
    .header-report-title { margin: 0; font-size: 19px; line-height: 1.15; font-weight: 700; color: #111827; }
    .header-report-timestamp { margin-top: 6px; font-size: 11px; color: #374151; }
    .header-meta-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 14px;
        margin-top: 10px;
    }
    .header-meta-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 8px 14px;
        flex: 1;
    }
    .header-meta-item { min-width: 0; }
    .header-meta-label { margin-bottom: 2px; }
    .header-meta-value {
        font-size: 13px;
        line-height: 1.25;
        font-weight: 600;
        color: #111827;
        word-break: break-word;
    }
    .header-actions { display: flex; align-items: center; justify-content: flex-end; min-width: fit-content; }
    .result-report-card {
        margin-top: 14px;
        padding: 14px;
        background: #fff;
        border: 1px solid #d7dde7;
        border-radius: 10px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, .05);
    }
    .result-empty-state { margin-top: 16px; padding: 20px; border-radius: 10px; }
    .result-empty-state strong { display: block; margin-bottom: 3px; }
    .result-print-page {
        break-after: page;
        page-break-after: always;
        min-height: 185mm;
        position: relative;
    }
    .result-print-page:last-child { break-after: auto; page-break-after: auto; }
    .page-footer { text-align: right; font-size: 9px; margin-top: 4px; }
    .result-signatures {
        display: flex;
        justify-content: space-around;
        gap: 50px;
        margin-top: 18px;
        break-inside: avoid;
        page-break-inside: avoid;
    }
    .result-signature { text-align: center; min-width: 180px; padding-top: 30px; }
    .result-signature-line { border-top: 1px solid #111; }
    .result-signature-image { height: 35px; max-width: 120px; object-fit: contain; }

    @media (max-width: 820px) {
        .header-surface { grid-template-columns: auto 1fr; }
        .header-title-block { grid-column: 1 / -1; text-align: left; }
        .header-meta-row { flex-direction: column; }
        .header-meta-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); width: 100%; }
        .header-actions { width: 100%; justify-content: flex-start; }
    }

    @media print {
        *, *::before, *::after {
            box-shadow: none !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }
        .no-print,
        .d-print-none,
        .navbar,
        .main-sidebar,
        .main-header,
        .content-header,
        .main-footer,
        .sidebar-main,
        .header-menu-one,
        .breadcrumbs-area,
        .footer-wrap-layout1,
        .result-filter-form {
            display: none !important;
        }
        .d-print-block { display: block !important; }
        #wrapper,
        .wrapper,
        .dashboard-page-one,
        .dashboard-content-one,
        .main-website,
        .main-content,
        .container-fluid,
        .print-report {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
        }
        .result-page-header {
            margin-bottom: 12px !important;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .header-surface {
            display: grid !important;
            grid-template-columns: auto 1fr auto !important;
            align-items: center !important;
            gap: 14px !important;
            padding: 10px 12px !important;
        }
        .header-meta-row {
            display: flex !important;
            align-items: flex-start !important;
            justify-content: space-between !important;
            gap: 12px !important;
            padding: 8px 12px !important;
            margin-top: 8px !important;
        }
        .header-meta-grid {
            display: grid !important;
            grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
            gap: 8px 14px !important;
            flex: 1;
        }
        .header-title-block { text-align: right !important; min-width: 220px !important; }
        .header-actions,
        .header-report-timestamp { display: none !important; }
        .result-report-card { margin: 0; padding: 0; border: 0; border-radius: 0; box-shadow: none; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { page-break-inside: avoid; break-inside: avoid; }
    }
</style>
