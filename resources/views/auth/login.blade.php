```html
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login PJL - Sistem Operasional PJL</title>

    <style>
        /* ========================================
           RESET
        ======================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        /* ========================================
           BODY
        ======================================== */

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #1E3157;
            min-height: 100vh;
            color: #1F3157;
        }


        /* ========================================
           JUDUL HALAMAN
        ======================================== */

        .page-title {
            position: absolute;
            top: 18px;
            left: 22px;

            color: #D1D5DB;
            font-size: 15px;
            font-weight: 400;
        }


        /* ========================================
           CONTAINER
        ======================================== */

        .login-container {
            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 40px 20px;
        }


        /* ========================================
           CARD LOGIN
        ======================================== */

        .login-card {
            width: 410px;
            max-width: 100%;

            background: #FFFFFF;

            border-radius: 10px;

            padding: 30px 34px 25px;

            box-shadow:
                0 8px 25px rgba(0, 0, 0, 0.16);
        }


        /* ========================================
           LOGO
        ======================================== */

        .logo {
            text-align: center;
            margin-bottom: 8px;
        }

        .logo-image {
            width: 100px;
            height: 58px;

            object-fit: contain;

            display: block;
            margin: 0 auto;
        }


        /* ========================================
           NAMA SISTEM
        ======================================== */

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


        /* ========================================
           DIVIDER
        ======================================== */

        .divider {
            border: 0;

            border-top: 1px solid #E5E7EB;

            margin: 22px 0 23px;
        }


        /* ========================================
           FORM GROUP
        ======================================== */

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;

            margin-bottom: 7px;

            color: #1F3157;

            font-size: 14px;
            font-weight: 700;
        }


        /* ========================================
           INPUT WRAPPER
        ======================================== */

        .input-wrapper {
            position: relative;

            width: 100%;
        }


        /* ========================================
           INPUT
        ======================================== */

        .form-input {
            width: 100%;
            height: 48px;

            padding: 0 45px;

            border: 1px solid #DDE1E7;

            border-radius: 8px;

            background: #FFFFFF;

            color: #1F3157;

            font-family: Arial, Helvetica, sans-serif;

            font-size: 14px;

            outline: none;

            transition: all 0.2s ease;
        }

        .form-input::placeholder {
            color: #9CA3AF;
            opacity: 1;
        }

        .form-input:focus {
            border-color: #1F3157;

            box-shadow:
                0 0 0 3px rgba(31, 49, 87, 0.08);
        }


        /* ========================================
           ICON INPUT
        ======================================== */

        .input-icon {
            position: absolute;

            left: 15px;
            top: 50%;

            width: 20px;
            height: 20px;

            transform: translateY(-50%);

            color: #6B7280;

            pointer-events: none;

            z-index: 2;
        }

        .input-icon svg {
            width: 100%;
            height: 100%;

            display: block;
        }


        /* ========================================
           PASSWORD TOGGLE
        ======================================== */

        .password-toggle {
            position: absolute;

            right: 13px;
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

        .password-toggle:hover {
            color: #1F3157;
        }

        .password-toggle:focus {
            outline: none;
        }

        .password-toggle svg {
            width: 20px;
            height: 20px;

            display: block;
        }


        /* ========================================
           TOMBOL LOGIN
        ======================================== */

        .login-button {
            width: 100%;
            height: 48px;

            margin-top: 4px;

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

        .login-button:hover {
            background: #D5822B;

            transform: translateY(-1px);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .login-button:focus {
            outline: none;

            box-shadow:
                0 0 0 3px rgba(227, 148, 61, 0.25);
        }


        /* ========================================
           INFORMASI
        ======================================== */

        .login-info {
            display: flex;

            align-items: center;
            justify-content: center;

            gap: 6px;

            margin-top: 15px;

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

        .info-icon svg {
            width: 100%;
            height: 100%;
        }


        /* ========================================
           RESPONSIVE
        ======================================== */

        @media (max-width: 600px) {

            .login-container {
                padding: 25px 16px;
            }

            .login-card {
                width: 100%;
                max-width: 380px;

                padding: 27px 23px 23px;
            }

            .logo-image {
                width: 90px;
                height: 52px;
            }

            .system-title {
                font-size: 16px;
            }

            .company-name {
                font-size: 10px;
            }

            .divider {
                margin: 20px 0 21px;
            }

            .form-group {
                margin-bottom: 16px;
            }

            .form-label {
                font-size: 13px;
            }

            .form-input {
                height: 46px;

                padding-left: 43px;
                padding-right: 43px;

                font-size: 13px;
            }

            .input-icon {
                left: 14px;

                width: 19px;
                height: 19px;
            }

            .password-toggle {
                right: 12px;

                width: 23px;
                height: 23px;
            }

            .password-toggle svg {
                width: 19px;
                height: 19px;
            }

            .login-button {
                height: 46px;

                font-size: 13px;
            }

            .login-info {
                font-size: 9px;
            }
        }


        /* ========================================
           MOBILE KECIL
        ======================================== */

        @media (max-width: 360px) {

            .login-card {
                padding: 24px 18px 20px;
            }

            .logo-image {
                width: 82px;
                height: 48px;
            }

            .system-title {
                font-size: 15px;
            }

            .company-name {
                font-size: 9px;
            }

            .form-label {
                font-size: 12px;
            }

            .form-input {
                height: 44px;

                font-size: 12px;
            }

            .login-button {
                height: 44px;

                font-size: 12px;
            }

            .login-info {
                font-size: 8px;
            }
        }
    </style>
</head>


<body>

    <main class="login-container">

        <div class="login-card">


            <!-- ========================================
                 LOGO KAI
            ======================================== -->

            <div class="logo">

                <img
                    src="{{ asset('images/logo-kai.jpg') }}"
                    alt="Logo KAI"
                    class="logo-image"
                >

            </div>


            <!-- ========================================
                 NAMA SISTEM
            ======================================== -->

            <div class="system-title">
                Sistem Operasional PJL
            </div>

            <div class="company-name">
                PT. Kereta Api Indonesia (Persero)
            </div>


            <!-- ========================================
                 PEMISAH
            ======================================== -->

            <hr class="divider">


            <!-- ========================================
                 FORM LOGIN
            ======================================== -->

            <form onsubmit="loginPJL(event)">


                <!-- ====================================
                     USERNAME
                ==================================== -->

                <div class="form-group">

                    <label
                        for="username"
                        class="form-label"
                    >
                        Username / NIP
                    </label>


                    <div class="input-wrapper">

                        <!-- Icon User -->

                        <span class="input-icon">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >

                                <circle
                                    cx="12"
                                    cy="7"
                                    r="4"
                                ></circle>

                                <path
                                    d="M5 21v-2a7 7 0 0 1 14 0v2"
                                ></path>

                            </svg>

                        </span>


                        <input
                            type="text"
                            name="username"
                            id="username"
                            class="form-input"
                            placeholder="199208242015031002"
                            autocomplete="username"
                            required
                        >

                    </div>

                </div>


                <!-- ====================================
                     PASSWORD
                ==================================== -->

                <div class="form-group">

                    <label
                        for="password"
                        class="form-label"
                    >
                        Kata Sandi
                    </label>


                    <div class="input-wrapper">

                        <!-- Icon Lock -->

                        <span class="input-icon">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >

                                <rect
                                    x="4"
                                    y="10"
                                    width="16"
                                    height="10"
                                    rx="2"
                                ></rect>

                                <path
                                    d="M8 10V7a4 4 0 0 1 8 0v3"
                                ></path>

                            </svg>

                        </span>


                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-input"
                            placeholder="••••••••••••"
                            autocomplete="current-password"
                            required
                        >


                        <!-- Tombol Tampilkan Password -->

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword()"
                            aria-label="Tampilkan password"
                        >

                            <svg
                                id="eyeIcon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >

                                <path
                                    d="M2 12s3.5-7 10-7
                                       10 7 10 7
                                       -3.5 7-10 7
                                       -10-7-10-7z"
                                ></path>

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="3"
                                ></circle>

                            </svg>

                        </button>

                    </div>

                </div>


                <!-- ====================================
                     TOMBOL LOGIN
                ==================================== -->

                <button
                    type="submit"
                    class="login-button"
                >
                    Masuk ke Sistem
                </button>


            </form>


            <!-- ========================================
                 INFORMASI
            ======================================== -->

            <div class="login-info">

                <span class="info-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >

                        <circle
                            cx="12"
                            cy="12"
                            r="9"
                        ></circle>

                        <line
                            x1="12"
                            y1="10"
                            x2="12"
                            y2="16"
                        ></line>

                        <circle
                            cx="12"
                            cy="7"
                            r="0.5"
                            fill="currentColor"
                        ></circle>

                    </svg>

                </span>

                <span>
                    Gunakan kredensial NIP resmi Anda.
                </span>

            </div>


        </div>

    </main>


    <!-- ========================================
         JAVASCRIPT
    ======================================== -->

    <script>

        /* ========================================
           TOGGLE PASSWORD
        ======================================== */

        function togglePassword() {

            const password =
                document.getElementById('password');

            const eyeIcon =
                document.getElementById('eyeIcon');


            if (password.type === 'password') {

                password.type = 'text';

                eyeIcon.innerHTML = `
                    <path
                        d="M2 12s3.5-7 10-7
                        10 7 10 7
                        -3.5 7-10 7
                        -10-7-10-7z"
                    ></path>

                    <line
                        x1="4"
                        y1="4"
                        x2="20"
                        y2="20"
                    ></line>
                `;

            } else {

                password.type = 'password';

                eyeIcon.innerHTML = `
                    <path
                        d="M2 12s3.5-7 10-7
                        10 7 10 7
                        -3.5 7-10 7
                        -10-7-10-7z"
                    ></path>

                    <circle
                        cx="12"
                        cy="12"
                        r="3"
                    ></circle>
                `;
            }
        }


        /* ========================================
           LOGIN
        ======================================== */

        function loginPJL(event) {

            /*
             * Mencegah form melakukan reload
             * saat tombol Enter ditekan.
             */

            event.preventDefault();


            /* Ambil username */

            const username =
                document
                    .getElementById('username')
                    .value
                    .trim();


            /* Ambil password */

            const password =
                document
                    .getElementById('password')
                    .value;


            /* ====================================
               AKUN TESTING
            ==================================== */

            const usernameBenar = '12345678';

            const passwordBenar = '123456';


            /* ====================================
               CEK LOGIN
            ==================================== */

            if (
                username === usernameBenar &&
                password === passwordBenar
            ) {

                /*
                 * Login berhasil
                 * diarahkan ke dashboard
                 */

                window.location.href = '/user/dashboard';

            } else {

                /*
                 * Login gagal
                 */

                alert('Username atau password salah.');

            }

        }

    </script>

</body>

</html>
