<?php
if (!function_exists('app_url')) {
    http_response_code(500);
    die('Missing config.');
}

$user = getUser();
$role = (string)($user['role'] ?? '');
$deptId = (int)($user['department_id'] ?? 0);
$userBuilding = normalize_building($user['entity'] ?? null);

$canSeeAll = in_array($role, ['ga_president', 'ga_staff', 'security'], true);
$canChooseBuilding = in_array($role, ['ga_president', 'ga_staff', 'department'], true);
$departmentsDb = fetch_departments();

$apiUrl = app_url('api/analytics.php');
?>

<style>
    /* 1. FILTER COLLAPSIBLE LOGIC */
    #filter-collapsible-content {
        max-height: 500px;
        transition: all 0.3s ease-in-out;
        overflow: hidden;
    }
    #filter-collapsible-content.collapsed {
        max-height: 0 !important;
        margin-top: 0 !important;
        opacity: 0;
        pointer-events: none;
    }
    #filter-chevron.rotated {
        transform: rotate(-180deg);
    }

    /* 2. TAB BAR TRACK (The rounded grey background) */
    .tabs-bar {
        display: flex !important;
        width: 100% !important;
        gap: 4px !important;
        background-color: #f1f5f9 !important; 
        padding: 6px !important;
        border-radius: 12px !important;
        margin-bottom: 24px !important;
        overflow: hidden !important; 
    }

    /* 3. TAB BUTTONS - BALANCED FOR SPACE & READABILITY */
    #analytics-tabs button.tab-btn {
        flex: 1 !important;
        background-color: transparent !important;
        color: #64748b !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 10px 4px !important; 
        font-size: 0.75rem !important; 
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.02em !important;
        border: none !important;
        border-radius: 8px !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        white-space: nowrap !important;
        outline: none !important;
    }

    /* 4. ACTIVE TAB STATE - VIVID GREEN WITH PURE WHITE TEXT */
    #analytics-tabs button.tab-btn.active {
        background-color: #22c55e !important; 
        color: #ffffff !important;           
        font-weight: 800 !important;
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3) !important;
    }

    /* 5. TAB HOVER STATE */
    #analytics-tabs button.tab-btn:hover:not(.active) {
        background-color: rgba(0, 0, 0, 0.05) !important;
        color: #1e293b !important;
    }

    /* 6. KPI CARDS - READABLE TEXT FOR 2-ROW / 3-CARD LAYOUT */
    .kpi-card i {
        font-size: 1.3rem;
        display: flex;
        align-items: center;
    }

    .kpi-value {
        font-size: 2rem !important; 
        font-weight: 800 !important;
        color: #1e293b;
        margin: 5px 0 !important;
        text-align: left !important; 
    }

    .kpi-label {
      font-size: 0.95rem !important; 
        font-weight: 700 !important;
      color: #111;
      text-transform: none;
      letter-spacing: 0.01em;
    }

    .kpi-sub {
        font-size: 0.8rem !important; 
      color: #6c757d;
        line-height: 1.4;
    }

    /* 7. HEADER RANGE TEXT - REMOVED CAPSLOCK */
    #analytics-range {
        font-size: 0.85rem !important;
        font-weight: 700 !important;
        color: #64748b !important;
        text-transform: none !important; /* Forces normal casing */
    }

    /* 8. RESPONSIVE FIX */
    @media (max-width: 1100px) {
        .tabs-bar { 
            overflow-x: auto !important; 
            flex-wrap: nowrap !important; 
            scrollbar-width: none; 
        }
        .tabs-bar::-webkit-scrollbar { display: none; }
        #analytics-tabs button.tab-btn { min-width: 140px !important; }
    }

    /* ══════════════════════════════════════════════════
       9. GRADIENT DESIGN — CARDS, PANELS & BUTTONS
    ══════════════════════════════════════════════════ */

    /* Page header pill */
    #analytics-dashboard > .mb-4 {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 55%, #eff6ff 100%);
        border-radius: 14px;
        padding: 1rem 1.25rem;
        border: 1px solid #bbf7d0;
    }

    /* Tab bar — gradient track */
    .tabs-bar {
        background: linear-gradient(135deg, #e2e8f0 0%, #f1f5f9 55%, #e2e8f0 100%) !important;
        border: 1px solid #cbd5e1;
    }

    /* Filter panel — green-tinted gradient */
    .section-card.section-accent-primary {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 60%, #eff6ff 100%) !important;
        border: 1px solid #bbf7d0 !important;
    }

    /* ── KPI cards: gradient per colour ── */
    .kpi-card {
        border-radius: 12px !important;
        overflow: hidden;
        position: relative;
        min-height: 100px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08) !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease !important;
    }
    .kpi-card:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 22px rgba(0,0,0,0.13) !important;
    }

    /* 50% colour → white — light enough to read dark text, colour still obvious */
    .kpi-card-blue  { background: linear-gradient(135deg, #bfdbfe 0%, #dbeafe 55%, #f8fafc 100%) !important; border: none !important; box-shadow: 0 4px 18px rgba(59,130,246,0.30), 0 1px 4px rgba(0,0,0,0.06) !important; }
    .kpi-card-amber { background: linear-gradient(135deg, #fde68a 0%, #fef3c7 55%, #fffdf5 100%) !important; border: none !important; box-shadow: 0 4px 18px rgba(217,119,6,0.28),  0 1px 4px rgba(0,0,0,0.06) !important; }
    .kpi-card-green { background: linear-gradient(135deg, #86efac 0%, #dcfce7 55%, #f8fff9 100%) !important; border: none !important; box-shadow: 0 4px 18px rgba(22,163,74,0.28),   0 1px 4px rgba(0,0,0,0.06) !important; }
    .kpi-card-red   { background: linear-gradient(135deg, #fca5a5 0%, #fee2e2 55%, #fff8f8 100%) !important; border: none !important; box-shadow: 0 4px 18px rgba(220,38,38,0.25),  0 1px 4px rgba(0,0,0,0.06) !important; }
    .kpi-card-cyan  { background: linear-gradient(135deg, #67e8f9 0%, #cffafe 55%, #f0fcff 100%) !important; border: none !important; box-shadow: 0 4px 18px rgba(6,182,212,0.28),   0 1px 4px rgba(0,0,0,0.06) !important; }
    .kpi-card-rose  { background: linear-gradient(135deg, #fda4af 0%, #ffe4e6 55%, #fff8f9 100%) !important; border: none !important; box-shadow: 0 4px 18px rgba(225,29,72,0.25),  0 1px 4px rgba(0,0,0,0.06) !important; }

    .kpi-card:hover {
        transform: translateY(-3px) !important;
    }
    .kpi-card-blue:hover  { box-shadow: 0 10px 28px rgba(59,130,246,0.40),  0 2px 6px rgba(0,0,0,0.08) !important; }
    .kpi-card-amber:hover { box-shadow: 0 10px 28px rgba(217,119,6,0.38),   0 2px 6px rgba(0,0,0,0.08) !important; }
    .kpi-card-green:hover { box-shadow: 0 10px 28px rgba(22,163,74,0.38),    0 2px 6px rgba(0,0,0,0.08) !important; }
    .kpi-card-red:hover   { box-shadow: 0 10px 28px rgba(220,38,38,0.35),   0 2px 6px rgba(0,0,0,0.08) !important; }
    .kpi-card-cyan:hover  { box-shadow: 0 10px 28px rgba(6,182,212,0.38),    0 2px 6px rgba(0,0,0,0.08) !important; }
    .kpi-card-rose:hover  { box-shadow: 0 10px 28px rgba(225,29,72,0.35),   0 2px 6px rgba(0,0,0,0.08) !important; }

    /* Dark text on light-gradient cards */
    .kpi-card-blue  .kpi-label, .kpi-card-blue  .kpi-value, .kpi-card-blue  i  { color: #1e40af !important; }
    .kpi-card-amber .kpi-label, .kpi-card-amber .kpi-value, .kpi-card-amber i  { color: #92400e !important; }
    .kpi-card-green .kpi-label, .kpi-card-green .kpi-value, .kpi-card-green i  { color: #14532d !important; }
    .kpi-card-red   .kpi-label, .kpi-card-red   .kpi-value, .kpi-card-red   i  { color: #7f1d1d !important; }
    .kpi-card-cyan  .kpi-label, .kpi-card-cyan  .kpi-value, .kpi-card-cyan  i  { color: #164e63 !important; }
    .kpi-card-rose  .kpi-label, .kpi-card-rose  .kpi-value, .kpi-card-rose  i  { color: #881337 !important; }
    .kpi-card-blue  .kpi-sub, .kpi-card-amber .kpi-sub, .kpi-card-green .kpi-sub,
    .kpi-card-red   .kpi-sub, .kpi-card-cyan  .kpi-sub, .kpi-card-rose  .kpi-sub {
        color: #475569 !important;
    }
    .kpi-card-blue  .kpi-value, .kpi-card-amber .kpi-value, .kpi-card-green .kpi-value,
    .kpi-card-red   .kpi-value, .kpi-card-cyan  .kpi-value, .kpi-card-rose  .kpi-value {
        font-size: 2.1rem !important;
    }

    /* ── Chart section panels — NEUTRAL white container so colored cards pop ──
       Must kill ::before (top accent bar) and ::after (radial gradient overlay)
       because background: #fff alone cannot override pseudo-elements. */
    [data-tab-panel="trend"]      > .section-card,
    [data-tab-panel="severity"]   > .section-card,
    [data-tab-panel="department"] > .section-card,
    [data-tab-panel="timeline"]   > .section-card,
    [data-tab-panel="overdue"]    > .section-card {
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 2px 16px rgba(0,0,0,0.07) !important;
    }
    [data-tab-panel="trend"]      > .section-card::before,
    [data-tab-panel="severity"]   > .section-card::before,
    [data-tab-panel="department"] > .section-card::before,
    [data-tab-panel="timeline"]   > .section-card::before,
    [data-tab-panel="overdue"]    > .section-card::before,
    [data-tab-panel="trend"]      > .section-card::after,
    [data-tab-panel="severity"]   > .section-card::after,
    [data-tab-panel="department"] > .section-card::after,
    [data-tab-panel="timeline"]   > .section-card::after,
    [data-tab-panel="overdue"]    > .section-card::after {
        display: none !important;
    }

    /* ── Chart wrap: pure white card, strong shadow — floats above the neutral container ── */
    [data-tab-panel="trend"] .chart-wrap,
    [data-tab-panel="severity"] .chart-wrap,
    [data-tab-panel="department"] .chart-wrap,
    [data-tab-panel="timeline"] .chart-wrap,
    [data-tab-panel="overdue"] .chart-wrap {
        background: #ffffff !important;
        background-image: none !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 6px 24px rgba(0,0,0,0.11), 0 1px 4px rgba(0,0,0,0.06) !important;
    }

    /* ── Chart header: title flush against content, no excess gap ── */
    .analytics-chart-header {
        padding-bottom: 0.5rem !important;
        margin-bottom: 0.75rem !important;
    }
    .analytics-chart-header h2 {
        margin-bottom: 0.1rem !important;
    }
    .analytics-chart-header .chart-subtitle {
        margin-bottom: 0 !important;
    }

    /* ── Side cards: colored per tab theme — NO border, shadow only for depth ── */
    [data-tab-panel="trend"] .analytics-side-card {
        background: linear-gradient(145deg, #bfdbfe 0%, #dbeafe 55%, #eff6ff 100%) !important;
        border: none !important;
        box-shadow: 0 6px 22px rgba(59,130,246,0.28), 0 1px 4px rgba(0,0,0,0.07) !important;
    }
    [data-tab-panel="severity"] .analytics-side-card {
        background: linear-gradient(145deg, #fde68a 0%, #fef3c7 55%, #fffbeb 100%) !important;
        border: none !important;
        box-shadow: 0 6px 22px rgba(217,119,6,0.26), 0 1px 4px rgba(0,0,0,0.07) !important;
    }
    [data-tab-panel="department"] .analytics-side-card {
        background: linear-gradient(145deg, #67e8f9 0%, #cffafe 55%, #f0f9ff 100%) !important;
        border: none !important;
        box-shadow: 0 6px 22px rgba(6,182,212,0.26), 0 1px 4px rgba(0,0,0,0.07) !important;
    }
    [data-tab-panel="timeline"] .analytics-side-card {
        background: linear-gradient(145deg, #86efac 0%, #dcfce7 55%, #f0fdf4 100%) !important;
        border: none !important;
        box-shadow: 0 6px 22px rgba(22,163,74,0.26), 0 1px 4px rgba(0,0,0,0.07) !important;
    }
    [data-tab-panel="overdue"] .analytics-side-card {
        background: linear-gradient(145deg, #fca5a5 0%, #fee2e2 55%, #fff1f2 100%) !important;
        border: none !important;
        box-shadow: 0 6px 22px rgba(220,38,38,0.22), 0 1px 4px rgba(0,0,0,0.07) !important;
    }

    /* ── Side card title badge ── */
    .analytics-side-title {
        display: inline-block;
        font-size: 0.7rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.07em !important;
        text-transform: uppercase !important;
        padding: 0.2rem 0.65rem !important;
        border-radius: 20px !important;
        margin-bottom: 0.55rem !important;
    }
    [data-tab-panel="trend"] .analytics-side-title      { background: #1d4ed8; color: #fff !important; }
    [data-tab-panel="severity"] .analytics-side-title   { background: #b45309; color: #fff !important; }
    [data-tab-panel="department"] .analytics-side-title { background: #0e7490; color: #fff !important; }
    [data-tab-panel="timeline"] .analytics-side-title   { background: #15803d; color: #fff !important; }
    [data-tab-panel="overdue"] .analytics-side-title    { background: #b91c1c; color: #fff !important; }
        background: #ffffff;
        border-radius: 12px;
        padding: 1rem;
        box-shadow: 0 2px 14px rgba(0,0,0,0.07);
    }

    /* Side info cards — base style (per-tab rules below override color/shadow) */
    .analytics-side-card {
        border-radius: 10px !important;
        border: none !important;
        box-shadow: 0 4px 14px rgba(0,0,0,0.10) !important;
    }
    /* Insight card — base: just shape + shadow, NO background override
       (per-tab rules below own the background so each tab gets its own color) */
    .insight-card {
        border-radius: 10px !important;
        border: none !important;
    }
    .insight-card::before {
        background-color: #22c55e !important; /* fallback if no tab match */
    }

    /* Per-tab insight card colors + matching left bar */
    [data-tab-panel="trend"] .insight-card {
        background: linear-gradient(145deg, #bfdbfe 0%, #dbeafe 55%, #eff6ff 100%) !important;
        box-shadow: 0 6px 20px rgba(59,130,246,0.22), 0 1px 4px rgba(0,0,0,0.07) !important;
    }
    [data-tab-panel="trend"] .insight-card::before { background-color: #1d4ed8 !important; }

    [data-tab-panel="severity"] .insight-card {
        background: linear-gradient(145deg, #fde68a 0%, #fef3c7 55%, #fffbeb 100%) !important;
        box-shadow: 0 6px 20px rgba(217,119,6,0.22), 0 1px 4px rgba(0,0,0,0.07) !important;
    }
    [data-tab-panel="severity"] .insight-card::before { background-color: #b45309 !important; }

    [data-tab-panel="department"] .insight-card {
        background: linear-gradient(145deg, #67e8f9 0%, #cffafe 55%, #f0f9ff 100%) !important;
        box-shadow: 0 6px 20px rgba(6,182,212,0.22), 0 1px 4px rgba(0,0,0,0.07) !important;
    }
    [data-tab-panel="department"] .insight-card::before { background-color: #0e7490 !important; }

    [data-tab-panel="timeline"] .insight-card {
        background: linear-gradient(145deg, #86efac 0%, #dcfce7 55%, #f0fdf4 100%) !important;
        box-shadow: 0 6px 20px rgba(22,163,74,0.22), 0 1px 4px rgba(0,0,0,0.07) !important;
    }
    [data-tab-panel="timeline"] .insight-card::before { background-color: #15803d !important; }

    [data-tab-panel="overdue"] .insight-card {
        background: linear-gradient(145deg, #fca5a5 0%, #fee2e2 55%, #fff1f2 100%) !important;
        box-shadow: 0 6px 20px rgba(220,38,38,0.20), 0 1px 4px rgba(0,0,0,0.07) !important;
    }
    [data-tab-panel="overdue"] .insight-card::before { background-color: #b91c1c !important; }
    /* Overdue table wrapper */
    .table-container.table-card {
        background: linear-gradient(160deg, #fff1f2 0%, #fff8f8 55%, #ffffff 100%) !important;
        border-radius: 12px !important;
        border: 1px solid #fecdd3 !important;
    }
    /* KPI section header strip */
    [data-tab-panel="metrics"] .d-flex.align-items-end.border-bottom {
        background: linear-gradient(90deg, #f0fdf4 0%, #ffffff 100%);
        border-radius: 8px 8px 0 0;
        padding: 0.5rem 0.75rem !important;
        margin-bottom: 0.75rem !important;
    }

    /* Overdue table header — light red, 50/50 tint */
    [data-tab-panel="overdue"] thead tr th {
        background: linear-gradient(90deg, #fca5a5 0%, #fee2e2 100%) !important;
        color: #7f1d1d !important;
        border-bottom: 2px solid #f87171 !important;
        font-weight: 700 !important;
    }

    /* ── Refresh button — gradient green ── */
    #af-apply {
        background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%) !important;
        box-shadow: 0 4px 14px rgba(34,197,94,0.40) !important;
        transition: all 0.2s ease !important;
        border: none !important;
    }
    #af-apply:hover {
        background: linear-gradient(135deg, #14532d 0%, #16a34a 100%) !important;
        box-shadow: 0 6px 20px rgba(34,197,94,0.50) !important;
        transform: translateY(-1px);
    }
    #af-apply:active { transform: translateY(0); }

    /* ── Download buttons ── */
    #download-csv, #download-pdf {
        border: 1.5px solid #22c55e !important;
        color: #15803d !important;
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%) !important;
        font-weight: 700 !important;
        border-radius: 8px !important;
        transition: all 0.2s ease !important;
        padding: 0.35rem 0.9rem !important;
        font-size: 0.78rem !important;
    }
    #download-csv:hover, #download-pdf:hover {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
        color: #ffffff !important;
        border-color: #16a34a !important;
        box-shadow: 0 4px 14px rgba(34,197,94,0.35) !important;
        transform: translateY(-1px);
        text-decoration: none;
    }
</style>

<main class="main-content">
  <div class="animate-fade-in" id="analytics-dashboard" data-api-url="<?php echo htmlspecialchars($apiUrl); ?>">

    <div class="mb-4 d-flex align-items-start justify-content-between gap-3 flex-wrap">
      <div>
        <h1 class="h4 fw-bold text-foreground mb-1"><i class="bi bi-bar-chart-line-fill me-2 text-primary"></i>Executive Analytics Dashboard</h1>
        <p class="text-sm text-muted-foreground mb-0">System performance, risk profile, and trend tracking</p>
      </div>
      <div class="d-flex align-items-center gap-2 flex-shrink-0 align-self-end">
        <span class="text-xs text-muted-foreground" id="download-hint"></span>
        <a id="download-csv" href="#" class="btn btn-outline btn-sm d-flex align-items-center gap-1" title="Download Excel (XLSX)">
          <i class="bi bi-file-earmark-excel"></i> Excel
        </a>
        <a id="download-pdf" href="#" class="btn btn-outline btn-sm d-flex align-items-center gap-1" title="Download PDF">
          <i class="bi bi-file-earmark-pdf"></i> PDF
        </a>
      </div>
    </div>


    <div class="section-card section-accent-primary mb-3">
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap cursor-pointer" id="toggle-filters-btn" style="user-select: none;">
        <div class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="3">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
            </svg>
            <h3 class="font-semibold text-foreground uppercase mt-1" style="font-size: 0.9rem; letter-spacing: 0.05em; font-weight: 700; margin-bottom: 0; margin-left: 10px;">Filter Analytics</h3>
        </div>
        <div class="d-flex align-items-center gap-2 text-muted-foreground transition-colors">
            <span class="text-xs font-bold uppercase" id="filter-status-text">Hide Filters</span>
            <svg id="filter-chevron" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="transition-transform duration-200">
                <polyline points="18 15 12 9 6 15"></polyline>
            </svg>
        </div>
    </div>

    <div id="filter-collapsible-content" class="mt-4 transition-all duration-300 overflow-hidden">
        <form id="analytics-filters" class="row g-2 align-items-end" onsubmit="return false;">
            
            <div class="col-6 col-md-2">
                <label class="form-label text-xs fw-bold text-muted-foreground mb-1 uppercase">Start Date</label>
                <input type="date" class="form-control form-control-sm border-0 shadow-sm" name="start_date" id="af-start" 
                       style="background-color: #c3e6cb; color: #155724; font-weight: 600; border-radius: 6px; height: 38px;" />
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label text-xs fw-bold text-muted-foreground mb-1 uppercase">End Date</label>
                <input type="date" class="form-control form-control-sm border-0 shadow-sm" name="end_date" id="af-end" 
                       style="background-color: #c3e6cb; color: #155724; font-weight: 600; border-radius: 6px; height: 38px;" />
            </div>

            <div class="col-12 col-md-2">
                <label class="form-label text-xs fw-bold text-muted-foreground mb-1 uppercase">Building</label>
                <?php if ($role === 'security'): ?>
                    <div class="analytics-readonly px-2 d-flex align-items-center" style="background-color: #e9ecef; border-radius: 6px; height: 38px;">
                        <span class="text-sm fw-bold text-muted-foreground"><?php echo htmlspecialchars($userBuilding ?: '—'); ?></span>
                    </div>
                    <input type="hidden" name="building" id="af-building" value="<?php echo htmlspecialchars($userBuilding ?: ''); ?>" />
                <?php elseif ($canChooseBuilding): ?>
                    <select name="building" id="af-building" class="form-select form-select-sm border-0 shadow-sm" 
                            style="background-color: #c3e6cb; color: #155724; font-weight: 600; border-radius: 6px; cursor: pointer; height: 38px;">
                        <option value="">All Buildings</option>
                        <option value="NCFL">NCFL</option>
                        <option value="NPFL">NPFL</option>
                    </select>
                <?php else: ?>
                    <input type="hidden" name="building" id="af-building" value="" />
                <?php endif; ?>
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label text-xs fw-bold text-muted-foreground mb-1 uppercase">Department</label>
                <?php if ($canSeeAll): ?>
                    <select name="department_id" id="af-dept" class="form-select form-select-sm border-0 shadow-sm" 
                            style="background-color: #c3e6cb; color: #155724; font-weight: 600; border-radius: 6px; cursor: pointer; height: 38px;">
                        <option value="0">All Departments</option>
                        <?php foreach ($departmentsDb as $d): ?>
                            <option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <div class="analytics-readonly px-2 d-flex align-items-center" style="background-color: #e9ecef; border-radius: 6px; height: 38px;">
                        <span class="text-sm fw-bold text-muted-foreground">Your Dept Only</span>
                    </div>
                    <input type="hidden" name="department_id" id="af-dept" value="<?php echo (int)$deptId; ?>" />
                <?php endif; ?>
            </div>

            <div class="col-12 col-md-3">
                <button type="button" id="af-apply" class="btn w-100 fw-bold d-flex align-items-center justify-content-center gap-2" 
                        style="background-color: #28a745 !important; color: white !important; border: none; border-radius: 6px; height: 38px; transition: 0.2s;">
                    <i class="bi bi-arrow-clockwise" style="-webkit-text-stroke: 1px;"></i>
                    REFRESH ANALYTICS
                </button>
            </div>
        </form>
    </div>
</div>

    <!-- Tabs -->
    <div class="tabs-bar" id="analytics-tabs" role="tablist" aria-label="Analytics Sections">
    <button type="button" class="tab-btn active" role="tab" aria-selected="true" data-tab="metrics">Key Metrics</button>
    <button type="button" class="tab-btn" role="tab" aria-selected="false" data-tab="trend">Report Trend</button>
    <button type="button" class="tab-btn" role="tab" aria-selected="false" data-tab="severity">Severity Distribution</button>
    <button type="button" class="tab-btn" role="tab" aria-selected="false" data-tab="department">Reports by Department</button>
    <button type="button" class="tab-btn" role="tab" aria-selected="false" data-tab="timeline">Timeline Performance</button>
    <button type="button" class="tab-btn" role="tab" aria-selected="false" data-tab="overdue">Overdue Alerts</button>
    </div>

    <!-- SECTION 1 — KPI PERFORMANCE CARDS -->
    <section class="analytics-panel" data-tab-panel="metrics">
  <div class="d-flex align-items-end justify-content-between pb-2 mb-2 border-bottom">
    <div>
      <h2 class="text-lg font-bold text-foreground mb-0">Executive Key Performance Indicators</h2>
    </div>
    <div class="text-end">
      <p class="text-xs fw-bold text-muted-foreground mb-0 tracking-tight" id="analytics-range" style="text-transform: none !important;">
        Range: 2026-02-01 to 2026-03-02 • Department: All Departments
      </p>
    </div>
  </div>

  <div class="row g-2">
    <div class="col-12 col-md-4">
      <div class="kpi-card kpi-card-blue h-100 p-2 p-md-3">
        <i class="bi bi-file-earmark-text mb-2"></i>
        <div class="d-flex justify-content-between align-items-center">
          <div class="kpi-label">Total Reports</div>
          <div class="kpi-value" id="kpi-total">0</div>
        </div>
        <div class="kpi-sub mt-1">Within selected filters</div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="kpi-card kpi-card-amber h-100 p-2 p-md-3">
        <i class="bi bi-envelope-open mb-2"></i>
        <div class="d-flex justify-content-between align-items-center">
          <div class="kpi-label">Open Reports</div>
          <div class="kpi-value" id="kpi-open">0</div>
        </div>
        <div class="kpi-sub mt-1">Not yet fully resolved</div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="kpi-card kpi-card-green h-100 p-2 p-md-3">
        <i class="bi bi-check-circle mb-2"></i>
        <div class="d-flex justify-content-between align-items-center">
          <div class="kpi-label">Resolved Reports</div>
          <div class="kpi-value" id="kpi-resolved">0</div>
        </div>
        <div class="kpi-sub mt-1">Closed within the range</div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="kpi-card kpi-card-red h-100 p-2 p-md-3">
        <i class="bi bi-exclamation-octagon mb-2"></i>
        <div class="d-flex justify-content-between align-items-center">
          <div class="kpi-label">Overdue Reports</div>
          <div class="kpi-value" id="kpi-overdue">0</div>
        </div>
        <div class="kpi-sub mt-1">Past due while under fix</div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="kpi-card kpi-card-cyan h-100 p-2 p-md-3">
        <i class="bi bi-clock-history mb-2"></i>
        <div class="d-flex justify-content-between align-items-center">
          <div class="kpi-label">Avg Resolution Time</div>
          <div class="kpi-value" id="kpi-avg-days">N/A</div>
        </div>
        <div class="kpi-sub mt-1">Resolved reports only</div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="kpi-card kpi-card-rose h-100 p-2 p-md-3">
        <i class="bi bi-shield-shaded mb-2"></i>
        <div class="d-flex justify-content-between align-items-center">
          <div class="kpi-label">High Severity</div>
          <div class="kpi-value" id="kpi-high-sev">0</div>
        </div>
        <div class="kpi-sub mt-1">High + Critical severity</div>
      </div>
    </div>
  </div>
</section>
    <!-- SECTION 2 — REPORT TREND GRAPH (LINE CHART) -->
    <section class="analytics-panel hidden" data-tab-panel="trend">
      <div class="section-card section-accent-primary chart-card">
        <div class="analytics-chart-header">
          <div>
            <h2 class="text-lg font-bold text-foreground">Report Trend</h2>
            <p class="chart-subtitle" id="subtitle-trend">Loading…</p>
          </div>
        </div>

        <div class="row g-3 align-items-stretch">
          <div class="col-12 col-lg-8">
            <div class="chart-wrap h-100">
              <canvas id="chart-trend" height="320"></canvas>
            </div>
          </div>

          <div class="col-12 col-lg-4">
            <div class="analytics-side-stack">
              <div class="analytics-side-card">
                <div class="analytics-side-title">View</div>
                <select id="trend-mode" class="form-select form-select-sm w-100">
                  <option value="daily">Daily (Last 7 Days)</option>
                  <option value="weekly">Weekly (Last 4 Weeks)</option>
                  <option value="monthly">Monthly (Last 12 Months)</option>
                </select>
                <div class="text-sm text-muted-foreground mt-2">Switch time scale to compare patterns.</div>
              </div>

              <div class="insight-card hidden" id="insight-trend" role="status" aria-live="polite">
                <div class="insight-label">Insight</div>
                <p class="insight-text" id="caption-trend"></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="analytics-panel hidden" data-tab-panel="severity">
      <div class="section-card section-accent-warning chart-card">
        <div class="analytics-chart-header">
          <div>
            <h2 class="text-lg font-bold text-foreground">Severity Distribution</h2>
            <p class="chart-subtitle" id="subtitle-severity">Loading…</p>
          </div>
        </div>

        <div class="row g-3 align-items-stretch">
          <div class="col-12 col-lg-7">
            <div class="chart-wrap h-100">
              <canvas id="chart-severity" height="320"></canvas>
            </div>
          </div>
          <div class="col-12 col-lg-5">
            <div class="analytics-side-stack">
              <div class="analytics-side-card">
                <div class="analytics-side-title">Legend</div>
                <div class="chart-legend" id="severity-legend"></div>
              </div>
              <div class="insight-card hidden" id="insight-severity" role="status" aria-live="polite">
                <div class="insight-label">Insight</div>
                <p class="insight-text" id="caption-severity"></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="analytics-panel hidden" data-tab-panel="department">
      <div class="section-card section-accent-info chart-card">
        <div class="analytics-chart-header">
          <div>
            <h2 class="text-lg font-bold text-foreground">Reports by Department</h2>
            <p class="chart-subtitle" id="subtitle-department">Loading…</p>
          </div>
        </div>

        <div class="row g-3 align-items-stretch">
          <div class="col-12 col-lg-8">
            <div class="chart-wrap h-100">
              <canvas id="chart-department" height="320"></canvas>
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <div class="analytics-side-stack">
              <div class="analytics-side-card">
                <div class="analytics-side-title">Reading</div>
                <div class="text-sm text-muted-foreground">Highlights the departments contributing most reports within the selected filters.</div>
              </div>

              <div class="insight-card hidden" id="insight-department" role="status" aria-live="polite">
                <div class="insight-label">Insight</div>
                <p class="insight-text" id="caption-department"></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION 5 — TIMELINE PERFORMANCE -->
    <section class="analytics-panel hidden" data-tab-panel="timeline">
      <div class="section-card section-accent-success chart-card">
        <div class="analytics-chart-header">
          <div>
            <h2 class="text-lg font-bold text-foreground">Timeline Performance</h2>
            <p class="chart-subtitle" id="subtitle-timeline">Loading…</p>
          </div>
        </div>

        <div class="row g-3 align-items-stretch">
          <div class="col-12 col-lg-8">
            <div class="chart-wrap h-100">
              <canvas id="chart-timeline" height="260"></canvas>
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <div class="analytics-side-stack">
              <div class="analytics-side-card">
                <div class="analytics-side-title">Compliance</div>
                <div class="analytics-side-metric">
                  <div class="label">Rate</div>
                  <div class="value" id="timeline-rate">N/A</div>
                </div>
                <div class="text-sm text-muted-foreground mt-2">Measures on-time completion vs. overdue work.</div>
              </div>

              <div class="insight-card hidden" id="insight-timeline" role="status" aria-live="polite">
                <div class="insight-label">Insight</div>
                <p class="insight-text" id="caption-timeline"></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION 6 — OVERDUE ALERT TABLE -->
    <section class="analytics-panel hidden" data-tab-panel="overdue">
    <div class="section-card section-accent-destructive chart-card">
      <div class="analytics-chart-header">
        <div>
          <h2 class="text-lg font-bold text-foreground">Overdue Alerts</h2>
          <p class="chart-subtitle">Items past due while still under fix or returned to department</p>
        </div>
      </div>

      <div class="row g-3 align-items-stretch">
        <div class="col-12 col-xl-8">
          <div class="table-container table-card h-100" style="--table-accent: var(--destructive);">
            <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Report ID</th>
                  <th>Department</th>
                  <th>Due Date</th>
                  <th>Days Overdue</th>
                </tr>
              </thead>
              <tbody id="overdue-body">
                <tr><td colspan="4" class="text-center text-muted-foreground">Loading…</td></tr>
              </tbody>
            </table>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-4">
          <div class="analytics-side-stack">
            <div class="analytics-side-card">
              <div class="analytics-side-title">Action</div>
              <div class="text-sm text-muted-foreground">Use this list to prioritize follow-ups with departments and verify evidence uploads once fixes are completed.</div>
            </div>
            <div class="analytics-side-card">
              <div class="analytics-side-title">Tip</div>
              <div class="text-sm text-muted-foreground">Narrow results using filters (date range, building, department) to focus on a specific area.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    </section>

  </div>
</main>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggle-filters-btn');
    const content = document.getElementById('filter-collapsible-content');
    const chevron = document.getElementById('filter-chevron');
    const statusText = document.getElementById('filter-status-text');

    if (toggleBtn && content) {
        // Function to toggle the filter state
        function toggleFilters(isManualAction = true) {
            const isCollapsed = content.classList.toggle('collapsed');
            chevron.classList.toggle('rotated', isCollapsed);
            statusText.textContent = isCollapsed ? 'Show Filters' : 'Hide Filters';
            
            if (isManualAction) {
                localStorage.setItem('analytics_filters_collapsed', isCollapsed);
            }
        }

        // Handle click event
        toggleBtn.addEventListener('click', () => toggleFilters(true));

        // Check localStorage to remember user's last preference
        const savedState = localStorage.getItem('analytics_filters_collapsed');
        if (savedState === 'true') {
            content.classList.add('collapsed');
            chevron.classList.add('rotated');
            statusText.textContent = 'Show Filters';
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
