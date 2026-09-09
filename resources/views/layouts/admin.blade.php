{{-- ============================================================
     resources/views/layouts/admin.blade.php
     Layout utama panel admin (Yayasan & Unit Sekolah).
     - Sidebar responsif (off-canvas di mobile, collapsible di desktop)
     - Judul navbar dinamis mengikuti halaman aktif
     - Notifikasi real-time (polling) + tandai sudah dibaca
     ============================================================ --}}
@php
    /** @var \App\Models\User|null $authUser */
    $authUser   = auth()->user();
    $pageTitle  = trim($__env->yieldContent('page-title')) ?: \App\Helpers\PageTitleHelper::current();
    $pageIcon   = trim($__env->yieldContent('page-icon')) ?: \App\Helpers\PageTitleHelper::icon();
    $unitLabel  = $authUser && $authUser->isSuperAdmin() ? 'Yayasan' : ($authUser?->unit?->label() ?? 'Sekolah');
    $unreadNotif = $authUser
        ? \App\Models\Notification::where('user_id', $authUser->id)->whereNull('read_at')->count()
        : 0;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} — Salvras {{ $unitLabel }}</title>

    <link rel="icon" type="image/png" href="{{ asset('image/logo.png') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:      #1E3A5F;
            --navy-d:    #152C48;
            --navy-l:    #2A4F80;
            --green:     #2ECC71;
            --green-d:   #27AE60;
            --blue:      #2563EB;
            --amber:     #D97706;
            --red:       #DC2626;
            --ink:       #0F172A;
            --gray:      #64748B;
            --gray-l:    #94A3B8;
            --border:    #E2E8F0;
            --slate:     #F8FAFC;
            --white:     #FFFFFF;
            --sidebar-w: 264px;
            --sidebar-collapsed: 72px;
            --topbar-h:  62px;
            --radius:    14px;
            --shadow-sm: 0 1px 2px rgba(15,23,42,.06);
            --shadow:    0 4px 14px rgba(15,23,42,.07);
            --shadow-lg: 0 18px 40px -12px rgba(15,23,42,.28);
        }

        html { -webkit-text-size-adjust: 100%; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--slate);
            color: var(--ink);
            min-height: 100vh;
            line-height: 1.55;
            overflow-x: hidden;
        }
        img { max-width: 100%; }
        i[data-lucide], svg.lucide {
            display: inline-block; vertical-align: middle;
            width: 1.15em; height: 1.15em; stroke-width: 2.1;
            flex-shrink: 0;
        }

        /* ══════════════ SIDEBAR ══════════════ */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--navy-d);
            display: flex; flex-direction: column;
            position: fixed; inset: 0 auto 0 0;
            z-index: 60;
            transition: width .22s ease, transform .22s ease;
            overflow: hidden;
        }
        .sidebar-brand {
            display: flex; align-items: center; gap: 10px;
            padding: 16px 14px;
            border-bottom: 1px solid rgba(255,255,255,.07);
            flex-shrink: 0;
        }
        .brand-icon {
            width: 36px; height: 36px; flex-shrink: 0;
            background: var(--green); border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; box-shadow: 0 2px 10px rgba(46,204,113,.35);
        }
        .brand-icon--yayasan { background: var(--amber); box-shadow: 0 2px 10px rgba(217,119,6,.35); }
        .brand-text { flex: 1; min-width: 0; }
        .brand-name { display: block; font-size: .92rem; font-weight: 800; color: #fff; letter-spacing: -.02em; white-space: nowrap; }
        .brand-unit { display: block; font-size: .68rem; color: var(--green); font-weight: 700; letter-spacing: .05em; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .brand-unit--yayasan { color: #FBBF24; }
        .sidebar-toggle {
            background: rgba(255,255,255,.06); border: none; cursor: pointer;
            color: rgba(255,255,255,.55); width: 28px; height: 28px;
            border-radius: 8px; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; transition: color .2s, background .2s, transform .22s;
        }
        .sidebar-toggle:hover { color: #fff; background: rgba(255,255,255,.14); }

        .sidebar-user {
            display: flex; align-items: center; gap: 10px;
            padding: 13px 14px;
            border-bottom: 1px solid rgba(255,255,255,.07);
            flex-shrink: 0;
        }
        .user-avatar {
            width: 36px; height: 36px; flex-shrink: 0; border-radius: 50%;
            background: linear-gradient(135deg, var(--navy-d), var(--navy-l));
            display: flex; align-items: center; justify-content: center;
            font-size: .76rem; font-weight: 800; color: #fff;
        }
        .user-avatar--yayasan { background: linear-gradient(135deg, var(--navy-d), var(--navy-l)); }
        .user-info { min-width: 0; }
        .user-name { display: block; font-size: .82rem; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-role { font-size: .7rem; color: var(--gray-l); white-space: nowrap; }

        .sidebar-nav {
            flex: 1; overflow-y: auto; overflow-x: hidden;
            padding: 10px 8px 24px;
            scrollbar-width: thin; scrollbar-color: rgba(255,255,255,.14) transparent;
        }
        .sidebar-nav::-webkit-scrollbar { width: 4px; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.14); border-radius: 4px; }

        .nav-item {
            display: flex; align-items: center; gap: 11px;
            padding: 9px 10px; border-radius: 10px;
            color: rgba(255,255,255,.66); text-decoration: none;
            font-size: .855rem; font-weight: 500;
            transition: background .15s, color .15s;
            cursor: pointer; width: 100%; border: none; background: none;
            text-align: left; font-family: inherit;
        }
        .nav-item:hover { background: rgba(255,255,255,.08); color: #fff; }
        .nav-item.active { background: rgba(46,204,113,.16); color: var(--green); font-weight: 700; }
        .sidebar--yayasan .nav-item.active { background: rgba(217,119,6,.18); color: #FBBF24; }
        .nav-item--logout { color: rgba(248,113,113,.85); }
        .nav-item--logout:hover { background: rgba(220,38,38,.14); color: #FCA5A5; }
        .nav-icon { width: 20px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .nav-label { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .nav-badge {
            background: var(--green); color: #fff; font-size: .65rem; font-weight: 800;
            padding: 2px 7px; border-radius: 100px; flex-shrink: 0;
        }
        .nav-badge--alert { background: var(--red); }
        .nav-badge--muted { background: rgba(255,255,255,.14); color: rgba(255,255,255,.75); }

        .nav-divider { display: flex; align-items: center; gap: 8px; margin: 12px 6px 6px; }
        .nav-divider span {
            font-size: .64rem; font-weight: 800; letter-spacing: .12em;
            text-transform: uppercase; color: rgba(255,255,255,.3); white-space: nowrap;
        }
        .nav-divider::after { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,.08); }

        .nav-group { border-radius: 10px; }
        .nav-group-trigger {
            display: flex; align-items: center; gap: 11px;
            padding: 9px 10px; border-radius: 10px;
            color: rgba(255,255,255,.66); font-size: .855rem; font-weight: 500;
            cursor: pointer; width: 100%; border: none; background: none;
            text-align: left; font-family: inherit; transition: background .15s, color .15s;
        }
        .nav-group-trigger:hover { background: rgba(255,255,255,.08); color: #fff; }
        .nav-group.open > .nav-group-trigger { color: #fff; background: rgba(255,255,255,.05); }
        .nav-group-items { display: none; padding: 2px 0 6px 6px; }
        .nav-group.open > .nav-group-items { display: block; }
        .nav-arrow { color: rgba(255,255,255,.35); transition: transform .2s; display: flex; }
        .nav-group.open > .nav-group-trigger .nav-arrow { transform: rotate(90deg); }

        .nav-sub-item {
            display: flex; align-items: center; gap: 9px;
            padding: 7px 10px; border-radius: 9px; margin-left: 4px;
            color: rgba(255,255,255,.5); text-decoration: none;
            font-size: .79rem; font-weight: 500;
            border-left: 1px solid rgba(255,255,255,.08);
            transition: background .15s, color .15s;
        }
        .nav-sub-item:hover { background: rgba(255,255,255,.07); color: #fff; }
        .nav-sub-item.active { background: rgba(46,204,113,.14); color: var(--green); font-weight: 700; }
        .nav-sub-meta { font-size: .68rem; color: rgba(255,255,255,.35); margin-left: auto; }

        /* Collapsed (desktop) */
        .sidebar.collapsed { width: var(--sidebar-collapsed); }
        .sidebar.collapsed .brand-text,
        .sidebar.collapsed .user-info,
        .sidebar.collapsed .nav-label,
        .sidebar.collapsed .nav-arrow,
        .sidebar.collapsed .nav-badge,
        .sidebar.collapsed .nav-sub-meta,
        .sidebar.collapsed .nav-divider span,
        .sidebar.collapsed .nav-group-items { display: none; }
        .sidebar.collapsed .sidebar-brand,
        .sidebar.collapsed .sidebar-user { justify-content: center; padding-left: 8px; padding-right: 8px; }
        .sidebar.collapsed .nav-item,
        .sidebar.collapsed .nav-group-trigger { justify-content: center; padding-left: 0; padding-right: 0; }
        .sidebar.collapsed .nav-divider { border-top: 1px solid rgba(255,255,255,.08); margin: 10px 12px; }
        .sidebar.collapsed .sidebar-toggle { transform: rotate(180deg); }

        /* ══════════════ MAIN ══════════════ */
        .main-wrapper {
            margin-left: var(--sidebar-w);
            display: flex; flex-direction: column;
            min-height: 100vh; min-width: 0;
            transition: margin-left .22s ease;
        }
        .main-wrapper.expanded { margin-left: var(--sidebar-collapsed); }

        .topbar {
            min-height: var(--topbar-h);
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center;
            padding: 10px 20px; gap: 12px;
            position: sticky; top: 0; z-index: 45;
        }
        .topbar-menu-btn {
            background: var(--slate); border: 1px solid var(--border); cursor: pointer;
            color: var(--navy); width: 38px; height: 38px; border-radius: 10px;
            display: none; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .topbar-title {
            display: flex; align-items: center; gap: 9px;
            font-size: 1rem; font-weight: 800; color: var(--navy);
            flex: 1; min-width: 0; letter-spacing: -.01em;
        }
        .topbar-title span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .topbar-title .tt-icon {
            width: 32px; height: 32px; border-radius: 9px; flex-shrink: 0;
            background: rgba(30,58,95,.07); color: var(--navy);
            display: flex; align-items: center; justify-content: center;
        }
        .topbar-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
        .topbar-user {
            display: flex; align-items: center; gap: 9px;
            padding: 5px 10px 5px 6px; border-radius: 100px;
            background: var(--slate); border: 1px solid var(--border);
            text-decoration: none; color: var(--ink); max-width: 220px;
        }
        .topbar-user:hover { border-color: var(--navy-l); }
        .topbar-user .tu-avatar {
            width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
            background: linear-gradient(135deg, var(--navy-d), var(--navy-l));
            color: #fff; font-size: .68rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
        }
        .topbar-user .tu-name { font-size: .8rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .topbar-user .tu-role { font-size: .68rem; color: var(--gray); white-space: nowrap; }

        /* Notifikasi */
        .notif-wrap { position: relative; }
        .notif-btn {
            position: relative; background: var(--slate); border: 1px solid var(--border);
            cursor: pointer; width: 38px; height: 38px; border-radius: 10px;
            color: var(--navy); display: flex; align-items: center; justify-content: center;
            transition: border-color .2s, background .2s;
        }
        .notif-btn:hover { border-color: var(--navy-l); background: #fff; }
        .notif-btn.ring { animation: notifRing .6s ease; }
        @keyframes notifRing { 0%,100% { transform: rotate(0); } 25% { transform: rotate(-12deg); } 75% { transform: rotate(12deg); } }
        .notif-dot {
            position: absolute; top: -5px; right: -5px; min-width: 18px; height: 18px;
            padding: 0 4px; background: var(--red); color: #fff;
            border-radius: 100px; border: 2px solid #fff;
            font-size: .62rem; font-weight: 800; line-height: 14px;
            display: none; align-items: center; justify-content: center;
        }
        .notif-dot.show { display: flex; }
        .notif-panel {
            position: absolute; top: calc(100% + 10px); right: 0;
            width: min(380px, calc(100vw - 32px));
            background: #fff; border: 1px solid var(--border);
            border-radius: var(--radius); box-shadow: var(--shadow-lg);
            overflow: hidden; display: none; z-index: 70;
        }
        .notif-panel.show { display: block; }
        .notif-head {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            padding: 13px 16px; border-bottom: 1px solid var(--border); background: var(--slate);
        }
        .notif-head h4 { font-size: .9rem; font-weight: 800; color: var(--navy); display: flex; align-items: center; gap: 7px; }
        .notif-mark {
            background: none; border: none; cursor: pointer; font-family: inherit;
            font-size: .74rem; font-weight: 700; color: var(--blue);
        }
        .notif-mark:hover { text-decoration: underline; }
        .notif-mark:disabled { color: var(--gray-l); cursor: default; text-decoration: none; }
        .notif-list { max-height: min(60vh, 420px); overflow-y: auto; }
        .notif-item {
            display: flex; gap: 11px; padding: 12px 16px;
            border-bottom: 1px solid #F1F5F9; text-decoration: none; color: inherit;
            background: #fff; transition: background .15s; cursor: pointer;
            width: 100%; text-align: left; border-left: 3px solid transparent;
        }
        .notif-item:hover { background: var(--slate); }
        .notif-item.unread { background: #F5F9FF; border-left-color: var(--blue); }
        .notif-ico {
            width: 32px; height: 32px; border-radius: 9px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; color: #fff;
        }
        .notif-body { min-width: 0; flex: 1; }
        .notif-title { font-size: .82rem; font-weight: 700; color: var(--ink); }
        .notif-msg { font-size: .76rem; color: var(--gray); margin-top: 2px; line-height: 1.45; }
        .notif-time { font-size: .68rem; color: var(--gray-l); margin-top: 4px; display: flex; align-items: center; gap: 4px; }
        .notif-empty { padding: 34px 20px; text-align: center; color: var(--gray-l); font-size: .84rem; }
        .notif-foot { padding: 10px 16px; border-top: 1px solid var(--border); text-align: center; background: var(--slate); }
        .notif-foot a { font-size: .78rem; font-weight: 700; color: var(--navy); text-decoration: none; }
        .notif-foot a:hover { text-decoration: underline; }

        .page-content { flex: 1; padding: 22px clamp(14px, 3vw, 28px) 40px; width: 100%; }

        .sidebar-overlay {
            position: fixed; inset: 0; background: rgba(15,23,42,.5);
            z-index: 55; opacity: 0; pointer-events: none; transition: opacity .2s;
        }
        .sidebar-overlay.show { opacity: 1; pointer-events: auto; }

        /* ══════════════ KOMPONEN UMUM ══════════════ */
        .page-head {
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: 14px; flex-wrap: wrap; margin-bottom: 20px;
        }
        .page-head h1 {
            font-size: clamp(1.15rem, 2.6vw, 1.5rem); font-weight: 800; color: var(--ink);
            display: flex; align-items: center; gap: 9px; letter-spacing: -.02em;
        }
        .page-head p { color: var(--gray); font-size: .86rem; margin-top: 4px; }
        .page-actions { display: flex; gap: 9px; flex-wrap: wrap; }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            padding: 10px 16px; border-radius: 10px; border: 1px solid transparent;
            font-family: inherit; font-size: .84rem; font-weight: 700;
            text-decoration: none; cursor: pointer; transition: filter .18s, box-shadow .18s, background .18s;
            line-height: 1.2;
        }
        .btn:hover { filter: brightness(.96); }
        .btn:active { transform: translateY(1px); }
        .btn-primary { background: var(--navy); color: #fff; box-shadow: 0 4px 12px rgba(30,58,95,.2); }
        .btn-blue    { background: var(--blue); color: #fff; box-shadow: 0 4px 12px rgba(37,99,235,.2); }
        .btn-green   { background: #16A34A; color: #fff; box-shadow: 0 4px 12px rgba(22,163,74,.2); }
        .btn-amber   { background: var(--amber); color: #fff; box-shadow: 0 4px 12px rgba(217,119,6,.2); }
        .btn-red     { background: var(--red); color: #fff; box-shadow: 0 4px 12px rgba(220,38,38,.2); }
        .btn-ghost   { background: #fff; color: var(--navy); border-color: var(--border); }
        .btn-ghost:hover { background: var(--slate); }
        .btn-soft    { background: var(--slate); color: var(--gray); border-color: var(--border); }
        .btn-sm      { padding: 7px 11px; font-size: .76rem; border-radius: 8px; }
        .btn-block   { width: 100%; }
        .btn-icon    { padding: 8px; width: 34px; height: 34px; }

        .card {
            background: #fff; border: 1px solid var(--border);
            border-radius: var(--radius); box-shadow: var(--shadow-sm);
        }
        .card-pad { padding: clamp(14px, 2.4vw, 22px); }
        .card-head {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; flex-wrap: wrap;
            padding: 14px 18px; border-bottom: 1px solid var(--border);
            background: var(--slate); border-radius: var(--radius) var(--radius) 0 0;
        }
        .card-head h3 { font-size: .95rem; font-weight: 800; color: var(--navy); display: flex; align-items: center; gap: 8px; }
        .card-head .muted { font-size: .78rem; color: var(--gray); font-weight: 600; }

        .grid { display: grid; gap: 14px; }
        .grid-stats { grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); }
        .grid-cards { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); }
        .grid-2 { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }

        .stat {
            background: #fff; border: 1px solid var(--border); border-radius: var(--radius);
            padding: 17px 18px; box-shadow: var(--shadow-sm);
        }
        .stat-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .stat-lbl { font-size: .72rem; font-weight: 800; color: var(--gray); text-transform: uppercase; letter-spacing: .05em; }
        .stat-ico { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .stat-val { font-size: clamp(1.3rem, 3vw, 1.7rem); font-weight: 800; color: var(--ink); margin-top: 8px; letter-spacing: -.03em; line-height: 1.1; }
        .stat-val small { font-size: .8rem; font-weight: 700; color: var(--gray); }
        .stat-sub { font-size: .74rem; color: var(--gray); margin-top: 3px; }

        .table-wrap { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table.table { width: 100%; border-collapse: collapse; font-size: .85rem; min-width: 680px; }
        table.table.table-wide { min-width: 900px; }
        table.table th {
            background: #F1F5F9; color: #475569; text-align: left;
            font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase;
            padding: 11px 14px; border-bottom: 1px solid var(--border); white-space: nowrap;
        }
        table.table td { padding: 12px 14px; border-bottom: 1px solid #F1F5F9; vertical-align: middle; }
        table.table tbody tr:hover { background: #FBFDFF; }
        table.table tfoot td { background: var(--slate); font-weight: 800; }
        .td-nowrap { white-space: nowrap; }
        .cell-actions { display: flex; gap: 6px; flex-wrap: wrap; justify-content: center; }

        .badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 9px; border-radius: 100px;
            font-size: .7rem; font-weight: 800; white-space: nowrap;
        }
        .badge-green { background: #DCFCE7; color: #15803D; }
        .badge-amber { background: #FEF3C7; color: #92400E; }
        .badge-red   { background: #FEE2E2; color: #B91C1C; }
        .badge-blue  { background: #DBEAFE; color: #1E40AF; }
        .badge-gray  { background: #F1F5F9; color: #475569; }
        .badge-purple{ background: #F3E8FF; color: #6B21A8; }
        .code {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: .78rem; font-weight: 800; color: #1D4ED8;
            background: #EFF6FF; padding: 3px 7px; border-radius: 6px; white-space: nowrap;
        }

        .alert {
            display: flex; align-items: flex-start; gap: 9px;
            padding: 12px 15px; border-radius: 11px; margin-bottom: 16px;
            font-size: .85rem; font-weight: 600; border: 1px solid transparent;
        }
        .alert-success { background: #DCFCE7; border-color: #86EFAC; color: #15803D; }
        .alert-error   { background: #FEE2E2; border-color: #FCA5A5; color: #B91C1C; }
        .alert-info    { background: #EFF6FF; border-color: #BFDBFE; color: #1E40AF; }
        .alert ul { margin: 4px 0 0 16px; font-weight: 500; }

        .form-grid { display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
        .field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
        .field label { font-size: .78rem; font-weight: 700; color: #334155; }
        .field label .req { color: var(--red); }
        .field .hint { font-size: .72rem; color: var(--gray); }
        .input, .select, .textarea {
            width: 100%; padding: 10px 12px; font-family: inherit; font-size: .86rem;
            border: 1px solid #CBD5E1; border-radius: 10px; background: #fff; color: var(--ink);
            outline: none; transition: border-color .18s, box-shadow .18s;
        }
        .input:focus, .select:focus, .textarea:focus { border-color: var(--navy-l); box-shadow: 0 0 0 3px rgba(42,79,128,.12); }
        .input[disabled], .input[readonly] { background: var(--slate); color: var(--gray); }
        .textarea { resize: vertical; min-height: 84px; }
        .field-error { font-size: .74rem; color: var(--red); font-weight: 700; }

        .empty-state { padding: 46px 20px; text-align: center; color: var(--gray-l); }
        .empty-state strong { display: block; color: var(--gray); font-size: .95rem; margin: 8px 0 4px; }
        .empty-state p { font-size: .82rem; }

        .modal {
            position: fixed; inset: 0; z-index: 90; padding: 16px;
            background: rgba(15,23,42,.6); backdrop-filter: blur(3px);
            display: none; align-items: flex-start; justify-content: center;
            overflow-y: auto;
        }
        .modal.show { display: flex; }
        .modal-box {
            background: #fff; border-radius: 16px; width: 100%; max-width: 520px;
            box-shadow: var(--shadow-lg); margin: min(6vh, 60px) 0;
        }
        .modal-head {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 16px 20px; border-bottom: 1px solid var(--border);
        }
        .modal-head h3 { font-size: 1rem; font-weight: 800; color: var(--ink); }
        .modal-close { background: none; border: none; font-size: 1.15rem; cursor: pointer; color: var(--gray); line-height: 1; }
        .modal-body { padding: 18px 20px; display: flex; flex-direction: column; gap: 14px; }
        .modal-foot { padding: 14px 20px; border-top: 1px solid var(--border); display: flex; gap: 9px; justify-content: flex-end; flex-wrap: wrap; }

        .filter-bar {
            display: grid; gap: 10px; align-items: end;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        }
        .pagination-wrap { margin-top: 16px; display: flex; justify-content: center; }
        .pagination-wrap nav { display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; align-items: center; }
        .pagination-wrap svg { width: 14px; height: 14px; }
        .pagination-wrap a, .pagination-wrap span {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 34px; height: 34px; padding: 0 9px;
            border: 1px solid var(--border); border-radius: 9px;
            background: #fff; color: var(--navy); text-decoration: none;
            font-size: .8rem; font-weight: 700;
        }
        .pagination-wrap [aria-current="page"] span,
        .pagination-wrap span[aria-current="page"] { background: var(--navy); color: #fff; border-color: var(--navy); }
        .pagination-wrap p { font-size: .78rem; color: var(--gray); }

        .scroll-hint { display: none; font-size: .72rem; color: var(--gray-l); padding: 6px 14px; }

        /* ══════════════ RESPONSIVE ══════════════ */
        @media (max-width: 1100px) {
            .sidebar { width: var(--sidebar-collapsed); }
            .sidebar .brand-text, .sidebar .user-info, .sidebar .nav-label,
            .sidebar .nav-arrow, .sidebar .nav-badge, .sidebar .nav-sub-meta,
            .sidebar .nav-divider span, .sidebar .nav-group-items { display: none; }
            .sidebar.wide { width: var(--sidebar-w); }
            .sidebar.wide .brand-text, .sidebar.wide .user-info, .sidebar.wide .nav-label,
            .sidebar.wide .nav-arrow, .sidebar.wide .nav-badge, .sidebar.wide .nav-sub-meta,
            .sidebar.wide .nav-divider span { display: block; }
            .sidebar.wide .nav-group.open > .nav-group-items { display: block; }
            .main-wrapper { margin-left: var(--sidebar-collapsed); }
        }
        @media (max-width: 900px) {
            .sidebar {
                width: var(--sidebar-w) !important;
                transform: translateX(-100%);
                box-shadow: var(--shadow-lg);
            }
            .sidebar .brand-text, .sidebar .user-info, .sidebar .nav-label,
            .sidebar .nav-arrow, .sidebar .nav-badge, .sidebar .nav-sub-meta,
            .sidebar .nav-divider span { display: block; }
            .sidebar .nav-group.open > .nav-group-items { display: block; }
            .sidebar .sidebar-brand, .sidebar .sidebar-user { justify-content: flex-start; padding-left: 14px; padding-right: 14px; }
            .sidebar .nav-item, .sidebar .nav-group-trigger { justify-content: flex-start; padding-left: 10px; padding-right: 10px; }
            .sidebar.mobile-open { transform: translateX(0); }
            .sidebar .sidebar-toggle { display: none; }
            .main-wrapper, .main-wrapper.expanded { margin-left: 0; }
            .topbar-menu-btn { display: flex; }
            .topbar { padding: 10px 14px; }
            .topbar-user .tu-name, .topbar-user .tu-role { display: none; }
            .topbar-user { padding: 4px; }
            .scroll-hint { display: block; }
        }
        @media (max-width: 640px) {
            .page-head { flex-direction: column; align-items: stretch; }
            .page-actions { width: 100%; }
            .page-actions .btn { flex: 1 1 auto; }
            .grid-stats { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
            .card-head { padding: 12px 14px; }
            .modal-box { margin: 10px 0; }
            .filter-bar { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 420px) {
            .filter-bar { grid-template-columns: 1fr; }
            .grid-stats { grid-template-columns: 1fr; }
        }
        @media print {
            .sidebar, .topbar, .sidebar-overlay, .no-print { display: none !important; }
            .main-wrapper { margin-left: 0 !important; }
            .page-content { padding: 0; }
            body { background: #fff; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition: none !important; animation: none !important; }
        }
    </style>
    @yield('styles')
</head>
<body>

    {{-- ══ SIDEBAR ══ --}}
    @if($authUser && $authUser->isSuperAdmin())
        @include('components.sidebar-yayasan')
    @else
        @include('components.sidebar-unit')
    @endif

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    {{-- ══ MAIN ══ --}}
    <div class="main-wrapper" id="mainWrapper">

        <header class="topbar">
            <button class="topbar-menu-btn" onclick="openSidebar()" aria-label="Buka menu navigasi">
                <i data-lucide="menu"></i>
            </button>

            <div class="topbar-title">
                <span class="tt-icon"><i data-lucide="{{ $pageIcon }}"></i></span>
                <span>{{ $pageTitle }}</span>
            </div>

            <div class="topbar-right">
                {{-- Notifikasi --}}
                <div class="notif-wrap">
                    <button class="notif-btn" id="notifBtn" type="button" aria-label="Notifikasi" aria-expanded="false">
                        <i data-lucide="bell"></i>
                        <span class="notif-dot {{ $unreadNotif > 0 ? 'show' : '' }}" id="notifDot">{{ $unreadNotif > 99 ? '99+' : $unreadNotif }}</span>
                    </button>

                    <div class="notif-panel" id="notifPanel" role="dialog" aria-label="Daftar notifikasi">
                        <div class="notif-head">
                            <h4><i data-lucide="bell-ring"></i> Notifikasi</h4>
                            <button class="notif-mark" id="notifMarkAll" type="button">Tandai semua dibaca</button>
                        </div>
                        <div class="notif-list" id="notifList">
                            <div class="notif-empty">Memuat notifikasi…</div>
                        </div>
                        <div class="notif-foot">
                            <a href="{{ route('notifikasi.index') }}">Lihat semua notifikasi →</a>
                        </div>
                    </div>
                </div>

                <a href="{{ route('profile.edit') }}" class="topbar-user" title="Pengaturan akun">
                    <span class="tu-avatar">{{ $authUser?->initials() ?? 'AD' }}</span>
                    <span style="min-width:0;">
                        <span class="tu-name">{{ $authUser->name ?? 'Admin' }}</span><br>
                        <span class="tu-role">{{ $authUser?->roleLabel() ?? 'Admin' }}</span>
                    </span>
                </a>
            </div>
        </header>

        <main class="page-content">
            @if(session('success'))
                <div class="alert alert-success"><i data-lucide="check-circle-2"></i><div>{{ session('success') }}</div></div>
            @endif
            @if(session('error'))
                <div class="alert alert-error"><i data-lucide="alert-triangle"></i><div>{{ session('error') }}</div></div>
            @endif
            @if(session('status') && ! session('success'))
                <div class="alert alert-info"><i data-lucide="info"></i><div>{{ session('status') }}</div></div>
            @endif
            @if($errors->any())
                <div class="alert alert-error">
                    <i data-lucide="alert-octagon"></i>
                    <div>
                        Periksa kembali data yang Anda masukkan:
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        /* ── Ikon Lucide ── */
        function renderIcons() {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
        document.addEventListener('DOMContentLoaded', renderIcons);

        /* ── Sidebar ── */
        const sidebar     = document.getElementById('sidebar');
        const mainWrapper = document.getElementById('mainWrapper');
        const overlay     = document.getElementById('sidebarOverlay');
        const toggleBtn   = document.getElementById('sidebarToggle');
        const MOBILE_BP   = 900;
        const MID_BP      = 1100;

        function isMobile() { return window.innerWidth <= MOBILE_BP; }

        function applyStoredSidebarState() {
            if (!sidebar) return;
            const collapsed = localStorage.getItem('siv_sidebar_collapsed') === '1';
            if (window.innerWidth > MID_BP) {
                sidebar.classList.toggle('collapsed', collapsed);
                mainWrapper.classList.toggle('expanded', collapsed);
                sidebar.classList.remove('wide');
            } else {
                sidebar.classList.remove('collapsed');
                mainWrapper.classList.remove('expanded');
            }
        }
        applyStoredSidebarState();
        window.addEventListener('resize', () => {
            applyStoredSidebarState();
            if (!isMobile()) closeSidebar();
        });

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                if (window.innerWidth > MID_BP) {
                    const collapsed = !sidebar.classList.contains('collapsed');
                    sidebar.classList.toggle('collapsed', collapsed);
                    mainWrapper.classList.toggle('expanded', collapsed);
                    localStorage.setItem('siv_sidebar_collapsed', collapsed ? '1' : '0');
                } else {
                    sidebar.classList.toggle('wide');
                }
            });
        }

        function openSidebar() {
            if (!sidebar) return;
            sidebar.classList.add('mobile-open');
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        function closeSidebar() {
            if (!sidebar) return;
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }

        /* Accordion menu ruangan */
        function toggleGroup(id) {
            const el = document.getElementById(id);
            if (el) el.classList.toggle('open');
        }

        /* ── Modal helper ── */
        function openModal(id) {
            const el = document.getElementById(id);
            if (el) { el.classList.add('show'); renderIcons(); }
        }
        function closeModal(id) {
            const el = document.getElementById(id);
            if (el) el.classList.remove('show');
        }
        document.addEventListener('click', (e) => {
            if (e.target.classList && e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.show').forEach(m => m.classList.remove('show'));
                closeSidebar();
                if (notifPanel) { notifPanel.classList.remove('show'); notifBtn?.setAttribute('aria-expanded', 'false'); }
            }
        });

        /* ══ NOTIFIKASI REAL-TIME ══ */
        const notifBtn     = document.getElementById('notifBtn');
        const notifPanel   = document.getElementById('notifPanel');
        const notifList    = document.getElementById('notifList');
        const notifDot     = document.getElementById('notifDot');
        const notifMarkAll = document.getElementById('notifMarkAll');
        const csrfToken    = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const FEED_URL     = @json(route('notifikasi.feed'));
        const READ_URL     = @json(url('notifikasi'));
        const READ_ALL_URL = @json(route('notifikasi.readAll'));
        const POLL_MS      = 20000;

        let lastUnread = {{ (int) $unreadNotif }};
        let notifTimer = null;
        let notifLoaded = false;

        function escapeHtml(text) {
            return String(text ?? '').replace(/[&<>"']/g, s => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            })[s]);
        }

        function renderNotifications(data) {
            if (!notifList) return;

            const unread = Number(data.unread_count || 0);
            notifDot.textContent = unread > 99 ? '99+' : unread;
            notifDot.classList.toggle('show', unread > 0);
            if (unread > lastUnread) {
                notifBtn.classList.remove('ring');
                void notifBtn.offsetWidth;
                notifBtn.classList.add('ring');
            }
            lastUnread = unread;
            if (notifMarkAll) notifMarkAll.disabled = unread === 0;

            if (!data.items || data.items.length === 0) {
                notifList.innerHTML = '<div class="notif-empty">'
                    + '<div style="font-size:1.6rem;">🔔</div>'
                    + 'Belum ada notifikasi untuk akun Anda.</div>';
                return;
            }

            notifList.innerHTML = data.items.map(item => `
                <button type="button" class="notif-item ${item.unread ? 'unread' : ''}"
                        data-id="${item.id}" data-url="${escapeHtml(item.url || '')}">
                    <span class="notif-ico" style="background:${escapeHtml(item.accent)}">
                        <i data-lucide="${escapeHtml(item.icon)}"></i>
                    </span>
                    <span class="notif-body">
                        <span class="notif-title">${escapeHtml(item.judul)}</span>
                        <span class="notif-msg">${escapeHtml(item.pesan)}</span>
                        <span class="notif-time"><i data-lucide="clock" style="width:11px;height:11px;"></i> ${escapeHtml(item.waktu)}</span>
                    </span>
                </button>
            `).join('');

            notifList.querySelectorAll('.notif-item').forEach(el => {
                el.addEventListener('click', () => {
                    const id  = el.dataset.id;
                    const url = el.dataset.url;
                    markAsRead(id).finally(() => {
                        if (url) window.location.href = url;
                    });
                });
            });

            renderIcons();
        }

        async function fetchNotifications(silent = true) {
            try {
                const res = await fetch(FEED_URL, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const data = await res.json();
                notifLoaded = true;
                renderNotifications(data);
            } catch (e) {
                if (!silent && notifList && !notifLoaded) {
                    notifList.innerHTML = '<div class="notif-empty">Gagal memuat notifikasi. Periksa koneksi Anda.</div>';
                }
            }
        }

        async function markAsRead(id) {
            try {
                const res = await fetch(`${READ_URL}/${id}/read`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                if (res.ok) {
                    const data = await res.json();
                    const unread = Number(data.unread_count || 0);
                    lastUnread = unread;
                    notifDot.textContent = unread > 99 ? '99+' : unread;
                    notifDot.classList.toggle('show', unread > 0);
                    const el = notifList?.querySelector(`.notif-item[data-id="${id}"]`);
                    if (el) el.classList.remove('unread');
                    if (notifMarkAll) notifMarkAll.disabled = unread === 0;
                }
            } catch (e) { /* diamkan: notifikasi tetap tampil */ }
        }

        if (notifBtn) {
            notifBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const willShow = !notifPanel.classList.contains('show');
                notifPanel.classList.toggle('show', willShow);
                notifBtn.setAttribute('aria-expanded', willShow ? 'true' : 'false');
                if (willShow) fetchNotifications(false);
            });

            document.addEventListener('click', (e) => {
                if (notifPanel.classList.contains('show')
                    && !notifPanel.contains(e.target)
                    && !notifBtn.contains(e.target)) {
                    notifPanel.classList.remove('show');
                    notifBtn.setAttribute('aria-expanded', 'false');
                }
            });
        }

        if (notifMarkAll) {
            notifMarkAll.disabled = lastUnread === 0;
            notifMarkAll.addEventListener('click', async (e) => {
                e.stopPropagation();
                try {
                    const res = await fetch(READ_ALL_URL, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });
                    if (res.ok) {
                        lastUnread = 0;
                        notifDot.textContent = '0';
                        notifDot.classList.remove('show');
                        notifMarkAll.disabled = true;
                        notifList.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
                    }
                } catch (err) { /* diamkan */ }
            });
        }

        /* Polling: berhenti saat tab tidak aktif, lanjut & segarkan saat kembali. */
        function startPolling() {
            stopPolling();
            notifTimer = setInterval(() => fetchNotifications(true), POLL_MS);
        }
        function stopPolling() {
            if (notifTimer) { clearInterval(notifTimer); notifTimer = null; }
        }
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) { stopPolling(); } else { fetchNotifications(true); startPolling(); }
        });

        if (notifBtn) {
            fetchNotifications(true);
            startPolling();
        }
    </script>
    @yield('scripts')
</body>
</html>
