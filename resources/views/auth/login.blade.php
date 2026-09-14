<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin — SalPras Husnul Abror</title>

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
            --gray:    #64748B;
            --border:  #E2E8F0;
            --white:   #FFFFFF;
            --slate:   #F4F7FB;
            --error:   #EF4444;
            --ink:     #0F172A;
        }

        html, body { min-height: 100%; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--slate);
            color: var(--ink);
            display: flex; flex-direction: column;
            min-height: 100vh;
        }
        img { max-width: 100%; display: block; }
        i[data-lucide], svg.lucide { width: 1.15em; height: 1.15em; stroke-width: 2.1; display: inline-block; vertical-align: middle; }

        .shell { flex: 1; display: grid; grid-template-columns: 1.05fr .95fr; }

        /* ══ PANEL KIRI (identitas yayasan + foto sekolah) ══ */
        .left {
            position: relative; overflow: hidden;
            display: flex; flex-direction: column; justify-content: center;
            padding: clamp(30px, 5vw, 60px);
            background:
                linear-gradient(150deg, rgba(21,44,72,.95) 0%, rgba(30,58,95,.9) 45%, rgba(42,79,128,.86) 100%),
                url('{{ asset('image/sekolah1on1.jpeg') }}');
            background-size: cover; background-position: center;
            border-radius: 0 28px 28px 0;
        }
        .left::before {
            content: ''; position: absolute; inset: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,.08) 1px, transparent 1px);
            background-size: 26px 26px; opacity: .6;
        }
        .left::after {
            content: ''; position: absolute; width: 380px; height: 380px; border-radius: 50%;
            background: radial-gradient(circle, rgba(46,204,113,.22) 0%, transparent 70%);
            bottom: -110px; right: -110px;
        }
        .left-inner { position: relative; z-index: 1; max-width: 520px; }

        .brand-row { display: flex; align-items: center; gap: 16px; margin-bottom: 26px; }
        .brand-logo-img {
            width: 72px; height: 72px; flex-shrink: 0; object-fit: contain;
            filter: drop-shadow(0 6px 18px rgba(0,0,0,.35));
        }
        .brand-title h1 {
            font-size: clamp(1.5rem, 3.2vw, 2.15rem); font-weight: 800; color: #fff;
            letter-spacing: .01em; line-height: 1.05; text-transform: uppercase;
        }
        .brand-title h2 {
            font-size: clamp(.95rem, 2vw, 1.28rem); font-weight: 700; color: #fff;
            letter-spacing: .02em; text-transform: uppercase; margin-top: 2px;
        }
        .brand-title p { font-size: 1rem; font-weight: 700; color: var(--green); margin-top: 5px; }

        .left-divider { height: 1px; background: rgba(255,255,255,.18); margin: 0 0 24px; }
        .left-desc { font-size: .95rem; color: rgba(255,255,255,.8); line-height: 1.8; max-width: 460px; }

        .units-box {
            margin-top: 30px; background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.16); border-radius: 18px;
            padding: 20px; backdrop-filter: blur(6px);
        }
        .units-box h3 {
            text-align: center; font-size: .92rem; font-weight: 800; color: #fff; margin-bottom: 16px;
        }
        .units-row { display: grid; grid-template-columns: repeat(3, 1fr); }
        .unit-item { text-align: center; padding: 0 10px; }
        .unit-item + .unit-item { border-left: 1px solid rgba(255,255,255,.14); }
        .unit-badge {
            width: 52px; height: 52px; margin: 0 auto 10px; border-radius: 15px;
            display: flex; align-items: center; justify-content: center; color: #fff;
        }
        .ub-mi  { background: linear-gradient(140deg, #16A34A, #15803D); box-shadow: 0 6px 16px rgba(22,163,74,.35); }
        .ub-mts { background: linear-gradient(140deg, #2563EB, #1D4ED8); box-shadow: 0 6px 16px rgba(37,99,235,.35); }
        .ub-smk { background: linear-gradient(140deg, #9333EA, #7C3AED); box-shadow: 0 6px 16px rgba(147,51,234,.35); }
        .unit-item strong { display: block; font-size: 1.02rem; font-weight: 800; }
        .u-mi strong  { color: #4ADE80; }
        .u-mts strong { color: #60A5FA; }
        .u-smk strong { color: #C084FC; }
        .unit-item span { display: block; font-size: .72rem; color: rgba(255,255,255,.68); margin-top: 3px; line-height: 1.4; }

        /* ══ PANEL KANAN (form login) ══ */
        .right {
            display: flex; align-items: center; justify-content: center;
            padding: clamp(28px, 5vw, 60px);
        }
        .login-card {
            width: 100%; max-width: 440px; background: #fff;
            border: 1px solid var(--border); border-radius: 22px;
            padding: clamp(26px, 4vw, 40px);
            box-shadow: 0 22px 50px -20px rgba(15,23,42,.22);
        }
        .lock-badge {
            width: 84px; height: 84px; margin: 0 auto 18px; border-radius: 50%;
            background: #EEF4FF; display: flex; align-items: center; justify-content: center;
        }
        .lock-badge span {
            width: 52px; height: 52px; border-radius: 15px;
            background: linear-gradient(140deg, var(--navy), var(--navy-l));
            display: flex; align-items: center; justify-content: center; color: #fff;
            box-shadow: 0 8px 18px rgba(30,58,95,.35);
        }
        .login-card h2 {
            text-align: center; font-size: clamp(1.35rem, 3vw, 1.7rem);
            font-weight: 800; color: var(--navy); letter-spacing: -.02em;
        }
        .login-card .sub { text-align: center; font-size: .88rem; color: var(--gray); margin: 6px 0 26px; }

        .alert {
            display: flex; gap: 9px; align-items: flex-start;
            padding: 12px 14px; border-radius: 11px; margin-bottom: 20px;
            font-size: .84rem; font-weight: 600; border: 1px solid transparent;
        }
        .alert-error { background: #FEF2F2; border-color: #FECACA; color: #DC2626; }
        .alert-ok    { background: #F0FDF4; border-color: #BBF7D0; color: #15803D; }
        .alert ul { margin: 3px 0 0 15px; font-weight: 500; }

        .field { margin-bottom: 17px; }
        .field label { display: block; font-size: .82rem; font-weight: 700; color: #334155; margin-bottom: 7px; }
        .field label .req { color: var(--error); }
        .input-wrap { position: relative; }
        .input-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: #94A3B8; pointer-events: none; display: flex;
        }
        .form-input {
            width: 100%; padding: 13px 14px 13px 42px;
            border: 1.5px solid var(--border); border-radius: 12px;
            font-size: .92rem; font-family: inherit; background: #fff; color: var(--ink);
            outline: none; transition: border-color .18s, box-shadow .18s;
        }
        .form-input::placeholder { color: #C3CCD8; }
        .form-input:focus { border-color: var(--navy-l); box-shadow: 0 0 0 3px rgba(42,79,128,.12); }
        .form-input.is-error { border-color: var(--error); box-shadow: 0 0 0 3px rgba(239,68,68,.1); }
        .toggle-pwd {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; color: #94A3B8;
            padding: 5px; line-height: 1; display: flex; border-radius: 7px;
        }
        .toggle-pwd:hover { color: var(--navy); background: var(--slate); }
        .field-error { font-size: .77rem; color: var(--error); font-weight: 700; margin-top: 6px; display: block; }

        .row-between { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 22px; flex-wrap: wrap; }
        .remember { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .remember input { width: 17px; height: 17px; accent-color: var(--navy); cursor: pointer; }
        .remember span { font-size: .85rem; color: var(--gray); user-select: none; }
        .forgot { font-size: .85rem; color: var(--navy-l); text-decoration: none; font-weight: 700; }
        .forgot:hover { color: var(--green-d); }

        .btn-login {
            width: 100%; padding: 14px; background: var(--navy); color: #fff;
            border: none; border-radius: 12px; font-size: .98rem; font-weight: 700;
            font-family: inherit; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 9px;
            box-shadow: 0 8px 20px rgba(30,58,95,.24);
            transition: background .2s, transform .15s, box-shadow .2s;
        }
        .btn-login:hover { background: var(--navy-l); transform: translateY(-1px); box-shadow: 0 11px 26px rgba(30,58,95,.3); }
        .btn-login:disabled { opacity: .65; cursor: not-allowed; transform: none; }

        .divider { display: flex; align-items: center; gap: 12px; margin: 22px 0; color: #A6B2C2; font-size: .8rem; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }

        .btn-back {
            width: 100%; padding: 13px; background: #fff; color: var(--navy);
            border: 1.5px solid var(--border); border-radius: 12px;
            font-size: .9rem; font-weight: 700; text-decoration: none;
            display: flex; align-items: center; justify-content: center; gap: 9px;
            transition: border-color .2s, background .2s;
        }
        .btn-back:hover { border-color: var(--navy-l); background: var(--slate); }

        .hint-box {
            margin-top: 22px; padding: 14px 16px; background: rgba(30,58,95,.04);
            border: 1px solid rgba(30,58,95,.1); border-radius: 12px;
            display: flex; gap: 10px; align-items: flex-start;
            font-size: .8rem; color: var(--gray); line-height: 1.6;
        }
        .hint-box strong { color: var(--navy); }
        .hint-box i { color: var(--navy-l); margin-top: 2px; }

        .spinner {
            width: 17px; height: 17px; border: 2px solid rgba(255,255,255,.35);
            border-top-color: #fff; border-radius: 50%; display: none;
            animation: spin .7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .page-foot {
            text-align: center; padding: 16px; font-size: .8rem; color: var(--gray);
            background: var(--slate); border-top: 1px solid var(--border);
        }

        /* ══ RESPONSIVE ══ */
        @media (max-width: 1024px) {
            .shell { grid-template-columns: 1fr; }
            .left { border-radius: 0; padding: 40px clamp(20px, 6vw, 48px) 46px; }
            .left-inner { max-width: none; margin: 0 auto; }
            .right { padding: 32px clamp(18px, 6vw, 48px) 44px; }
        }
        @media (max-width: 560px) {
            .brand-row { gap: 12px; }
            .brand-mark { width: 54px; height: 54px; border-radius: 15px; }
            .units-row { grid-template-columns: 1fr; gap: 14px; }
            .unit-item + .unit-item { border-left: none; border-top: 1px solid rgba(255,255,255,.14); padding-top: 14px; }
            .login-card { padding: 24px 20px; border-radius: 18px; }
            .row-between { flex-direction: column; align-items: flex-start; }
        }
        @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }
    </style>
</head>
<body>

<div class="shell">

    {{-- ══ KIRI: IDENTITAS YAYASAN ══ --}}
    <div class="left">
        <div class="left-inner">
            <div class="brand-row">
                <img src="{{ asset('image/logo.png') }}" alt="Logo Yayasan" class="brand-logo-img">
                <div class="brand-title">
                    <h1>SarPras</h1>
                    <h2>Sarana &amp; Prasarana</h2>
                    <p>Yayasan Husnul Abror</p>
                </div>
            </div>

            <div class="left-divider"></div>

            <p class="left-desc">
                Sistem informasi SalPras untuk mengelola aset inventaris sarana dan prasarana
                di lingkungan Yayasan Pendidikan Ponpes Tahfizul Qur'an Husnul Abror
                yang terdiri dari tingkat MI, MTS, dan SMK — terintegrasi QR Code.
            </p>

            <div class="units-box">
                <h3>Unit Sekolah dalam Yayasan</h3>
                <div class="units-row">
                    <div class="unit-item u-mi">
                        <div class="unit-badge ub-mi"><i data-lucide="school" style="width:24px;height:24px;"></i></div>
                        <strong>MI</strong>
                        <span>Madrasah Ibtidaiyah</span>
                    </div>
                    <div class="unit-item u-mts">
                        <div class="unit-badge ub-mts"><i data-lucide="graduation-cap" style="width:24px;height:24px;"></i></div>
                        <strong>MTS</strong>
                        <span>Madrasah Tsanawiyah</span>
                    </div>
                    <div class="unit-item u-smk">
                        <div class="unit-badge ub-smk"><i data-lucide="cpu" style="width:24px;height:24px;"></i></div>
                        <strong>SMK</strong>
                        <span>Sekolah Menengah Kejuruan</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ KANAN: FORM LOGIN ══ --}}
    <div class="right">
        <div class="login-card">
            <div class="lock-badge">
                <span><i data-lucide="shield-check" style="width:26px;height:26px;"></i></span>
            </div>

            <h2>Login Admin</h2>
            <p class="sub">Silakan masuk untuk melanjutkan</p>

            @if ($errors->any())
                <div class="alert alert-error">
                    <i data-lucide="alert-triangle" style="width:16px;height:16px;flex-shrink:0;margin-top:2px;"></i>
                    <div>
                        @if($errors->count() === 1)
                            {{ $errors->first() }}
                        @else
                            Periksa kembali data yang Anda masukkan:
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif

            @if (session('status'))
                <div class="alert alert-ok">
                    <i data-lucide="check-circle-2" style="width:16px;height:16px;flex-shrink:0;margin-top:2px;"></i>
                    <div>{{ session('status') }}</div>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
                @csrf

                <div class="field">
                    <label for="email">Email <span class="req">*</span></label>
                    <div class="input-wrap">
                        <span class="input-icon"><i data-lucide="user" style="width:17px;height:17px;"></i></span>
                        <input type="email" id="email" name="email"
                               class="form-input @error('email') is-error @enderror"
                               placeholder="Masukkan email"
                               value="{{ old('email') }}" autocomplete="email" required autofocus>
                    </div>
                    @error('email')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="field">
                    <label for="password">Password <span class="req">*</span></label>
                    <div class="input-wrap">
                        <span class="input-icon"><i data-lucide="lock" style="width:17px;height:17px;"></i></span>
                        <input type="password" id="password" name="password"
                               class="form-input @error('password') is-error @enderror"
                               placeholder="Masukkan password" autocomplete="current-password" required>
                        <button type="button" class="toggle-pwd" id="togglePwd"
                                title="Tampilkan / sembunyikan password" aria-label="Tampilkan password">
                            <i data-lucide="eye-off" id="eyeIcon" style="width:17px;height:17px;"></i>
                        </button>
                    </div>
                    @error('password')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="row-between">
                    <label class="remember">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <span>Ingat saya</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="forgot">Lupa password?</a>
                    @endif
                </div>

                <button type="submit" class="btn-login" id="submitBtn">
                    <i data-lucide="lock" style="width:17px;height:17px;" id="btnIcon"></i>
                    <span id="btnText">Masuk</span>
                    <span class="spinner" id="spinner"></span>
                </button>
            </form>

            <div class="divider">atau</div>

            <a href="{{ url('/') }}" class="btn-back">
                <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Kembali ke Beranda
            </a>

            <div class="hint-box">
                <i data-lucide="info" style="width:15px;height:15px;"></i>
                <div>
                    <strong>Belum punya akun?</strong> Akun dibuat dan dikelola oleh
                    <strong>Admin Yayasan</strong>. Hubungi bagian administrasi yayasan
                    untuk mendapatkan akses sistem.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-foot">
    © {{ date('Y') }} Yayasan Pendidikan Ponpes Tahfizul Qur'an Husnul Abror. Seluruh hak cipta dilindungi.
</div>

<script>
    if (typeof lucide !== 'undefined') lucide.createIcons();

    /* Tampilkan / sembunyikan password */
    const toggleBtn = document.getElementById('togglePwd');
    const pwdInput  = document.getElementById('password');
    let visible = false;

    toggleBtn.addEventListener('click', () => {
        visible = !visible;
        pwdInput.type = visible ? 'text' : 'password';
        document.getElementById('eyeIcon').setAttribute('data-lucide', visible ? 'eye' : 'eye-off');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    /* Indikator proses saat submit */
    const form      = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', () => {
        const email = document.getElementById('email').value.trim();
        const pwd   = pwdInput.value.trim();
        if (!email || !pwd) return;

        submitBtn.disabled = true;
        document.getElementById('btnText').textContent = 'Memproses…';
        document.getElementById('spinner').style.display = 'block';
        const icon = document.getElementById('btnIcon');
        if (icon) icon.style.display = 'none';
    });
</script>
</body>
</html>
