<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salvras — Sistem Inventaris Sarana &amp; Prasarana Yayasan Husnul Abror</title>
    <meta name="description" content="Sistem informasi manajemen aset inventaris sarana dan prasarana terintegrasi QR Code pada Yayasan Pendidikan Ponpes Tahfizul Qur'an Husnul Abror (MI, MTS, SMK).">

    <link rel="icon" type="image/png" href="{{ asset('image/logo.png') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:    #1E3A5F;
            --navy-d:  #152C48;
            --navy-l:  #2A4F80;
            --green:   #2ECC71;
            --green-d: #27AE60;
            --slate:   #F8FAFC;
            --gray:    #64748B;
            --border:  #E2E8F0;
            --white:   #FFFFFF;
            --ink:     #0F172A;
        }

        html { scroll-behavior: smooth; -webkit-text-size-adjust: 100%; }
        body { font-family: 'Inter', system-ui, sans-serif; background: var(--white); color: #1A202C; line-height: 1.6; overflow-x: hidden; }
        img { max-width: 100%; display: block; }
        i[data-lucide], svg.lucide { width: 1.15em; height: 1.15em; stroke-width: 2.1; display: inline-block; vertical-align: middle; }

        /* ══ NAV ══ */
        nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 clamp(16px, 5vw, 72px); height: 66px;
            background: rgba(21,44,72,.94); backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .nav-logo { display: flex; align-items: center; gap: 11px; text-decoration: none; min-width: 0; }
        .nav-logo-img {
            width: 38px; height: 38px; object-fit: contain; flex-shrink: 0;
            filter: drop-shadow(0 2px 8px rgba(0,0,0,.3));
        }
        .nav-logo-text { min-width: 0; }
        .nav-logo-text strong { display: block; color: #fff; font-size: 1.05rem; font-weight: 800; letter-spacing: -.02em; }
        .nav-logo-text span { display: block; color: rgba(255,255,255,.55); font-size: .68rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; }

        .nav-links { display: flex; align-items: center; gap: clamp(14px, 2.4vw, 30px); list-style: none; }
        .nav-links a { color: rgba(255,255,255,.78); text-decoration: none; font-size: .88rem; font-weight: 500; transition: color .2s; white-space: nowrap; }
        .nav-links a:hover { color: #fff; }
        .nav-cta {
            background: var(--green); color: #fff !important; padding: 9px 20px;
            border-radius: 9px; font-weight: 700 !important;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .nav-cta:hover { background: var(--green-d); }
        .nav-toggle {
            display: none; background: none; border: 1px solid rgba(255,255,255,.2);
            color: #fff; width: 38px; height: 38px; border-radius: 9px; cursor: pointer;
            align-items: center; justify-content: center;
        }
        .nav-mobile {
            display: none; position: fixed; top: 66px; left: 0; right: 0; z-index: 99;
            background: rgba(21,44,72,.98); border-bottom: 1px solid rgba(255,255,255,.1);
            padding: 12px clamp(16px, 5vw, 72px) 18px;
        }
        .nav-mobile.show { display: block; }
        .nav-mobile a {
            display: block; padding: 11px 0; color: rgba(255,255,255,.82);
            text-decoration: none; font-size: .92rem; font-weight: 600;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }

        /* ══ HERO ══ */
        .hero {
            min-height: 100vh;
            background:
                linear-gradient(135deg, rgba(21,44,72,.93) 0%, rgba(30,58,95,.86) 55%, rgba(42,79,128,.9) 100%),
                url('{{ asset('image/sekolah.jpeg') }}');
            background-size: cover; background-position: center; background-repeat: no-repeat;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            text-align: center; padding: 120px clamp(16px, 6vw, 72px) 80px; position: relative;
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(46,204,113,.15); border: 1px solid rgba(46,204,113,.4);
            color: var(--green); padding: 7px 17px; border-radius: 100px;
            font-size: .76rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
            margin-bottom: 26px;
        }
        .hero-badge .dot { width: 6px; height: 6px; background: var(--green); border-radius: 50%; animation: pulse 2s infinite; }
        @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: .35; } }

        .hero h1 {
            font-size: clamp(2rem, 6vw, 3.9rem); font-weight: 800; color: #fff;
            line-height: 1.14; max-width: 880px; letter-spacing: -.03em; margin-bottom: 20px;
        }
        .hero h1 .accent { color: var(--green); }
        .hero p { font-size: clamp(.95rem, 2vw, 1.14rem); color: rgba(255,255,255,.78); max-width: 620px; margin-bottom: 36px; }
        .hero-actions { display: flex; gap: 13px; flex-wrap: wrap; justify-content: center; }
        .btn-primary {
            background: var(--green); color: #fff; padding: 14px 30px; border-radius: 11px;
            font-weight: 700; font-size: .98rem; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px;
            box-shadow: 0 6px 22px rgba(46,204,113,.38); transition: transform .2s, box-shadow .2s;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(46,204,113,.5); }
        .btn-outline {
            background: rgba(255,255,255,.06); color: #fff; padding: 14px 30px; border-radius: 11px;
            font-weight: 600; font-size: .98rem; text-decoration: none;
            border: 1.5px solid rgba(255,255,255,.32);
            display: inline-flex; align-items: center; gap: 8px; transition: background .2s, border-color .2s;
        }
        .btn-outline:hover { border-color: rgba(255,255,255,.7); background: rgba(255,255,255,.12); }

        .hero-units {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 14px; margin-top: 52px; width: 100%; max-width: 820px;
        }
        .hero-unit {
            background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.16);
            border-radius: 15px; padding: 18px 20px; text-align: left;
            backdrop-filter: blur(6px); transition: background .2s, transform .2s;
        }
        .hero-unit:hover { background: rgba(46,204,113,.14); transform: translateY(-3px); }
        .hero-unit .hu-top { display: flex; align-items: center; gap: 9px; margin-bottom: 11px; }
        .hero-unit .hu-ico {
            width: 34px; height: 34px; border-radius: 9px; background: rgba(46,204,113,.2);
            color: var(--green); display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .hero-unit .hu-kode { font-size: .95rem; font-weight: 800; color: #fff; }
        .hero-unit .hu-jenjang { font-size: .68rem; color: rgba(255,255,255,.55); }
        .hero-unit .hu-qty { font-size: 1.7rem; font-weight: 800; color: #fff; line-height: 1; letter-spacing: -.03em; }
        .hero-unit .hu-lbl { font-size: .72rem; color: rgba(255,255,255,.6); margin-top: 4px; }

        /* ══ STATS ══ */
        .stats { background: #fff; border-bottom: 1px solid var(--border); }
        .stats-inner {
            max-width: 1000px; margin: 0 auto; display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        }
        .stat-item { text-align: center; padding: 34px 20px; border-right: 1px solid var(--border); }
        .stat-item:last-child { border-right: none; }
        .stat-num { font-size: clamp(1.7rem, 4vw, 2.3rem); font-weight: 800; color: var(--navy); letter-spacing: -.03em; line-height: 1; }
        .stat-num .suffix { font-size: 1.2rem; }
        .stat-label { font-size: .82rem; color: var(--gray); margin-top: 7px; font-weight: 600; }

        /* ══ SECTION ══ */
        section { padding: clamp(56px, 9vw, 92px) clamp(16px, 6vw, 72px); }
        .section-label { font-size: .74rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--green-d); margin-bottom: 11px; }
        .section-title { font-size: clamp(1.5rem, 4vw, 2.35rem); font-weight: 800; color: var(--navy); line-height: 1.2; letter-spacing: -.02em; max-width: 620px; margin-bottom: 14px; }
        .section-desc { font-size: .98rem; color: var(--gray); max-width: 620px; margin-bottom: 44px; }

        #fitur { background: var(--slate); }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(275px, 1fr)); gap: 20px; }
        .feature-card {
            background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 28px;
            transition: box-shadow .25s, transform .25s;
        }
        .feature-card:hover { transform: translateY(-4px); box-shadow: 0 14px 36px rgba(30,58,95,.11); }
        .feature-icon {
            width: 50px; height: 50px; border-radius: 13px;
            background: linear-gradient(135deg, var(--navy), var(--navy-l));
            display: flex; align-items: center; justify-content: center; color: #fff;
            margin-bottom: 17px;
        }
        .feature-card h3 { font-size: 1.02rem; font-weight: 800; color: var(--navy); margin-bottom: 9px; }
        .feature-card p { font-size: .88rem; color: var(--gray); line-height: 1.65; }

        .steps-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(215px, 1fr)); gap: 8px; }
        .step { padding: 26px 24px; border-left: 3px solid var(--border); }
        .step:first-child { border-left-color: var(--green); }
        .step-num { font-size: 2.5rem; font-weight: 800; color: var(--border); line-height: 1; margin-bottom: 13px; letter-spacing: -.04em; }
        .step h3 { font-size: .98rem; font-weight: 800; color: var(--navy); margin-bottom: 7px; }
        .step p { font-size: .86rem; color: var(--gray); line-height: 1.6; }

        #unit-sekolah { background: var(--navy); }
        #unit-sekolah .section-title { color: #fff; }
        #unit-sekolah .section-desc { color: rgba(255,255,255,.62); }
        .unit-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(255px, 1fr)); gap: 18px; }
        .unit-card {
            background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.12);
            border-radius: 16px; padding: 24px; transition: background .2s, border-color .2s, transform .2s;
        }
        .unit-card:hover { background: rgba(46,204,113,.1); border-color: rgba(46,204,113,.35); transform: translateY(-3px); }
        .unit-card .uc-ico {
            width: 46px; height: 46px; border-radius: 12px; background: rgba(46,204,113,.18);
            color: var(--green); display: flex; align-items: center; justify-content: center; margin-bottom: 15px;
        }
        .unit-card h3 { font-size: 1.05rem; font-weight: 800; color: #fff; }
        .unit-card .uc-sub { font-size: .8rem; color: rgba(255,255,255,.55); margin-bottom: 16px; }
        .uc-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 9px; }
        .uc-stat { background: rgba(255,255,255,.06); border-radius: 10px; padding: 11px 9px; text-align: center; }
        .uc-stat b { display: block; font-size: 1.1rem; font-weight: 800; color: #fff; line-height: 1.1; }
        .uc-stat span { font-size: .66rem; color: rgba(255,255,255,.55); }

        /* ══ CTA ══ */
        #cta {
            background: linear-gradient(135deg, var(--navy-d), var(--navy-l));
            text-align: center; color: #fff;
        }
        #cta h2 { font-size: clamp(1.5rem, 4vw, 2.4rem); font-weight: 800; margin-bottom: 13px; letter-spacing: -.02em; }
        #cta p { color: rgba(255,255,255,.7); font-size: .98rem; margin: 0 auto 32px; max-width: 560px; }

        /* ══ FOOTER ══ */
        footer { background: var(--navy-d); color: rgba(255,255,255,.65); padding: clamp(40px, 6vw, 64px) clamp(16px, 6vw, 72px) 0; }
        .footer-grid {
            display: grid; gap: 34px; max-width: 1180px; margin: 0 auto;
            grid-template-columns: minmax(240px, 1.6fr) repeat(auto-fit, minmax(160px, 1fr));
        }
        .footer-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; }
        .footer-logo-img {
            width: 44px; height: 44px; object-fit: contain; flex-shrink: 0;
            filter: drop-shadow(0 2px 8px rgba(0,0,0,.35));
        }
        .footer-brand strong { display: block; color: #fff; font-size: 1.05rem; font-weight: 800; }
        .footer-brand span { font-size: .7rem; color: rgba(255,255,255,.5); text-transform: uppercase; letter-spacing: .06em; }
        .footer-about { font-size: .86rem; line-height: 1.75; max-width: 380px; }
        .footer-contact { margin-top: 17px; display: flex; flex-direction: column; gap: 9px; font-size: .84rem; }
        .footer-contact div { display: flex; gap: 9px; align-items: flex-start; }
        .footer-contact i { color: var(--green); margin-top: 3px; }
        .footer-col h4 { color: #fff; font-size: .84rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 15px; }
        .footer-col a, .footer-col li {
            display: block; color: rgba(255,255,255,.62); text-decoration: none;
            font-size: .86rem; padding: 5px 0; list-style: none; transition: color .2s;
        }
        .footer-col a:hover { color: var(--green); }

        .socials { display: flex; gap: 10px; margin-top: 18px; flex-wrap: wrap; }
        .social-link {
            width: 38px; height: 38px; border-radius: 10px;
            background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.13);
            display: flex; align-items: center; justify-content: center;
            color: rgba(255,255,255,.75); text-decoration: none;
            transition: background .2s, color .2s, transform .2s;
        }
        .social-link:hover { background: var(--green); color: #fff; border-color: var(--green); transform: translateY(-2px); }

        .footer-bottom {
            max-width: 1180px; margin: 38px auto 0; padding: 20px 0 26px;
            border-top: 1px solid rgba(255,255,255,.09);
            display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap;
            font-size: .8rem; color: rgba(255,255,255,.45);
        }
        .footer-bottom a { color: var(--green); text-decoration: none; }

        /* ══ RESPONSIVE ══ */
        @media (max-width: 860px) {
            .nav-links { display: none; }
            .nav-toggle { display: flex; }
            .stat-item { border-right: none; border-bottom: 1px solid var(--border); }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 560px) {
            .hero-actions { flex-direction: column; width: 100%; }
            .btn-primary, .btn-outline { width: 100%; justify-content: center; }
            .footer-grid { grid-template-columns: 1fr; }
            .footer-bottom { flex-direction: column; text-align: center; }
        }
        @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }
    </style>
</head>
<body>

<!-- ══ NAV ══ -->
<nav>
    <a href="#" class="nav-logo">
        <img src="{{ asset('image/logo.png') }}" alt="Logo Yayasan" class="nav-logo-img">
        <span class="nav-logo-text">
            <strong>SarPras</strong>
            <span>Yayasan Husnul Abror</span>
        </span>
    </a>

    <ul class="nav-links">
        <li><a href="#fitur">Fitur</a></li>
        <li><a href="#cara-kerja">Cara Kerja</a></li>
        <li><a href="#unit-sekolah">Unit Sekolah</a></li>
        <li><a href="#tentang">Tentang</a></li>
        <li>
            <a href="{{ route('login') }}" class="nav-cta">
                <i data-lucide="log-in" style="width:15px;height:15px;"></i> Masuk
            </a>
        </li>
    </ul>

    <button type="button" class="nav-toggle" onclick="document.getElementById('navMobile').classList.toggle('show')" aria-label="Buka menu">
        <i data-lucide="menu"></i>
    </button>
</nav>

<div class="nav-mobile" id="navMobile">
    <a href="#fitur">Fitur</a>
    <a href="#cara-kerja">Cara Kerja</a>
    <a href="#unit-sekolah">Unit Sekolah</a>
    <a href="#tentang">Tentang</a>
    <a href="{{ route('login') }}" style="color:var(--green);border-bottom:none;">Masuk ke Dashboard →</a>
</div>

<!-- ══ HERO ══ -->
<section class="hero">
    <div class="hero-badge"><span class="dot"></span> Sistem Manajemen Aset Sekolah</div>

    <h1>Kelola <span class="accent">Inventaris Sekolah</span> dengan Lebih Cerdas</h1>
    <p>
        Pantau, catat, dan laporkan seluruh aset sekolah dalam satu platform terpusat.
        Dari buku hingga peralatan laboratorium — terintegrasi QR Code untuk tiga unit
        pendidikan Yayasan Husnul Abror.
    </p>

    <div class="hero-actions">
        <a href="{{ route('login') }}" class="btn-primary">
            <i data-lucide="log-in" style="width:17px;height:17px;"></i> Masuk ke Dashboard
        </a>
        <a href="#fitur" class="btn-outline">
            <i data-lucide="sparkles" style="width:17px;height:17px;"></i> Lihat Fitur
        </a>
    </div>

    {{-- Jumlah aset nyata per unit sekolah --}}
    <div class="hero-units">
        @foreach($unitStats as $u)
            <div class="hero-unit">
                <div class="hu-top">
                    <span class="hu-ico"><i data-lucide="{{ $u['icon'] }}" style="width:17px;height:17px;"></i></span>
                    <span>
                        <span class="hu-kode">Unit {{ $u['kode'] }}</span>
                        <span class="hu-jenjang">{{ $u['jenjang'] }}</span>
                    </span>
                </div>
                <div class="hu-qty">{{ number_format($u['total_qty']) }}</div>
                <div class="hu-lbl">unit aset · {{ $u['jenis'] }} jenis barang · {{ $u['ruangan'] }} ruangan</div>
            </div>
        @endforeach
    </div>
</section>

<!-- ══ STATS ══ -->
<div class="stats">
    <div class="stats-inner">
        <div class="stat-item">
            <div class="stat-num"><span class="count" data-target="{{ $ringkasan['total_aset'] }}">0</span></div>
            <div class="stat-label">Unit Aset Tercatat</div>
        </div>
        <div class="stat-item">
            <div class="stat-num"><span class="count" data-target="{{ $ringkasan['total_jenis'] }}">0</span></div>
            <div class="stat-label">Jenis Barang Terdata</div>
        </div>
        <div class="stat-item">
            <div class="stat-num"><span class="count" data-target="{{ $ringkasan['total_ruang'] }}">0</span></div>
            <div class="stat-label">Ruangan Terdaftar</div>
        </div>
        <div class="stat-item">
            <div class="stat-num"><span class="count" data-target="{{ $ringkasan['total_unit'] }}">0</span></div>
            <div class="stat-label">Unit Pendidikan</div>
        </div>
        <div class="stat-item">
            <div class="stat-num"><span class="count" data-target="{{ (int) $ringkasan['persen_baik'] }}">0</span><span class="suffix">%</span></div>
            <div class="stat-label">Aset Kondisi Baik</div>
        </div>
    </div>
</div>

<!-- ══ FITUR ══ -->
<section id="fitur">
    <div class="section-label">Fitur Unggulan</div>
    <div class="section-title">Semua yang Dibutuhkan Pengelola Sarana &amp; Prasarana</div>
    <p class="section-desc">
        Dirancang mengikuti alur kerja administrasi sarpras di lingkungan yayasan pendidikan,
        dari pendataan barang per ruangan sampai verifikasi laporan oleh Yayasan.
    </p>

    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon"><i data-lucide="clipboard-list"></i></div>
            <h3>Pendataan Aset per Ruangan</h3>
            <p>Catat setiap barang lengkap dengan kode aset, foto, rincian kondisi, nilai estimasi, dan ruangan tempat barang berada.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i data-lucide="qr-code"></i></div>
            <h3>QR Code Otomatis</h3>
            <p>Setiap barang memperoleh QR Code unik yang dapat dicetak sebagai label dan dipindai memakai kamera HP maupun laptop.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i data-lucide="scan-line"></i></div>
            <h3>Scan &amp; Cetak Label</h3>
            <p>Pindai QR untuk melihat detail aset secara instan, atau pilih ruangan dan barang tertentu untuk dicetak labelnya.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i data-lucide="arrow-left-right"></i></div>
            <h3>Barang Masuk &amp; Keluar</h3>
            <p>Catat pengadaan barang baru, pengajuan penggantian barang rusak, hingga peminjaman barang beserta pengembaliannya.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i data-lucide="file-check-2"></i></div>
            <h3>Verifikasi Berjenjang</h3>
            <p>Admin unit sekolah mengajukan laporan, Admin Yayasan memverifikasi, dan status akhirnya terpantau kedua belah pihak.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i data-lucide="bell-ring"></i></div>
            <h3>Notifikasi Real-Time</h3>
            <p>Pemberitahuan langsung pada navbar setiap kali ada laporan baru, hasil verifikasi, atau mutasi barang.</p>
        </div>
    </div>
</section>

<!-- ══ CARA KERJA ══ -->
<section id="cara-kerja">
    <div class="section-label">Cara Kerja</div>
    <div class="section-title">Empat Langkah Pengelolaan Inventaris</div>
    <p class="section-desc">Alur kerja yang sederhana sehingga langsung bisa dipakai oleh staf administrasi tiap unit sekolah.</p>

    <div class="steps-grid">
        <div class="step">
            <div class="step-num">01</div>
            <h3>Daftarkan Ruangan</h3>
            <p>Admin unit mendata ruang kelas, laboratorium, aula, dan ruang guru sebagai lokasi penempatan barang.</p>
        </div>
        <div class="step">
            <div class="step-num">02</div>
            <h3>Data Barang &amp; Cetak QR</h3>
            <p>Barang dicatat beserta foto dan rincian kondisi, lalu label QR Code dicetak dan ditempel pada fisik barang.</p>
        </div>
        <div class="step">
            <div class="step-num">03</div>
            <h3>Pantau &amp; Laporkan</h3>
            <p>Perubahan kondisi, barang rusak yang harus diganti, dan peminjaman barang tercatat rapi setiap saat.</p>
        </div>
        <div class="step">
            <div class="step-num">04</div>
            <h3>Verifikasi Yayasan</h3>
            <p>Admin Yayasan meninjau seluruh laporan unit, memverifikasi, dan mengunduh rekap audit dalam format siap cetak.</p>
        </div>
    </div>
</section>

<!-- ══ UNIT SEKOLAH ══ -->
<section id="unit-sekolah">
    <div class="section-label">Unit Sekolah</div>
    <div class="section-title">Tiga Unit Pendidikan dalam Satu Sistem</div>
    <p class="section-desc">
        Data setiap unit terisolasi dan hanya dapat dikelola oleh admin unit masing-masing,
        namun tetap terkonsolidasi pada dashboard Yayasan.
    </p>

    <div class="unit-grid">
        @foreach($unitStats as $u)
            <div class="unit-card">
                <div class="uc-ico"><i data-lucide="{{ $u['icon'] }}"></i></div>
                <h3>{{ $u['nama'] }}</h3>
                <div class="uc-sub">{{ $u['jenjang'] }}</div>
                <div class="uc-stats">
                    <div class="uc-stat">
                        <b>{{ number_format($u['total_qty']) }}</b>
                        <span>Unit Aset</span>
                    </div>
                    <div class="uc-stat">
                        <b>{{ number_format($u['jenis']) }}</b>
                        <span>Jenis Barang</span>
                    </div>
                    <div class="uc-stat">
                        <b>{{ number_format($u['ruangan']) }}</b>
                        <span>Ruangan</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>

<!-- ══ CTA ══ -->
<section id="cta">
    <h2>Siap Mengelola Aset Sekolah Lebih Tertib?</h2>
    <p>Masuk memakai akun yang diberikan administrator yayasan untuk mulai mendata dan memantau inventaris sarana &amp; prasarana.</p>
    <a href="{{ route('login') }}" class="btn-primary" style="margin:0 auto;">
        <i data-lucide="log-in" style="width:17px;height:17px;"></i> Masuk ke Dashboard
    </a>
</section>

<!-- ══ FOOTER ══ -->
<footer id="tentang">
    <div class="footer-grid">
        {{-- Tentang Yayasan --}}
        <div>
            <div class="footer-brand">
                <img src="{{ asset('image/logo.png') }}" alt="Logo Yayasan" class="footer-logo-img">
                <span>
                    <strong>Yayasan Husnul Abror</strong>
                    <span>Ponpes Tahfizul Qur'an</span>
                </span>
            </div>
            <p class="footer-about">
                Yayasan Pendidikan Ponpes Tahfizul Qur'an Husnul Abror menaungi tiga unit pendidikan
                — Madrasah Ibtidaiyah (MI), Madrasah Tsanawiyah (MTS), dan Sekolah Menengah Kejuruan (SMK).
                Sistem Salvras digunakan untuk menata seluruh aset sarana dan prasarana yayasan
                secara terpusat, transparan, dan terintegrasi QR Code.
            </p>

            <div class="footer-contact">
                <div>
                    <i data-lucide="map-pin" style="width:15px;height:15px;"></i>
                    <span>Kompleks Ponpes Tahfizul Qur'an Husnul Abror,<br>Lombok Timur, Nusa Tenggara Barat</span>
                </div>
                <div>
                    <i data-lucide="mail" style="width:15px;height:15px;"></i>
                    <span>info@husnulabror.sch.id</span>
                </div>
                <div>
                    <i data-lucide="phone" style="width:15px;height:15px;"></i>
                    <span>(0376) 000 111</span>
                </div>
            </div>

            {{-- Ikon media sosial --}}
            <div class="socials">
                <a href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" class="social-link" title="Facebook" aria-label="Facebook">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
                <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" class="social-link" title="Instagram" aria-label="Instagram">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                </a>
                <a href="https://www.youtube.com/" target="_blank" rel="noopener noreferrer" class="social-link" title="YouTube" aria-label="YouTube">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                </a>
                <a href="https://wa.me/6280000000000" target="_blank" rel="noopener noreferrer" class="social-link" title="WhatsApp" aria-label="WhatsApp">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.316 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.818-.981zm10.957-7.469c-.066-.11-.242-.176-.506-.308-.264-.132-1.562-.77-1.804-.858-.242-.088-.418-.132-.594.132-.176.264-.682.858-.836 1.034-.154.176-.308.198-.572.066-.264-.132-1.115-.411-2.124-1.311-.785-.699-1.315-1.563-1.469-1.827-.154-.264-.016-.407.116-.538.119-.118.264-.308.396-.462.132-.154.176-.264.264-.44.088-.176.044-.33-.022-.462-.066-.132-.594-1.431-.814-1.959-.214-.515-.432-.445-.594-.453-.154-.008-.33-.01-.506-.01-.176 0-.462.066-.704.33-.242.264-.924.903-.924 2.202s.946 2.553 1.078 2.729c.132.176 1.861 2.842 4.509 3.984.63.272 1.122.434 1.506.556.633.201 1.209.173 1.664.105.508-.076 1.562-.638 1.782-1.254.22-.616.22-1.144.154-1.254z"/></svg>
                </a>
                <a href="https://twitter.com/" target="_blank" rel="noopener noreferrer" class="social-link" title="X / Twitter" aria-label="X / Twitter">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <a href="mailto:info@husnulabror.sch.id" class="social-link" title="Email" aria-label="Email">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                </a>
            </div>
        </div>

        {{-- Navigasi --}}
        <div class="footer-col">
            <h4>Navigasi</h4>
            <a href="#">Beranda</a>
            <a href="#fitur">Fitur Unggulan</a>
            <a href="#cara-kerja">Cara Kerja</a>
            <a href="#unit-sekolah">Unit Sekolah</a>
            <a href="{{ route('login') }}">Masuk Dashboard</a>
        </div>

        {{-- Unit Sekolah --}}
        <div class="footer-col">
            <h4>Unit Pendidikan</h4>
            @foreach($unitStats as $u)
                <li>{{ $u['nama'] }}</li>
            @endforeach
            <li>Ponpes Tahfizul Qur'an</li>
        </div>

        {{-- Layanan Sistem --}}
        <div class="footer-col">
            <h4>Layanan Sistem</h4>
            <li>Pendataan Aset &amp; QR Code</li>
            <li>Cetak Label QR</li>
            <li>Barang Masuk &amp; Keluar</li>
            <li>Pelaporan &amp; Verifikasi</li>
            <li>Rekap Audit Yayasan</li>
        </div>
    </div>

    <div class="footer-bottom">
        <span>© {{ date('Y') }} Yayasan Pendidikan Ponpes Tahfizul Qur'an Husnul Abror. Seluruh hak cipta dilindungi.</span>
        <span>Salvras — Sistem Manajemen Aset Sarpras Terintegrasi <a href="{{ route('login') }}">QR Code</a></span>
    </div>
</footer>

<script>
    if (typeof lucide !== 'undefined') lucide.createIcons();

    /* Animasi angka statistik */
    const counters = document.querySelectorAll('.count');
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const el     = entry.target;
                const target = parseInt(el.dataset.target || '0', 10);
                const step   = Math.max(1, target / 90);
                let current  = 0;
                const timer = setInterval(() => {
                    current += step;
                    if (current >= target) { current = target; clearInterval(timer); }
                    el.textContent = Math.floor(current).toLocaleString('id-ID');
                }, 16);
                observer.unobserve(el);
            });
        }, { threshold: 0.4 });
        counters.forEach(c => observer.observe(c));
    } else {
        counters.forEach(c => c.textContent = Number(c.dataset.target || 0).toLocaleString('id-ID'));
    }

    /* Tutup menu mobile setelah memilih tautan */
    document.querySelectorAll('.nav-mobile a').forEach(a => {
        a.addEventListener('click', () => document.getElementById('navMobile').classList.remove('show'));
    });
</script>
</body>
</html>
