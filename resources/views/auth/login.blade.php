<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login PJL - Sistem Operasional PJL</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        [hidden] { display: none !important; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #1E3157;
            min-height: 100vh;
            color: #1F3157;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 20px;
        }

        .login-card {
            width: 410px;
            max-width: 100%;
            background: #FFFFFF;
            border-radius: 10px;
            padding: 24px 30px 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.16);
        }

        .logo { text-align: center; margin-bottom: 8px; }

        .logo-image {
            width: 100px;
            height: 58px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .system-title {
            text-align: center;
            margin-top: 5px;
            color: #1F3157;
            font-size: 17px;
            font-weight: 700;
            line-height: 1.3;
        }

        .company-name {
            text-align: center;
            margin-top: 5px;
            color: #6B7280;
            font-size: 11px;
            line-height: 1.4;
        }

        .divider {
            border: 0;
            border-top: 1px solid #E5E7EB;
            margin: 16px 0;
        }

        .error-message {
            margin-bottom: 16px;
            padding: 11px 13px;
            border: 1px solid #FECACA;
            border-radius: 7px;
            background: #FEF2F2;
            color: #B91C1C;
            font-size: 12px;
            line-height: 1.5;
        }

        /* ========== FORM ========== */

        .form-group { margin-bottom: 12px; }

        .form-label {
            display: block;
            margin-bottom: 5px;
            color: #1F3157;
            font-size: 13px;
            font-weight: 700;
        }

        .input-wrapper { position: relative; width: 100%; }

        .form-input {
            width: 100%;
            height: 42px;
            padding: 0 45px 0 42px;
            border: 1px solid #DDE1E7;
            border-radius: 8px;
            background: #FFFFFF;
            color: #1F3157;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-input::placeholder { color: #9CA3AF; opacity: 1; }

        .form-input:focus {
            border-color: #1F3157;
            box-shadow: 0 0 0 3px rgba(31, 49, 87, 0.08);
        }

        .input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            width: 18px;
            height: 18px;
            transform: translateY(-50%);
            color: #6B7280;
            pointer-events: none;
            z-index: 2;
        }

        .input-icon svg { width: 100%; height: 100%; display: block; }

        .password-toggle {
            position: absolute;
            right: 11px;
            top: 50%;
            width: 24px;
            height: 24px;
            padding: 0;
            border: none;
            background: transparent;
            color: #6B7280;
            cursor: pointer;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover { color: #1F3157; }
        .password-toggle:focus { outline: none; }
        .password-toggle svg { width: 18px; height: 18px; display: block; }

        /* ========== BAGIAN PJL (muncul hanya untuk role pjl) ========== */

        fieldset.pjl-fields { border: 0; min-width: 0; }

        .section-title {
            margin: 16px 0 12px;
            padding-top: 14px;
            border-top: 1px dashed #E5E7EB;
            color: #6B7280;
            font-size: 12px;
            line-height: 1.4;
        }

        .section-title strong {
            display: block;
            color: #1F3157;
            font-size: 13px;
        }

        .jenis-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .jenis-option { flex: 1 1 90px; position: relative; }

        .jenis-group--row { flex-wrap: nowrap; }
        .jenis-group--row .jenis-option { flex: 1; }

        .dinasan-empty { color: #B91C1C; font-size: 12px; }

        .jenis-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .jenis-option label {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 36px;
            padding: 0 6px;
            border: 1px solid #DDE1E7;
            border-radius: 8px;
            color: #6B7280;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .jenis-option input:checked + label {
            border-color: #E3943D;
            background: #FFF7ED;
            color: #B45F0F;
        }

        .jenis-option input:focus-visible + label {
            box-shadow: 0 0 0 3px rgba(227, 148, 61, 0.25);
        }

        /* ========== TOMBOL & INFO ========== */

        .login-button {
            width: 100%;
            height: 44px;
            margin-top: 2px;
            border: none;
            border-radius: 8px;
            background: #E3943D;
            color: #FFFFFF;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .login-button:hover { background: #D5822B; transform: translateY(-1px); }
        .login-button:active { transform: translateY(0); }
        .login-button:focus { outline: none; box-shadow: 0 0 0 3px rgba(227, 148, 61, 0.25); }
        .login-button:disabled { opacity: 0.7; cursor: wait; transform: none; }

        .login-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 12px;
            color: #7B8491;
            font-size: 10px;
            line-height: 1.4;
            text-align: center;
        }

        .info-icon {
            width: 13px;
            height: 13px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .info-icon svg { width: 100%; height: 100%; }

        /* ========== RESPONSIVE ========== */

        @media (max-width: 600px) {
            .login-container { padding: 25px 16px; }
            .login-card { width: 100%; max-width: 380px; padding: 24px 22px 20px; }
            .logo-image { width: 90px; height: 52px; }
            .system-title { font-size: 16px; }
            .company-name { font-size: 10px; }
            .form-label { font-size: 13px; }
            .form-input { height: 44px; font-size: 13px; }
            .login-button { height: 46px; font-size: 13px; }
            .login-info { font-size: 9px; }
        }

        @media (max-width: 360px) {
            .login-card { padding: 22px 16px 18px; }
            .logo-image { width: 82px; height: 48px; }
            .system-title { font-size: 15px; }
            .company-name { font-size: 9px; }
            .form-label { font-size: 12px; }
            .form-input { height: 42px; font-size: 12px; }
            .login-button { height: 44px; font-size: 12px; }
            .login-info { font-size: 8px; }
            .jenis-option label { font-size: 12px; }
        }
    </style>
</head>

<body>

    @php
        // Dikirim dari LoginController::showLoginForm() => [id_shift => nama_shift]
        $dinasanList = $dinasanList ?? [];
        $jenis       = old('jenis_petugas', 'reguler');

        // Tampilkan langsung bagian PJL jika ada input/error PJL dari submit sebelumnya
        $showPjl = old('nomor_pjl') || old('dinasan')
            || $errors->has('nomor_pjl') || $errors->has('dinasan') || $errors->has('jenis_petugas');
    @endphp

    <main class="login-container">

        <div class="login-card">

            <div class="logo">
                <img src="{{ asset('images/logo-kai.jpg') }}" alt="Logo KAI" class="logo-image">
            </div>

            <div class="system-title">Sistem Operasional PJL</div>
            <div class="company-name">PT. Kereta Api Indonesia (Persero)</div>

            <hr class="divider">

            @if ($errors->any())
                <div class="error-message">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Error dari pengecekan via JavaScript --}}
            <div class="error-message" id="clientError" hidden></div>

            <form method="POST" action="{{ route('login.store') }}" id="loginForm">
                @csrf

                <!-- ====================================================
                     SEMUA ROLE (User/PJL, Admin, Superadmin):
                     NIPP / NIPKWT + Kata Sandi
                ===================================================== -->
                <div class="form-group">
                    <label for="nip" class="form-label">NIPP / NIPKWT</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="7" r="4"></circle>
                                <path d="M5 21v-2a7 7 0 0 1 14 0v2"></path>
                            </svg>
                        </span>
                        <input type="text" name="nip" id="nip" class="form-input"
                               placeholder="Masukkan NIPP atau NIPKWT" value="{{ old('nip') }}"
                               autocomplete="username" inputmode="numeric" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Kata Sandi</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <rect x="4" y="10" width="16" height="10" rx="2"></rect>
                                <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                            </svg>
                        </span>
                        <input type="password" name="password" id="password" class="form-input"
                               placeholder="••••••••••••" autocomplete="current-password" required>
                        <button type="button" class="password-toggle" data-target="password"
                                aria-label="Tampilkan password">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- ====================================================
                     KHUSUS PETUGAS PJL
                     Disembunyikan + disabled (tidak divalidasi & tidak
                     terkirim) sampai akun terdeteksi berrole pjl.
                ===================================================== -->
                <fieldset class="pjl-fields" id="pjlFields" {{ $showPjl ? '' : 'hidden disabled' }}>

                    <div class="section-title">
                        <strong>Data Dinas Petugas PJL</strong>
                        Lengkapi data berikut untuk memulai dinas.
                    </div>

                    <!-- Nomor PJL -->
                    <div class="form-group">
                        <label for="nomorPjl" class="form-label">Nomor PJL</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"></path>
                                    <circle cx="12" cy="10" r="2.5"></circle>
                                </svg>
                            </span>
                            <input type="text" name="nomor_pjl" id="nomorPjl" class="form-input"
                                   placeholder="Contoh: 351" value="{{ old('nomor_pjl') }}" required>
                        </div>
                    </div>

                    <!-- Dinasan -->
                    <div class="form-group">
                        <span class="form-label">Dinasan</span>
                        <div class="jenis-group">
                            @forelse ($dinasanList as $shiftId => $shiftNama)
                                <div class="jenis-option">
                                    <input type="radio" name="dinasan" id="dinasan{{ $shiftId }}"
                                           value="{{ $shiftId }}"
                                           {{ (string) old('dinasan') === (string) $shiftId ? 'checked' : '' }}
                                           required>
                                    <label for="dinasan{{ $shiftId }}">{{ $shiftNama }}</label>
                                </div>
                            @empty
                                <p class="dinasan-empty">Data dinasan belum tersedia.</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Reguler / Wakilan (Pengganti) -->
                    <div class="form-group">
                        <span class="form-label">Jenis Petugas</span>
                        <div class="jenis-group jenis-group--row">
                            <div class="jenis-option">
                                <input type="radio" name="jenis_petugas" id="jenisReguler" value="reguler"
                                       {{ $jenis === 'reguler' ? 'checked' : '' }}>
                                <label for="jenisReguler">Reguler</label>
                            </div>
                            <div class="jenis-option">
                                <input type="radio" name="jenis_petugas" id="jenisWakilan" value="wakilan"
                                       {{ $jenis === 'wakilan' ? 'checked' : '' }}>
                                <label for="jenisWakilan">Wakilan (Pengganti)</label>
                            </div>
                        </div>
                    </div>

                </fieldset>

                <button type="submit" class="login-button">Masuk ke Sistem</button>

            </form>

            <div class="login-info">
                <span class="info-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9"></circle>
                        <line x1="12" y1="10" x2="12" y2="16"></line>
                        <circle cx="12" cy="7" r="0.5" fill="currentColor"></circle>
                    </svg>
                </span>
                <span>Gunakan kredensial resmi Anda.</span>
            </div>

        </div>

    </main>

    <script>
        (function () {
            const EYE = `<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path><circle cx="12" cy="12" r="3"></circle>`;
            const EYE_OFF = `<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path><line x1="4" y1="4" x2="20" y2="20"></line>`;

            /* ---- Tampilkan / sembunyikan password ---- */
            document.querySelectorAll('.password-toggle').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const input = document.getElementById(btn.dataset.target);
                    const show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
                    btn.querySelector('svg').innerHTML = show ? EYE_OFF : EYE;
                });
            });

            /* ---- Tampilkan field PJL hanya untuk akun role pjl ---- */
            const form      = document.getElementById('loginForm');
            const nipInput  = document.getElementById('nip');
            const passInput = document.getElementById('password');
            const pjlFields = document.getElementById('pjlFields');
            const submitBtn = form.querySelector('.login-button');
            const errorBox  = document.getElementById('clientError');
            const token     = form.querySelector('input[name="_token"]').value;

            function setPjl(show) {
                pjlFields.hidden = !show;
                pjlFields.disabled = !show;
            }

            function showError(msg) {
                errorBox.textContent = msg;
                errorBox.hidden = false;
            }

            // Ubah NIP / password => sembunyikan lagi, dicek ulang saat submit
            [nipInput, passInput].forEach(function (el) {
                el.addEventListener('input', function () { setPjl(false); });
            });

            form.addEventListener('submit', async function (e) {
                // Field PJL sudah tampil => submit normal (server yang memvalidasi)
                if (!pjlFields.hidden) return;

                e.preventDefault();
                errorBox.hidden = true;
                submitBtn.disabled = true;

                try {
                    const res = await fetch("{{ route('login.cek') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({ nip: nipInput.value, password: passInput.value }),
                    });

                    const data = await res.json();

                    if (!res.ok) {
                        showError(data.message || 'NIPP / NIPKWT atau password tidak valid.');
                        return;
                    }

                    if (data.role === 'pjl') {
                        setPjl(true);
                        document.getElementById('nomorPjl').focus();
                    } else {
                        form.submit(); // admin / superadmin langsung masuk
                    }
                } catch (err) {
                    form.submit(); // jaringan bermasalah => biar server yang menangani
                } finally {
                    submitBtn.disabled = false;
                }
            });
        })();
    </script>

</body>
</html>