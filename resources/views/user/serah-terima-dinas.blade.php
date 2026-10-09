<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Serah Terima Dinas PJL</title>

    <style>
        /* =====================================================
           RESET
        ===================================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6f9;
            color: #26344d;
            overflow-x: hidden;
        }

        body.menu-open {
            overflow: hidden;
        }

        /* =====================================================
           HEADER
        ===================================================== */

        .top-header {
            width: 100%;
            height: 72px;
            padding: 0 24px;

            position: fixed;
            top: 0;
            left: 0;
            z-index: 3000;

            display: flex;
            align-items: center;
            justify-content: space-between;

            background: #1e3156;
            color: white;

            border-top: 1px solid rgba(255, 255, 255, .08);
        }

        .header-left {
            min-width: 0;

            display: flex;
            align-items: center;

            gap: 12px;
        }

        .kai-logo {
            width: 40px;
            height: 40px;

            flex-shrink: 0;

            display: block;

            object-fit: contain;

            background: #fff;

            border-radius: 6px;

            padding: 2px;
        }

        .header-brand {
            min-width: 0;

            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand-title {
            color: #fff;

            font-size: 18px;
            font-weight: 800;

            line-height: 1.2;

            white-space: nowrap;
        }

        .brand-subtitle {
            margin-top: 3px;

            color: #d5deeb;

            font-size: 10px;

            white-space: nowrap;
        }

        .header-right {
            display: flex;
            align-items: center;

            gap: 8px;

            flex-shrink: 0;
        }

        /* =====================================================
           HAMBURGER
        ===================================================== */

        .menu-button {
            width: 40px;
            height: 40px;
            padding: 0;

            position: relative;
            z-index: 3100;

            display: flex;
            align-items: center;
            justify-content: center;

            border: none;
            border-radius: 6px;

            background: transparent;
            color: white;

            font-size: 27px;
            line-height: 1;

            cursor: pointer;

            transition:
                background .2s ease,
                transform .2s ease;
        }

        .menu-button:hover {
            background: rgba(255, 255, 255, .10);
        }

        .menu-button:active {
            transform: scale(.94);
        }

        .menu-button:focus {
            outline: 2px solid rgba(255, 255, 255, .35);
            outline-offset: 2px;
        }

        /* =====================================================
           AVATAR
        ===================================================== */

        .user-circle {
            width: 40px;
            height: 40px;

            display: flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;

            border-radius: 50%;

            background: #b9822b;
            color: white;

            font-size: 14px;
            font-weight: 700;
        }

        /* =====================================================
           OVERLAY
        ===================================================== */

        .menu-overlay {
            position: fixed;
            inset: 0;
            z-index: 2000;

            background: rgba(0, 0, 0, .40);

            opacity: 0;
            visibility: hidden;
            pointer-events: none;

            transition:
                opacity .25s ease,
                visibility .25s ease;
        }

        .menu-overlay.show {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        /* =====================================================
           SIDE MENU
        ===================================================== */

        .mobile-menu {
            width: 280px;
            max-width: 82vw;
            height: 100vh;

            position: fixed;
            top: 0;
            left: 0;
            z-index: 2500;

            display: flex;
            flex-direction: column;

            background: #1e3156;
            color: white;

            transform: translateX(-100%);

            transition: transform .30s ease;

            box-shadow: 8px 0 25px rgba(0, 0, 0, .16);

            overflow-y: auto;

            pointer-events: none;
        }

        .mobile-menu.show {
            transform: translateX(0);
            pointer-events: auto;
        }

        /* =====================================================
           BRAND SIDE MENU
        ===================================================== */

        .menu-brand {
            height: 88px;
            padding: 0 20px;

            display: flex;
            flex-direction: column;
            justify-content: center;

            flex-shrink: 0;

            border-bottom: 1px solid rgba(255, 255, 255, .13);
        }

        .menu-brand-title {
            color: white;

            font-size: 15px;
            font-weight: 800;
        }

        .menu-brand-subtitle {
            margin-top: 4px;

            color: #d5deeb;

            font-size: 9px;
        }

        /* =====================================================
           MENU LIST
        ===================================================== */

        .menu-list {
            padding: 12px 12px 0;

            display: flex;
            flex-direction: column;

            gap: 3px;
        }

        .menu-link {
            width: 100%;
            min-height: 44px;
            padding: 0 14px;

            display: flex;
            align-items: center;

            gap: 10px;

            text-decoration: none;

            color: #fff;
            background: transparent;

            border-radius: 7px;

            font-size: 14px;
            font-weight: 500;

            transition:
                background .2s ease,
                transform .2s ease;
        }

        .menu-link:hover {
            background: rgba(255, 255, 255, .08);
        }

        .menu-link.active {
            background: #3b82b8;
            color: white;

            font-weight: 700;
        }

        .menu-link:active {
            transform: scale(.99);
        }

        .menu-icon {
            width: 22px;
            min-width: 22px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 16px;
        }

        /* =====================================================
           MENU BOTTOM
        ===================================================== */

        .menu-bottom {
            margin-top: auto;

            padding: 12px;
        }

        .active-user-card {
            padding: 11px;

            background: #2e507f;

            border-radius: 8px;

            color: white;
        }

        .active-user-label {
            margin-bottom: 4px;

            color: #dbe5f1;

            font-size: 10px;
        }

        .active-user-name {
            margin-bottom: 5px;

            font-size: 15px;
            font-weight: 800;
        }

        .active-user-info {
            color: #edf2f8;

            font-size: 10px;

            line-height: 1.45;
        }

        /* =====================================================
           LOGOUT
        ===================================================== */

        .logout-link {
            width: 100%;
            min-height: 44px;
            margin-top: 8px;
            padding: 0 14px;

            display: flex;
            align-items: center;

            gap: 10px;

            text-decoration: none;

            color: white;

            border: 1px solid rgba(255, 255, 255, .25);
            border-radius: 7px;

            font-size: 14px;
            font-weight: 500;

            transition:
                background .2s ease,
                transform .2s ease;
        }

        .logout-link:hover {
            background: rgba(255, 255, 255, .08);
        }

        .logout-link:active {
            transform: scale(.99);
        }

        /* =====================================================
           MAIN
        ===================================================== */

        .main {
            width: 100%;
            min-height: calc(100vh - 72px);

            padding: 92px 26px 35px;
        }

        .page-card {
            width: 100%;

            padding: 22px 24px 25px;

            background: white;

            border: 1px solid #e2e6eb;
            border-radius: 7px;
        }

        /* =====================================================
           DOCUMENT HEADER
        ===================================================== */

        .document-header {
            margin-bottom: 20px;

            text-align: center;
        }

        .company {
            color: #1e3156;

            font-size: 10px;
            font-weight: 700;
        }

        .department {
            margin-top: 4px;

            color: #788394;

            font-size: 9px;
        }

        .page-title {
            margin-top: 9px;

            color: #1e3156;

            font-size: 22px;
            font-weight: 800;
        }

        .page-subtitle {
            margin-top: 5px;

            color: #7d8795;

            font-size: 10px;
        }

        /* =====================================================
           INFORMASI DINAS
        ===================================================== */

        .info-card {
            margin-bottom: 15px;
            padding: 15px;

            border: 1px solid #e3e7eb;
            border-radius: 7px;
        }

        .info-grid {
            display: grid;

            grid-template-columns: 1fr 1.1fr 1fr;

            gap: 10px;

            margin-bottom: 12px;
        }

        .info-grid-bottom {
            display: grid;

            grid-template-columns: 1.2fr 1fr;

            gap: 10px;
        }

        .field-label {
            margin-bottom: 6px;

            color: #647084;

            font-size: 10px;
            font-weight: 700;
        }

        .input,
        .select,
        textarea {
            width: 100%;

            border: 1px solid #e0e5ea;
            border-radius: 5px;

            background: #f7f8fa;
            color: #39465b;

            font-size: 12px;

            outline: none;
        }

        .input,
        .select {
            height: 40px;
            padding: 0 10px;
        }

        textarea {
            padding: 10px;

            resize: vertical;

            line-height: 1.5;
        }

        .input:focus,
        .select:focus,
        textarea:focus {
            border-color: #55769a;

            background: white;
        }

        /* =====================================================
           SECTION
        ===================================================== */

        .section {
            margin-bottom: 15px;
            padding: 15px;

            border: 1px solid #e3e7eb;
            border-radius: 7px;
        }

        .section-title {
            margin-bottom: 10px;

            display: flex;
            align-items: center;

            gap: 8px;

            color: #1e3156;

            font-size: 14px;
            font-weight: 800;
        }

        .section-number {
            width: 25px;
            height: 25px;

            display: flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;

            border-radius: 50%;

            background: #1e3156;
            color: white;

            font-size: 10px;
        }

        .section-description {
            margin-bottom: 9px;

            color: #687588;

            font-size: 10px;
        }

        /* =====================================================
           DATA KA OTOMATIS
        ===================================================== */

        .auto-ka-textarea {
            min-height: 95px;

            background: #f7f9fc;

            border-color: #d9e1ea;

            color: #26344d;

            font-weight: 600;

            cursor: default;

            resize: none;
        }

        .auto-ka-textarea:focus {
            background: #f7f9fc;

            border-color: #d9e1ea;
        }

        .auto-ka-empty {
            color: #8994a3;
            font-weight: 400;
        }

        .auto-ka-info {
            display: flex;
            align-items: center;

            gap: 7px;

            margin-top: 8px;

            color: #6f7d8f;

            font-size: 9px;
        }

        .auto-ka-dot {
            width: 7px;
            height: 7px;

            flex-shrink: 0;

            border-radius: 50%;

            background: #22b573;
        }

        /* =====================================================
           CATATAN
        ===================================================== */

        .note-area {
            min-height: 90px;
        }

        .note-date {
            margin-top: 7px;

            color: #8a94a2;

            font-size: 9px;
        }

        /* =====================================================
           PENGAMANAN
        ===================================================== */

        .security-list {
            overflow: hidden;

            border: 1px solid #e1e5ea;
            border-radius: 6px;
        }

        .security-row {
            min-height: 45px;
            padding: 8px 12px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 15px;

            border-bottom: 1px solid #e9edf1;

            color: #4b586b;

            font-size: 10px;
        }

        .security-row:last-child {
            border-bottom: none;
        }

        .toggle-group {
            display: flex;

            gap: 5px;
        }

        .toggle {
            min-width: 85px;
            height: 30px;
            padding: 0 10px;

            border: 1px solid #d8dee6;
            border-radius: 15px;

            background: white;
            color: #7a8594;

            font-size: 9px;

            cursor: pointer;

            transition: .2s;
        }

        .toggle.active {
            border-color: #22b573;

            background: #22b573;
            color: white;
        }

        /* =====================================================
           INVENTARIS
        ===================================================== */

        .inventory-grid {
            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 15px;
        }

        .inventory-column {
            display: flex;
            flex-direction: column;

            gap: 6px;
        }

        .inventory-row {
            min-height: 42px;
            padding: 5px 7px;

            display: grid;
            align-items: center;

            grid-template-columns:
                22px
                minmax(0, 1fr)
                55px
                105px
                55px;

            gap: 5px;

            background: #f7f8fa;

            border: 1px solid #e3e7eb;
            border-radius: 5px;

            font-size: 9px;
        }

        .inventory-number {
            color: #647084;
        }

        .inventory-name {
            color: #465368;

            line-height: 1.4;
        }

        .qty {
            width: 100%;
            height: 30px;

            border: 1px solid #dfe4e9;
            border-radius: 4px;

            background: white;

            text-align: center;

            font-size: 10px;
        }

        .condition {
            width: 100%;
            height: 30px;

            border: 1px solid #dfe4e9;
            border-radius: 4px;

            background: white;

            font-size: 9px;
        }

        .small-action {
            height: 30px;

            border: 1px solid #dfe4e9;
            border-radius: 4px;

            background: white;

            font-size: 9px;

            cursor: pointer;

            transition: .2s;
        }

        .small-action:hover {
            background: #eef3f8;
        }

        /* =====================================================
           TEXTAREA
        ===================================================== */

        .large-textarea {
            height: 120px;
        }

        .short-input {
            height: 40px;
        }

        /* =====================================================
           SIGNATURE
        ===================================================== */

        .signature-grid {
            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 15px;
        }

        .signature-card {
            padding: 14px;

            background: #f7f8fa;

            border: 1px solid #e1e5ea;
            border-radius: 7px;
        }

        .signature-title {
            color: #26344d;

            font-size: 10px;
            font-weight: 800;
        }

        .signature-subtitle {
            margin-top: 4px;

            color: #7c8796;

            font-size: 9px;
        }

        .signature-input {
            margin-top: 8px;
        }

        .signature-area {
            height: 130px;
            margin-top: 7px;

            position: relative;

            display: flex;
            align-items: center;
            justify-content: center;

            overflow: hidden;

            background: white;

            border: 1px dashed #d6dce3;
            border-radius: 5px;

            cursor: pointer;

            transition:
                border-color .2s ease,
                background .2s ease;
        }

        .signature-area.active {
            border-color: #1e3156;

            background: #fafcff;

            cursor: crosshair;
        }

        .signature-area.saved {
            border-color: #22b573;

            background: #fff;
        }

        .signature-area.show-result {
            cursor: pointer;
        }

        .signature-canvas {
            width: 100%;
            height: 100%;

            display: block;

            cursor: crosshair;

            pointer-events: none;

            touch-action: none;
        }

        .signature-area.active .signature-canvas {
            pointer-events: auto;
        }

        .signature-placeholder {
            position: absolute;

            color: #b0b8c3;

            font-size: 9px;

            pointer-events: none;
        }

        .signature-result {
            position: absolute;

            inset: 0;

            width: 100%;
            height: 100%;

            padding: 10px;

            display: none;

            object-fit: contain;

            pointer-events: none;
        }

        .signature-area.show-result .signature-result {
            display: block;
        }

        .signature-area.show-result .signature-canvas {
            display: none;
        }

        .signature-area.show-result .signature-placeholder {
            display: none !important;
        }

        /* =====================================================
           SIGNATURE BUTTONS
        ===================================================== */

        .signature-actions {
            margin-top: 10px;

            display: flex;
            justify-content: center;
            align-items: center;

            gap: 8px;

            flex-wrap: wrap;
        }

        .signature-save,
        .signature-clear,
        .signature-download {
            min-height: 32px;

            padding: 7px 14px;

            border: none;
            border-radius: 6px;

            font-size: 11px;
            font-weight: 600;

            cursor: pointer;

            transition:
                background .2s ease,
                transform .2s ease,
                opacity .2s ease;
        }

        .signature-save {
            background: #1e3a5f;
            color: white;
        }

        .signature-save:hover {
            background: #294a72;
        }

        .signature-clear {
            background: #e5e7eb;
            color: #374151;
        }

        .signature-clear:hover {
            background: #d1d5db;
        }

        .signature-download {
            background: #198754;
            color: white;
        }

        .signature-download:hover {
            background: #157347;
        }

        .signature-save:active,
        .signature-clear:active,
        .signature-download:active {
            transform: scale(.97);
        }

        .signature-name {
            margin-top: 8px;

            color: #536174;

            font-size: 9px;

            line-height: 1.6;
        }

        /* =====================================================
           BOTTOM BUTTON
        ===================================================== */

        .bottom {
            margin-top: 8px;

            display: flex;
            justify-content: flex-end;

            gap: 8px;
        }

        .button {
            height: 38px;
            padding: 0 18px;

            border-radius: 5px;

            font-size: 10px;
            font-weight: 700;

            cursor: pointer;

            transition: .2s;
        }

        .button-outline {
            background: white;
            color: #1e3156;

            border: 1px solid #1e3156;
        }

        .button-outline:hover {
            background: #eef3f8;
        }

        .button-primary {
            background: #1e3156;
            color: white;

            border: 1px solid #1e3156;
        }

        .button-primary:hover {
            background: #29446f;
        }

        /* =====================================================
           ALERT
        ===================================================== */

        .info-alert {
            margin-top: 8px;
            padding: 10px 12px;

            display: flex;
            align-items: center;

            gap: 9px;

            background: #eef6ff;

            border: 1px solid #cfe2fa;
            border-radius: 5px;

            color: #47709f;

            font-size: 9px;

            line-height: 1.5;
        }

        .alert-icon {
            width: 20px;
            height: 20px;

            display: flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;

            border: 1px solid #5d8ec5;
            border-radius: 50%;

            font-size: 10px;
        }

        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 900px) {

            .top-header {
                height: 86px;
                padding: 0 25px;
            }

            .brand-title {
                font-size: 21px;
            }

            .brand-subtitle {
                font-size: 12px;
            }

            .main {
                min-height: calc(100vh - 86px);
                padding: 104px 15px 30px;
            }

            .page-card {
                padding: 18px;
            }

            .info-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .info-grid-bottom {
                grid-template-columns: 1fr 1fr;
            }

            .inventory-grid {
                grid-template-columns: 1fr;
            }

            .signature-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 700px) {

            .top-header {
                height: 64px;
                padding: 0 15px;
            }

            .header-left {
                gap: 8px;
            }

            .brand-title {
                font-size: 15px;
            }

            .brand-subtitle {
                margin-top: 3px;
                font-size: 9px;
            }

            .header-right {
                gap: 8px;
            }

            .menu-button {
                width: 36px;
                height: 36px;

                flex-shrink: 0;

                font-size: 27px;
            }

            .kai-logo {
                width: 34px;
                height: 34px;
            }

            .user-circle {
                width: 40px;
                height: 40px;
                font-size: 13px;
            }

            .mobile-menu {
                width: 260px;
                max-width: 82vw;
            }

            .menu-brand {
                height: 82px;
                padding: 0 20px;
            }

            .menu-brand-title {
                font-size: 16px;
            }

            .menu-brand-subtitle {
                margin-top: 5px;
                font-size: 10px;
            }

            .menu-list {
                padding: 14px 12px 0;
                gap: 5px;
            }

            .menu-link {
                min-height: 46px;
                padding: 0 14px;
                gap: 11px;
                border-radius: 10px;
                font-size: 14px;
            }

            .menu-icon {
                font-size: 16px;
            }

            .menu-bottom {
                padding: 12px;
            }

            .active-user-card {
                padding: 12px;
                border-radius: 8px;
            }

            .active-user-label {
                font-size: 10px;
            }

            .active-user-name {
                font-size: 16px;
            }

            .active-user-info {
                font-size: 10px;
            }

            .logout-link {
                min-height: 46px;
                margin-top: 14px;
                padding: 0 14px;
                gap: 11px;
                border-radius: 10px;
                font-size: 14px;
            }

            .main {
                width: 100%;
                min-height: calc(100vh - 64px);
                padding: 74px 10px 25px;
            }

            .page-card {
                width: 100%;
                padding: 16px 12px 20px;
                border-radius: 6px;
            }

            .document-header {
                margin-bottom: 16px;
            }

            .company {
                font-size: 9px;
                line-height: 1.4;
            }

            .department {
                font-size: 7px;
                line-height: 1.4;
            }

            .page-title {
                margin-top: 8px;
                font-size: 18px;
                line-height: 1.3;
            }

            .page-subtitle {
                margin-top: 5px;
                font-size: 8px;
                line-height: 1.5;
            }

            .info-card {
                margin-bottom: 12px;
                padding: 12px;
            }

            .info-grid,
            .info-grid-bottom {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .field-label {
                margin-bottom: 5px;
                font-size: 8px;
            }

            .input,
            .select {
                height: 38px;
                padding: 0 10px;
                border-radius: 5px;
                font-size: 13px;
            }

            textarea {
                padding: 9px;
                border-radius: 5px;
                font-size: 12px;
            }

            .section {
                margin-bottom: 12px;
                padding: 12px;
                border-radius: 6px;
            }

            .section-title {
                margin-bottom: 9px;
                gap: 7px;
                font-size: 12px;
                line-height: 1.4;
            }

            .section-number {
                width: 23px;
                height: 23px;
                font-size: 9px;
            }

            .section-description {
                margin-bottom: 8px;
                font-size: 9px;
                line-height: 1.5;
            }

            .auto-ka-info {
                font-size: 8px;
                line-height: 1.5;
            }

            .security-row {
                min-height: 55px;
                padding: 8px 9px;
                gap: 10px;
                font-size: 10px;
                line-height: 1.4;
            }

            .toggle-group {
                flex-shrink: 0;
                gap: 5px;
            }

            .toggle {
                min-width: 70px;
                height: 32px;
                padding: 0 8px;
                font-size: 8px;
            }

            .inventory-grid {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .inventory-column {
                gap: 7px;
            }

            .inventory-row {
                min-height: 60px;

                grid-template-columns:
                    22px
                    minmax(120px, 1fr)
                    45px
                    90px
                    48px;

                gap: 5px;

                padding: 7px;

                overflow-x: auto;

                font-size: 8px;
            }

            .qty {
                height: 32px;
                font-size: 10px;
            }

            .condition {
                height: 32px;
                font-size: 8px;
            }

            .small-action {
                height: 32px;
                font-size: 8px;
            }

            .signature-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .signature-card {
                padding: 12px;
                border-radius: 6px;
            }

            .signature-title {
                font-size: 10px;
            }

            .signature-subtitle {
                font-size: 8px;
            }

            .signature-area {
                height: 130px;
            }

            .signature-actions {
                gap: 7px;
            }

            .signature-save,
            .signature-clear,
            .signature-download {
                flex: 1;
                min-height: 36px;
                font-size: 9px;
            }

            .signature-name {
                font-size: 8px;
            }

            .bottom {
                width: 100%;

                margin-top: 12px;

                display: flex;
                flex-direction: column;

                gap: 8px;
            }

            .button {
                width: 100%;
                height: 42px;
                font-size: 10px;
            }

            .info-alert {
                margin-top: 12px;
                padding: 10px;
                align-items: flex-start;
                gap: 8px;
                font-size: 8px;
                line-height: 1.5;
            }

            .alert-icon {
                width: 18px;
                height: 18px;
                font-size: 9px;
            }
        }

        /* =====================================================
           HP KECIL
        ===================================================== */

        @media (max-width: 400px) {

            .top-header {
                height: 60px;
                padding: 0 10px;
            }

            .header-left {
                gap: 5px;
            }

            .brand-title {
                font-size: 13px;
            }

            .brand-subtitle {
                font-size: 7px;
            }

            .menu-button {
                width: 32px;
                height: 32px;
                font-size: 24px;
            }

            .kai-logo {
                width: 30px;
                height: 30px;
            }

            .user-circle {
                width: 35px;
                height: 35px;
                font-size: 10px;
            }

            .mobile-menu {
                width: 260px;
                max-width: 88vw;
            }

            .menu-brand {
                height: 82px;
                padding: 0 18px;
            }

            .menu-brand-title {
                font-size: 16px;
            }

            .menu-brand-subtitle {
                font-size: 9px;
            }

            .menu-link {
                min-height: 43px;
                padding: 0 14px;
                font-size: 13px;
            }

            .active-user-card {
                padding: 12px;
            }

            .active-user-label {
                font-size: 10px;
            }

            .active-user-name {
                font-size: 16px;
            }

            .active-user-info {
                font-size: 10px;
            }

            .logout-link {
                min-height: 43px;
                padding: 0 14px;
                font-size: 13px;
            }

            .main {
                padding: 70px 7px 20px;
            }

            .page-card {
                padding: 14px 9px 18px;
            }

            .page-title {
                font-size: 16px;
            }

            .page-subtitle {
                font-size: 7px;
            }

            .section {
                padding: 10px;
            }

            .section-title {
                font-size: 10px;
            }

            .section-description {
                font-size: 8px;
            }

            .security-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .toggle-group {
                width: 100%;
            }

            .toggle {
                flex: 1;
            }

            .inventory-row {
                grid-template-columns:
                    20px
                    minmax(120px, 1fr)
                    42px
                    80px
                    44px;

                gap: 3px;

                font-size: 7px;
            }

            .qty,
            .condition,
            .small-action {
                font-size: 7px;
            }

            .signature-area {
                height: 115px;
            }

            .signature-actions {
                flex-direction: column;
            }

            .signature-save,
            .signature-clear,
            .signature-download {
                width: 100%;
                flex: none;
            }
        }


        /* =====================================================
           VISUAL REFINEMENT
           Tampilan lebih rapi, modern, dan konsisten
        ===================================================== */

        :root {
            --kai-navy: #17345f;
            --kai-blue: #245b8f;
            --kai-blue-soft: #eef5fb;
            --kai-gold: #c8943e;
            --text-main: #24344d;
            --text-muted: #6f7d90;
            --border: #dfe6ee;
            --surface: #ffffff;
            --surface-soft: #f7f9fc;
            --success: #1f9d68;
            --shadow-sm: 0 2px 8px rgba(23, 52, 95, .06);
            --shadow-md: 0 10px 30px rgba(23, 52, 95, .08);
        }

        body {
            font-family: Inter, "Segoe UI", Arial, Helvetica, sans-serif;
            background:
                linear-gradient(180deg, #eef3f8 0, #f6f8fb 260px, #f4f6f9 100%);
            color: var(--text-main);
            font-size: 14px;
        }

        .top-header {
            height: 68px;
            padding: 0 28px;
            background: linear-gradient(135deg, #17345f, #214976);
            border: 0;
            box-shadow: 0 3px 16px rgba(15, 38, 68, .18);
        }

        .header-left {
            gap: 13px;
        }

        .kai-logo {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            padding: 3px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .12);
        }

        .brand-title {
            font-size: 17px;
            letter-spacing: .2px;
        }

        .brand-subtitle {
            font-size: 10px;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .user-circle {
            width: 38px;
            height: 38px;
            background: var(--kai-gold);
            box-shadow: 0 2px 7px rgba(0, 0, 0, .15);
        }

        .main {
            min-height: calc(100vh - 68px);
            padding: 92px 30px 40px;
        }

        .page-card {
            width: min(1320px, 100%);
            margin: 0 auto;
            padding: 30px 32px 34px;
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow-md);
        }

        .document-header {
            margin-bottom: 26px;
            padding-bottom: 20px;
            border-bottom: 1px solid #edf0f4;
        }

        .company {
            font-size: 11px;
            letter-spacing: .4px;
        }

        .department {
            margin-top: 5px;
            font-size: 10px;
        }

        .page-title {
            margin-top: 10px;
            font-size: 28px;
            letter-spacing: -.4px;
        }

        .page-subtitle {
            max-width: 760px;
            margin: 7px auto 0;
            font-size: 11px;
            line-height: 1.6;
        }

        .info-card,
        .section {
            margin-bottom: 18px;
            padding: 20px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
        }

        .info-card {
            background: linear-gradient(180deg, #fff, #fbfcfe);
        }

        .info-grid,
        .info-grid-bottom {
            gap: 14px;
        }

        .info-grid {
            margin-bottom: 14px;
        }

        .field-label {
            margin-bottom: 7px;
            color: #596a80;
            font-size: 10px;
            letter-spacing: .25px;
        }

        .input,
        .select,
        textarea {
            border-color: #d8e0e9;
            border-radius: 7px;
            background: var(--surface-soft);
            color: var(--text-main);
            font-size: 13px;
            transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
        }

        .input,
        .select {
            height: 42px;
            padding: 0 12px;
        }

        textarea {
            padding: 12px;
            line-height: 1.55;
        }

        .input:focus,
        .select:focus,
        textarea:focus {
            border-color: #6d9bc3;
            box-shadow: 0 0 0 3px rgba(36, 91, 143, .10);
            background: #fff;
        }

        .section-title {
            margin-bottom: 12px;
            gap: 10px;
            font-size: 15px;
        }

        .section-number {
            width: 28px;
            height: 28px;
            background: var(--kai-navy);
            font-size: 10px;
            box-shadow: 0 2px 5px rgba(23, 52, 95, .18);
        }

        .section-description {
            margin: 0 0 11px 38px;
            color: var(--text-muted);
            font-size: 10px;
            line-height: 1.55;
        }

        .auto-ka-textarea {
            min-height: 105px;
            background: #f8fafc;
            border-color: #dce5ee;
            font-size: 12px;
        }

        .auto-ka-info {
            margin: 9px 0 0 2px;
            color: #738196;
            font-size: 9px;
        }

        .auto-ka-dot {
            width: 7px;
            height: 7px;
            background: var(--success);
            box-shadow: 0 0 0 3px rgba(31, 157, 104, .10);
        }

        .security-list {
            border-color: var(--border);
            border-radius: 9px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.6);
        }

        .security-row {
            min-height: 54px;
            padding: 10px 14px;
            font-size: 11px;
            background: #fff;
        }

        .security-row:nth-child(even) {
            background: #fbfcfe;
        }

        .toggle-group {
            gap: 7px;
        }

        .toggle {
            min-width: 100px;
            height: 32px;
            border-radius: 17px;
            font-size: 10px;
        }

        .toggle.active {
            border-color: var(--success);
            background: var(--success);
            box-shadow: 0 2px 6px rgba(31, 157, 104, .18);
        }

        .inventory-grid {
            gap: 18px;
        }

        .inventory-column {
            gap: 8px;
        }

        .inventory-row {
            min-height: 48px;
            padding: 6px 9px;
            grid-template-columns: 28px minmax(0, 1fr) 60px 115px 65px;
            gap: 7px;
            background: #f9fafc;
            border-color: #e1e7ee;
            border-radius: 7px;
            font-size: 10px;
            transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
        }

        .inventory-row:hover {
            background: #fff;
            border-color: #cfd9e4;
            box-shadow: var(--shadow-sm);
        }

        .inventory-name {
            color: #34445b;
            font-weight: 500;
        }

        .qty,
        .condition {
            height: 32px;
            border-color: #d8e0e8;
            border-radius: 6px;
            font-size: 10px;
        }

        .signature-grid {
            gap: 18px;
        }

        .signature-card {
            padding: 17px;
            background: #f9fafc;
            border-color: var(--border);
            border-radius: 10px;
        }

        .signature-title {
            font-size: 11px;
        }

        .signature-subtitle {
            font-size: 9px;
            line-height: 1.5;
        }

        .signature-area {
            height: 145px;
            margin-top: 9px;
            background:
                linear-gradient(#fff, #fff) padding-box,
                repeating-linear-gradient(
                    45deg,
                    #edf1f5 0,
                    #edf1f5 1px,
                    transparent 1px,
                    transparent 8px
                ) border-box;
            border: 1px dashed #cfd8e3;
            border-radius: 8px;
        }

        .signature-area.active {
            border-color: var(--kai-blue);
            background: #fbfdff;
            box-shadow: 0 0 0 3px rgba(36, 91, 143, .08);
        }

        .signature-actions {
            margin-top: 11px;
            gap: 7px;
        }

        .signature-save,
        .signature-clear,
        .signature-download {
            min-height: 34px;
            padding: 7px 15px;
            border-radius: 7px;
            font-size: 10px;
        }

        .signature-save {
            background: var(--kai-navy);
        }

        .signature-download {
            background: var(--success);
        }

        .bottom {
            margin-top: 18px;
            padding-top: 4px;
        }

        .button {
            height: 42px;
            padding: 0 20px;
            border-radius: 8px;
            font-size: 11px;
        }

        .button-primary {
            box-shadow: 0 3px 9px rgba(23, 52, 95, .16);
        }

        .info-alert {
            margin-top: 12px;
            padding: 11px 13px;
            border-radius: 8px;
            font-size: 10px;
        }

        /* Sidebar lebih bersih */
        .mobile-menu {
            width: 292px;
            background: linear-gradient(180deg, #17345f, #1d426f);
            box-shadow: 12px 0 35px rgba(0, 0, 0, .18);
        }

        .menu-brand {
            height: 92px;
            padding: 0 22px;
        }

        .menu-brand-title {
            font-size: 16px;
            letter-spacing: .2px;
        }

        .menu-brand-subtitle {
            font-size: 9px;
            letter-spacing: .45px;
            text-transform: uppercase;
        }

        .menu-list {
            padding: 15px 12px 0;
            gap: 5px;
        }

        .menu-link {
            min-height: 46px;
            border-radius: 9px;
            font-size: 13px;
        }

        .menu-link.active {
            background: #397eae;
            box-shadow: 0 3px 8px rgba(0, 0, 0, .12);
        }

        .active-user-card {
            padding: 13px;
            background: rgba(255,255,255,.10);
            border: 1px solid rgba(255,255,255,.10);
            border-radius: 10px;
        }

        .logout-link {
            min-height: 46px;
            border-radius: 9px;
        }

        @media (max-width: 900px) {
            .main {
                padding: 100px 18px 30px;
            }

            .page-card {
                padding: 24px 20px 28px;
            }

            .page-title {
                font-size: 24px;
            }
        }

        @media (max-width: 700px) {
            .top-header {
                height: 64px;
                padding: 0 14px;
            }

            .main {
                min-height: calc(100vh - 64px);
                padding: 78px 10px 24px;
            }

            .page-card {
                padding: 18px 12px 22px;
                border-radius: 10px;
            }

            .document-header {
                padding-bottom: 16px;
            }

            .page-title {
                font-size: 20px;
            }

            .page-subtitle {
                font-size: 9px;
            }

            .info-card,
            .section {
                padding: 14px;
                border-radius: 9px;
            }

            .section-description {
                margin-left: 0;
                margin-top: 2px;
            }

            .inventory-row {
                grid-template-columns: 24px minmax(120px, 1fr) 52px 100px 58px;
                min-width: 365px;
            }

            .inventory-column {
                overflow-x: auto;
                padding-bottom: 2px;
            }

            .signature-area {
                height: 125px;
            }
        }

        @media print {
            .page-card {
                width: 100%;
                margin: 0;
                box-shadow: none;
            }

            .info-card,
            .section {
                box-shadow: none;
            }
        }

        /* =====================================================
           PRINT
        ===================================================== */

        @media print {

            body {
                background: white;
            }

            .top-header {
                display: none;
            }

            .menu-overlay,
            .mobile-menu {
                display: none !important;
            }

            .main {
                width: 100%;
                padding: 0;
            }

            .page-card {
                padding: 0;
                border: none;
            }

            .bottom,
            .info-alert,
            .signature-actions {
                display: none;
            }

            .section,
            .info-card,
            .signature-card {
                break-inside: avoid;
            }

            .signature-area {
                border-color: #d6dce3;
            }

            .signature-area.show-result {
                border-color: #d6dce3;
            }

            .auto-ka-textarea {
                border-color: #d6dce3;
                background: white;
            }
        }
    
        /* =====================================================
           PRINT FIX — HASIL KA TIDAK TERPOTONG SAAT DICETAK
        ===================================================== */
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        @media print {
            html,
            body {
                width: 100%;
                min-width: 0;
                height: auto;
                overflow: visible !important;
                background: #fff !important;
                color: #000;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            body {
                font-size: 10pt;
            }

            .top-header,
            .mobile-menu,
            .menu-overlay,
            .menu-button,
            .header-right,
            .bottom,
            .info-alert {
                display: none !important;
            }

            .main {
                width: 100% !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .page-card {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }

            .document-header {
                margin-bottom: 12px !important;
                padding-bottom: 10px !important;
            }

            .company {
                font-size: 9pt !important;
            }

            .department {
                font-size: 8pt !important;
            }

            .page-title {
                margin-top: 5px !important;
                font-size: 18pt !important;
            }

            .page-subtitle {
                font-size: 8pt !important;
            }

            .info-card,
            .section {
                width: 100% !important;
                margin-bottom: 9px !important;
                padding: 10px !important;
                border: 1px solid #cfd6df !important;
                border-radius: 5px !important;
                box-shadow: none !important;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .section-title {
                margin-bottom: 7px !important;
                font-size: 10pt !important;
            }

            .section-number {
                width: 20px !important;
                height: 20px !important;
                font-size: 8pt !important;
            }

            .section-description {
                margin-bottom: 7px !important;
                margin-left: 0 !important;
                font-size: 7.5pt !important;
            }

            .field-label {
                margin-bottom: 3px !important;
                font-size: 7.5pt !important;
            }

            .input,
            .select {
                height: 28px !important;
                padding: 0 7px !important;
                font-size: 8pt !important;
            }

            textarea {
                padding: 7px !important;
                font-size: 8pt !important;
            }

            .info-grid,
            .info-grid-bottom {
                gap: 7px !important;
            }

            .auto-ka-textarea {
                min-height: 60px !important;
                height: auto !important;
            }

            .note-area {
                min-height: 55px !important;
            }

            .large-textarea {
                height: 75px !important;
            }

            .security-list {
                border-radius: 4px !important;
            }

            .security-row {
                min-height: 32px !important;
                padding: 5px 7px !important;
                font-size: 7.5pt !important;
                gap: 8px !important;
            }

            .toggle {
                min-width: 65px !important;
                height: 23px !important;
                padding: 0 6px !important;
                font-size: 7pt !important;
            }

            .inventory-grid {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 8px !important;
            }

            .inventory-column {
                display: flex !important;
                overflow: visible !important;
                gap: 4px !important;
            }

            .inventory-row {
                width: 100% !important;
                min-width: 0 !important;
                min-height: 30px !important;
                padding: 3px 4px !important;
                grid-template-columns: 16px minmax(0, 1fr) 35px 65px 38px !important;
                gap: 3px !important;
                font-size: 6.5pt !important;
            }

            .qty,
            .condition,
            .small-action {
                height: 21px !important;
                font-size: 6.5pt !important;
            }

            .signature-grid {
                grid-template-columns: 1fr 1fr !important;
                gap: 10px !important;
            }

            .signature-card {
                padding: 8px !important;
                border-radius: 5px !important;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .signature-title {
                font-size: 8pt !important;
            }

            .signature-subtitle {
                font-size: 6.5pt !important;
            }

            .signature-area {
                height: 90px !important;
                margin-top: 5px !important;
            }

            .signature-input {
                margin-top: 5px !important;
            }

            .signature-actions {
                display: none !important;
            }

            .signature-placeholder {
                font-size: 7pt !important;
            }

            input,
            select,
            textarea {
                color: #111 !important;
                background: #fff !important;
            }

            .page-card,
            .info-card,
            .section,
            .signature-card,
            .security-list {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            /* Jika satu section terlalu panjang, izinkan isi mengalir
               agar tidak menghasilkan potongan horizontal. */
            .section.inventory-section {
                break-inside: auto;
                page-break-inside: auto;
            }

            .inventory-row {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }

    
        /* =====================================================
           KA HASIL — TIGA KOLOM / BERDAMPINGAN
        ===================================================== */

        .ka-section {
            min-width: 0;
        }

        /* Bungkus visual otomatis: tiga section KA berdampingan.
           Karena ketiga section berurutan, gunakan display: contents
           pada container induknya melalui selector :has(). */
        .page-card {
            --ka-gap: 16px;
        }

        .page-card > .ka-section {
            display: inline-flex;
            vertical-align: top;
            width: calc((100% - (var(--ka-gap) * 2)) / 3);
            margin-right: var(--ka-gap);
            flex-direction: column;
        }

        .page-card > .ka-section:nth-of-type(3) {
            margin-right: 0;
        }

        .ka-section .section-title {
            min-height: 34px;
            align-items: center;
        }

        .ka-section .section-description {
            min-height: 34px;
            margin-left: 0;
        }

        .ka-section .auto-ka-textarea {
            height: 145px;
            min-height: 145px;
            overflow-y: auto;
            line-height: 1.65;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .ka-section .auto-ka-info {
            margin-top: 9px;
        }

        @media (max-width: 1050px) {
            .page-card > .ka-section {
                width: calc((100% - var(--ka-gap)) / 2);
            }

            .page-card > .ka-section:nth-of-type(3) {
                margin-right: var(--ka-gap);
            }

            .page-card > .ka-section:nth-of-type(2n) {
                margin-right: 0;
            }
        }

        @media (max-width: 700px) {
            .page-card > .ka-section {
                width: 100%;
                margin-right: 0 !important;
            }

            .ka-section .auto-ka-textarea {
                height: 120px;
                min-height: 120px;
            }
        }

        /* Saat dicetak, tiga hasil KA juga tetap berdampingan. */
        @media print {
            .page-card > .ka-section {
                display: inline-flex !important;
                vertical-align: top !important;
                width: calc((100% - 14px) / 3) !important;
                margin-right: 7px !important;
                margin-bottom: 9px !important;
                padding: 7px !important;
            }

            .page-card > .ka-section:nth-of-type(3) {
                margin-right: 0 !important;
            }

            .ka-section .section-title {
                min-height: 26px !important;
                font-size: 8pt !important;
            }

            .ka-section .section-description {
                min-height: 28px !important;
                margin-left: 0 !important;
                font-size: 6.5pt !important;
            }

            .ka-section .auto-ka-textarea {
                height: 85px !important;
                min-height: 85px !important;
                overflow: hidden !important;
                font-size: 7pt !important;
                line-height: 1.45 !important;
            }

            .ka-section .auto-ka-info {
                font-size: 6pt !important;
            }
        }

    
        /* =====================================================
           HASIL KA — NOMOR KE SAMPING, DIPISAH KOMA
        ===================================================== */
        .page-card > .ka-section-full {
            display: block !important;
            width: 100% !important;
            margin-right: 0 !important;
        }

        .ka-section-full .section-description {
            margin-left: 0;
        }

        .auto-ka-result {
            width: 100%;
            min-height: 72px;
            padding: 14px 16px;
            border: 1px solid #d9e2ec;
            border-radius: 8px;
            background: #f7f9fc;
            color: #26344d;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.8;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .auto-ka-result.auto-ka-empty {
            color: #8994a3;
            font-weight: 400;
        }

        @media print {
            .page-card > .ka-section-full {
                display: block !important;
                width: 100% !important;
                margin-right: 0 !important;
                margin-bottom: 8px !important;
            }

            .auto-ka-result {
                min-height: 0 !important;
                padding: 7px 9px !important;
                font-size: 8pt !important;
                line-height: 1.55 !important;
                background: #fff !important;
                border: 1px solid #cfd6df !important;
                overflow: visible !important;
            }
        }

    
        /* =====================================================
           PRINT — TAMPILKAN HASIL SAJA, TANPA DROPDOWN
        ===================================================== */
        @media print {
            /* Semua select dicetak sebagai teks/value saja.
               Tidak ada kotak dropdown maupun tanda panah. */
            select,
            .select,
            .condition {
                appearance: none !important;
                -webkit-appearance: none !important;
                -moz-appearance: none !important;
                background-image: none !important;
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
                outline: none !important;
                height: auto !important;
                min-height: 0 !important;
                padding: 2px 0 !important;
                margin: 0 !important;
                color: #111 !important;
                font-size: 8pt !important;
                font-weight: 500 !important;
            }

            /* Hilangkan ikon kalender dan jam pada input date/time. */
            input[type="date"]::-webkit-calendar-picker-indicator,
            input[type="time"]::-webkit-calendar-picker-indicator {
                display: none !important;
                -webkit-appearance: none !important;
            }

            input[type="date"],
            input[type="time"] {
                appearance: none !important;
                -webkit-appearance: none !important;
                border: none !important;
                background: transparent !important;
                box-shadow: none !important;
                height: auto !important;
                padding: 2px 0 !important;
            }

            /* Input biasa juga dicetak sebagai nilai bersih. */
            .info-card .input,
            .info-card .select {
                border: none !important;
                background: transparent !important;
                box-shadow: none !important;
            }

            /* Untuk kolom kondisi inventaris: tampilkan nilai terpilih
               saja, tanpa bentuk dropdown. */
            .inventory-row .condition {
                width: 100% !important;
                text-align: center !important;
                border: none !important;
                background: transparent !important;
            }
        }

    </style>
</head>

<body>

    <!-- =====================================================
         HEADER
    ===================================================== -->

    <header class="top-header">

        <div class="header-left">

            <button
                type="button"
                class="menu-button"
                id="menuButton"
                aria-label="Buka menu"
                aria-expanded="false"
                aria-controls="mobileMenu"
                onclick="toggleMobileMenu()"
            >
                ☰
            </button>

            <img
                src="{{ asset('images/logo-kai.jpg') }}"
                alt="Logo KAI"
                class="kai-logo"
            >

            <div class="header-brand">

                <div class="brand-title">
                    KAI OPERASIONAL
                </div>

                <div class="brand-subtitle">
                    SISTEM DIGITAL PJL
                </div>

            </div>

        </div>

        <div class="header-right">

            <div class="user-circle">
                S
            </div>

        </div>

    </header>


    <!-- =====================================================
         OVERLAY
    ===================================================== -->

    <div
        class="menu-overlay"
        id="menuOverlay"
        onclick="closeMobileMenu()"
    ></div>


    <!-- =====================================================
         SIDE MENU
    ===================================================== -->

    <aside
        class="mobile-menu"
        id="mobileMenu"
        aria-label="Menu navigasi"
    >

        <div class="menu-brand">

            <div class="menu-brand-title">
                KAI OPERASIONAL
            </div>

            <div class="menu-brand-subtitle">
                SISTEM DIGITAL PJL
            </div>

        </div>


        <nav class="menu-list">

            <a
                href="/user/dashboard"
                class="menu-link"
                onclick="closeMobileMenu()"
            >
                <span class="menu-icon">⌂</span>
                <span>Dashboard</span>
            </a>

            <a
                href="/user/realisasi-ka"
                class="menu-link"
                onclick="closeMobileMenu()"
            >
                <span class="menu-icon">▣</span>
                <span>Jadwal KA</span>
            </a>

            <a
                href="/user/serah-terima-dinas"
                class="menu-link active"
                onclick="closeMobileMenu()"
            >
                <span class="menu-icon">⇄</span>
                <span>Serah Terima Dinas</span>
            </a>

            <a
                href="/user/catatan-dinas"
                class="menu-link"
                onclick="closeMobileMenu()"
            >
                <span class="menu-icon">▣</span>
                <span>Catatan Dinas</span>
            </a>

        </nav>


        <div class="menu-bottom">

            <div class="active-user-card">

                <div class="active-user-label">
                    PJL AKTIF
                </div>

                <div class="active-user-name">
                    Budi Santoso
                </div>

                <div class="active-user-info">
                    NIP: 199208242015031002
                    <br>
                    Shift B&nbsp;&nbsp;•&nbsp;&nbsp;08:00–16:00
                </div>

            </div>


            <a
                href="/login"
                class="logout-link"
            >
                <span class="menu-icon">↪</span>
                <span>Keluar</span>
            </a>

        </div>

    </aside>


    <!-- =====================================================
         MAIN
    ===================================================== -->

    <main class="main">

        <div class="page-card">

            <!-- HEADER DOKUMEN -->

            <header class="document-header">

                <div class="company">
                    PT KERETA API INDONESIA (PERSERO)
                </div>

                <div class="department">
                    DIREKTORAT KESELAMATAN DAN KEAMANAN
                </div>

                <h1 class="page-title">
                    Serah Terima Dinas PJL
                </h1>

                <div class="page-subtitle">
                    Dokumen kegiatan serah terima dinas PJL sebagai bukti kondisi operasional saat pergantian dinas.
                </div>

            </header>


            <!-- INFORMASI DINAS -->

            <div class="info-card">

                <div class="info-grid">

                    <div>

                        <div class="field-label">
                            HARI
                        </div>

                        <select
                            class="select"
                            id="hari"
                        >
                            <option>Senin</option>
                            <option>Selasa</option>
                            <option>Rabu</option>
                            <option>Kamis</option>
                            <option>Jumat</option>
                            <option>Sabtu</option>
                            <option>Minggu</option>
                        </select>

                    </div>


                    <div>

                        <div class="field-label">
                            TANGGAL
                        </div>

                        <input
                            type="date"
                            class="input"
                            id="tanggal"
                            value="2024-09-30"
                        >

                    </div>


                    <div>

                        <div class="field-label">
                            JAM SERAH TERIMA
                        </div>

                        <input
                            type="time"
                            class="input"
                            id="jamSerahTerima"
                            value="16:00"
                        >

                    </div>

                </div>


                <div class="info-grid-bottom">

                    <div>

                        <div class="field-label">
                            PJL MENYERAHKAN / DINAS SEBELUMNYA
                        </div>

                        <input
                            type="text"
                            class="input"
                            id="pjlMenyerahkan"
                            value="Pagi"
                        >

                    </div>


                    <div>

                        <div class="field-label">
                            PJL MENERIMA / DINAS BERIKUTNYA
                        </div>

                        <input
                            type="text"
                            class="input"
                            id="pjlMenerima"
                            value="Siang"
                        >

                    </div>

                </div>

            </div>


            <!-- 1. KA BATAL -->

            <section class="section ka-section-full">

                <div class="section-title">

                    <span class="section-number">
                        1
                    </span>

                    KA-KA yang batal

                </div>

                <div class="section-description">
                    Daftar nomor KA diambil otomatis dari halaman Realisasi KA dengan status <strong>BATAL</strong>.
                </div>

                <div
                    class="auto-ka-result"
                    id="kaBatal"
                    aria-label="Daftar KA yang batal"
                ></div>

                <div class="auto-ka-info">
                    <span class="auto-ka-dot"></span>
                    Data otomatis dari Realisasi KA. Tidak perlu mengetik manual.
                </div>

            </section>


            <!-- 2. KA BERJALAN -->

            <section class="section ka-section-full">

                <div class="section-title">

                    <span class="section-number">
                        2
                    </span>

                    KA-KA yang berjalan / informasi perjalanan

                </div>

                <div class="section-description">
                    Daftar nomor KA diambil otomatis dari halaman Realisasi KA dengan status <strong>JALAN</strong>.
                </div>

                <div
                    class="auto-ka-result"
                    id="kaBerjalan"
                    aria-label="Daftar KA yang berjalan"
                ></div>

                <div class="auto-ka-info">
                    <span class="auto-ka-dot"></span>
                    Data otomatis dari Realisasi KA. Tidak perlu mengetik manual.
                </div>

            </section>


            <!-- 3. KA BELUM LEWAT -->

            <section class="section ka-section-full">

                <div class="section-title">

                    <span class="section-number">
                        3
                    </span>

                    KA-KA yang belum lewat

                </div>

                <div class="section-description">
                    Daftar nomor KA diambil otomatis dari halaman Realisasi KA dengan status <strong>BELUM</strong>.
                </div>

                <div
                    class="auto-ka-result"
                    id="kaBelumLewat"
                    aria-label="Daftar KA yang belum lewat"
                ></div>

                <div class="auto-ka-info">
                    <span class="auto-ka-dot"></span>
                    Data otomatis dari Realisasi KA. Tidak perlu mengetik manual.
                </div>

            </section>


            <!-- 4. ALAT PENGAMANAN -->

            <section class="section">

                <div class="section-title">

                    <span class="section-number">
                        4
                    </span>

                    Alat pengamanan

                </div>

                <div class="security-list">

                    <div class="security-row">

                        <span>
                            Perangkat pengamanan
                        </span>

                        <div class="toggle-group">

                            <button
                                type="button"
                                class="toggle active"
                                onclick="toggleSecurity(this)"
                            >
                                ✓ Normal
                            </button>

                            <button
                                type="button"
                                class="toggle"
                                onclick="toggleSecurity(this)"
                            >
                                Tidak Normal
                            </button>

                        </div>

                    </div>


                    <div class="security-row">

                        <span>
                            Perangkat komunikasi
                        </span>

                        <div class="toggle-group">

                            <button
                                type="button"
                                class="toggle active"
                                onclick="toggleSecurity(this)"
                            >
                                ✓ Normal
                            </button>

                            <button
                                type="button"
                                class="toggle"
                                onclick="toggleSecurity(this)"
                            >
                                Tidak Normal
                            </button>

                        </div>

                    </div>

                </div>

            </section>


            <!-- 5. INVENTARIS -->

            <section class="section">

                <div class="section-title">

                    <span class="section-number">
                        5
                    </span>

                    Alat perlengkapan dan inventaris PJL

                </div>


                <div class="inventory-grid">

                    <div class="inventory-column">

                        <div class="inventory-row">
                            <span>1.</span>
                            <span class="inventory-name">Palu perlintasan</span>
                            <input class="qty" type="number" value="2" min="0">
                            <select class="condition">
                                <option>Lengkap</option>
                                <option>Tidak Lengkap</option>
                            </select>
                            <select class="condition">
                                <option value="baik" selected>Baik</option>
                                <option value="tidak_baik">Tidak Baik</option>
                            </select>
                        </div>

                        <div class="inventory-row">
                            <span>2.</span>
                            <span class="inventory-name">Bendera merah KA</span>
                            <input class="qty" type="number" value="1" min="0">
                            <select class="condition">
                                <option>Lengkap</option>
                                <option>Tidak Lengkap</option>
                            </select>
                            <select class="condition">
                                <option value="baik" selected>Baik</option>
                                <option value="tidak_baik">Tidak Baik</option>
                            </select>
                        </div>

                        <div class="inventory-row">
                            <span>3.</span>
                            <span class="inventory-name">Bendera hijau</span>
                            <input class="qty" type="number" value="1" min="0">
                            <select class="condition">
                                <option>Lengkap</option>
                                <option>Tidak Lengkap</option>
                            </select>
                            <select class="condition">
                                <option value="baik" selected>Baik</option>
                                <option value="tidak_baik">Tidak Baik</option>
                            </select>
                        </div>

                        <div class="inventory-row">
                            <span>4.</span>
                            <span class="inventory-name">Senter</span>
                            <input class="qty" type="number" value="1" min="0">
                            <select class="condition">
                                <option>Lengkap</option>
                                <option>Tidak Lengkap</option>
                            </select>
                            <select class="condition">
                                <option value="baik" selected>Baik</option>
                                <option value="tidak_baik">Tidak Baik</option>
                            </select>
                        </div>

                        <div class="inventory-row">
                            <span>5.</span>
                            <span class="inventory-name">Buku catatan</span>
                            <input class="qty" type="number" value="1" min="0">
                            <select class="condition">
                                <option>Lengkap</option>
                                <option>Tidak Lengkap</option>
                            </select>
                            <select class="condition">
                                <option value="baik" selected>Baik</option>
                                <option value="tidak_baik">Tidak Baik</option>
                            </select>
                        </div>

                        <div class="inventory-row">
                            <span>6.</span>
                            <span class="inventory-name">Lainnya</span>
                            <input class="qty" type="number" value="2" min="0">
                            <select class="condition">
                                <option>Lengkap</option>
                                <option>Tidak Lengkap</option>
                            </select>
                            <select class="condition">
                                <option value="baik" selected>Baik</option>
                                <option value="tidak_baik">Tidak Baik</option>
                            </select>
                        </div>

                        <div class="inventory-row">
                            <span>7.</span>
                            <span class="inventory-name">Lampu senter</span>
                            <input class="qty" type="number" value="1" min="0">
                            <select class="condition">
                                <option>Lengkap</option>
                                <option>Tidak Lengkap</option>
                            </select>
                            <select class="condition">
                                <option value="baik" selected>Baik</option>
                                <option value="tidak_baik">Tidak Baik</option>
                            </select>
                        </div>

                        <div class="inventory-row">
                            <span>8.</span>
                            <span class="inventory-name">HT</span>
                            <input class="qty" type="number" value="1" min="0">
                            <select class="condition">
                                <option>Lengkap</option>
                                <option>Tidak Lengkap</option>
                            </select>
                            <select class="condition">
                                <option value="baik" selected>Baik</option>
                                <option value="tidak_baik">Tidak Baik</option>
                            </select>
                        </div>

                        <div class="inventory-row">
                            <span>9.</span>
                            <span class="inventory-name">Seragam sesuai ketentuan</span>
                            <input class="qty" type="number" value="1" min="0">
                            <select class="condition">
                                <option>Lengkap</option>
                                <option>Tidak Lengkap</option>
                            </select>
                            <select class="condition">
                                <option value="baik" selected>Baik</option>
                                <option value="tidak_baik">Tidak Baik</option>
                            </select>
                        </div>

                    </div>


                    <div class="inventory-column">

                        <div class="inventory-row">
                            <span>1.</span>
                            <span class="inventory-name">Baterai cadangan komunikasi</span>
                            <input class="qty" type="number" value="1" min="0">
                            <select class="condition">
                                <option>Lengkap</option>
                                <option>Tidak Lengkap</option>
                            </select>
                            <select class="condition">
                                <option value="baik" selected>Baik</option>
                                <option value="tidak_baik">Tidak Baik</option>
                            </select>
                        </div>

                        <div class="inventory-row">
                            <span>2.</span>
                            <span class="inventory-name">Baterai kuning komunikasi</span>
                            <input class="qty" type="number" value="1" min="0">
                            <select class="condition">
                                <option>Lengkap</option>
                                <option>Tidak Lengkap</option>
                            </select>
                            <select class="condition">
                                <option value="baik" selected>Baik</option>
                                <option value="tidak_baik">Tidak Baik</option>
                            </select>
                        </div>

                        <div class="inventory-row">
                            <span>3.</span>
                            <span class="inventory-name">Radio HT</span>
                            <input class="qty" type="number" value="1" min="0">
                            <select class="condition">
                                <option>Lengkap</option>
                                <option>Tidak Lengkap</option>
                            </select>
                            <select class="condition">
                                <option value="baik" selected>Baik</option>
                                <option value="tidak_baik">Tidak Baik</option>
                            </select>
                        </div>

                        <div class="inventory-row">
                            <span>4.</span>
                            <span class="inventory-name">Peluit</span>
                            <input class="qty" type="number" value="1" min="0">
                            <select class="condition">
                                <option>Lengkap</option>
                                <option>Tidak Lengkap</option>
                            </select>
                            <select class="condition">
                                <option value="baik" selected>Baik</option>
                                <option value="tidak_baik">Tidak Baik</option>
                            </select>
                        </div>

                        <div class="inventory-row">
                            <span>5.</span>
                            <span class="inventory-name">P3K</span>
                            <input class="qty" type="number" value="1" min="0">
                            <select class="condition">
                                <option>Lengkap</option>
                                <option>Tidak Lengkap</option>
                            </select>
                            <select class="condition">
                                <option value="baik" selected>Baik</option>
                                <option value="tidak_baik">Tidak Baik</option>
                            </select>
                        </div>

                        <div class="inventory-row">
                            <span>6.</span>
                            <span class="inventory-name">Jam dinding</span>
                            <input class="qty" type="number" value="1" min="0">
                            <select class="condition">
                                <option>Lengkap</option>
                                <option>Tidak Lengkap</option>
                            </select>
                            <select class="condition">
                                <option value="baik" selected>Baik</option>
                                <option value="tidak_baik">Tidak Baik</option>
                            </select>
                        </div>

                        <div class="inventory-row">
                            <span>7.</span>
                            <span class="inventory-name">APAR/P3K</span>
                            <input class="qty" type="number" value="1" min="0">
                            <select class="condition">
                                <option>Lengkap</option>
                                <option>Tidak Lengkap</option>
                            </select>
                            <select class="condition">
                                <option value="baik" selected>Baik</option>
                                <option value="tidak_baik">Tidak Baik</option>
                            </select>
                        </div>

                        <div class="inventory-row">
                            <span>8.</span>
                            <span class="inventory-name">APAR</span>
                            <input class="qty" type="number" value="1" min="0">
                            <select class="condition">
                                <option>Lengkap</option>
                                <option>Tidak Lengkap</option>
                            </select>
                            <select class="condition">
                                <option value="baik" selected>Baik</option>
                                <option value="tidak_baik">Tidak Baik</option>
                            </select>
                        </div>

                        <div class="inventory-row">
                            <span>9.</span>
                            <span class="inventory-name">Lain-lain</span>
                            <input class="qty" type="number" value="1" min="0">
                            <select class="condition">
                                <option>Lengkap</option>
                                <option>Tidak Lengkap</option>
                            </select>
                            <select class="condition">
                                <option value="baik" selected>Baik</option>
                                <option value="tidak_baik">Tidak Baik</option>
                            </select>
                        </div>

                    </div>

                </div>

            </section>


            <!-- 6. CATATAN -->

            <section class="section">

                <div class="section-title">

                    <span class="section-number">
                        6
                    </span>

                    Catatan penting dari dinas

                </div>

                <textarea
                    class="large-textarea"
                    id="catatanPenting"
                    placeholder="Tuliskan kondisi perlintasan, gangguan peralatan, informasi pekerjaan, atau catatan penting lainnya..."
                ></textarea>

            </section>


            <!-- 7. PPKA -->

            <section class="section">

                <div class="section-title">

                    <span class="section-number">
                        7
                    </span>

                    PPKA yang terlibat

                </div>

                <input
                    type="text"
                    class="input"
                    id="ppka"
                    placeholder="Masukkan nama PPKA"
                >

            </section>


            <!-- 8. TANDA TANGAN -->

            <section class="section">

                <div class="section-title">

                    <span class="section-number">
                        8
                    </span>

                    Tanda tangan

                </div>


                <div class="signature-grid">


                    <!-- PJL MENYERAHKAN -->

                    <div class="signature-card">

                        <div class="signature-title">
                            PJL yang menyerahkan / meninggalkan dinas
                        </div>

                        <div class="signature-subtitle">
                            Silakan lengkapi data dan tanda tangan.
                        </div>


                        <div class="signature-input">

                            <div class="field-label">
                                NIP
                            </div>

                            <input
                                type="text"
                                class="input"
                                value="199208242015031002"
                            >

                        </div>


                        <div
                            class="field-label"
                            style="margin-top:8px;"
                        >
                            Tanda tangan
                        </div>


                        <div class="signature-area">

                            <canvas class="signature-canvas"></canvas>

                            <img
                                class="signature-result"
                                alt="Hasil tanda tangan PJL menyerahkan"
                            >

                            <span class="signature-placeholder">
                                Klik di sini untuk tanda tangan
                            </span>

                        </div>


                        <div class="signature-actions">

                            <button
                                type="button"
                                class="signature-save"
                                onclick="saveSignature(this)"
                            >
                                Simpan
                            </button>

                            <button
                                type="button"
                                class="signature-clear"
                                onclick="clearSignature(this)"
                            >
                                Hapus
                            </button>

                            <button
                                type="button"
                                class="signature-download"
                                onclick="downloadSignature(this)"
                                style="display:none;"
                            >
                                Download PNG
                            </button>

                        </div>


                        <div class="signature-name">
                            Nama: Budi Santoso
                            <br>
                            NIP: 199208242015031002
                        </div>

                    </div>


                    <!-- PJL MENERIMA -->

                    <div class="signature-card">

                        <div class="signature-title">
                            PJL yang menerima / menggantikan dinas
                        </div>

                        <div class="signature-subtitle">
                            Pastikan data PJL berikutnya sudah benar.
                        </div>


                        <div class="signature-input">

                            <div class="field-label">
                                Nama PJL Penerima
                            </div>

                            <input
                                type="text"
                                class="input pjl-penerima-nama"
                                id="namaPjlPenerima"
                                placeholder="Masukkan nama PJL penerima"
                                autocomplete="off"
                            >

                            <div
                                class="field-label"
                                style="margin-top:8px;"
                            >
                                NIP
                            </div>

                            <input
                                type="text"
                                class="input pjl-penerima-nip"
                                id="nipPjlPenerima"
                                placeholder="Masukkan NIP PJL penerima"
                                inputmode="numeric"
                                autocomplete="off"
                            >

                        </div>


                        <div
                            class="field-label"
                            style="margin-top:8px;"
                        >
                            Tanda tangan
                        </div>


                        <div class="signature-area">

                            <canvas class="signature-canvas"></canvas>

                            <img
                                class="signature-result"
                                alt="Hasil tanda tangan PJL menerima"
                            >

                            <span class="signature-placeholder">
                                Klik di sini untuk tanda tangan
                            </span>

                        </div>


                        <div class="signature-actions">

                            <button
                                type="button"
                                class="signature-save"
                                onclick="saveSignature(this)"
                            >
                                Simpan
                            </button>

                            <button
                                type="button"
                                class="signature-clear"
                                onclick="clearSignature(this)"
                            >
                                Hapus
                            </button>

                            <button
                                type="button"
                                class="signature-download"
                                onclick="downloadSignature(this)"
                                style="display:none;"
                            >
                                Download PNG
                            </button>

                        </div>


                        <div class="signature-name" id="dataPjlPenerima">
                            Nama: -
                            <br>
                            NIP: -
                        </div>

                    </div>

                </div>

            </section>


            <!-- BUTTON -->

            <div class="bottom">

                <button
                    type="button"
                    class="button button-outline"
                    onclick="window.print()"
                >
                    🖨 Cetak
                </button>

                <button
                    type="button"
                    class="button button-primary"
                    onclick="saveDocument()"
                >
                    ▣ Simpan
                </button>

            </div>


            <!-- INFO -->

            <div class="info-alert">

                <span class="alert-icon">
                    i
                </span>

                <span>
                    Data KA pada dokumen ini mengikuti status terbaru dari halaman Realisasi KA. Pastikan data Realisasi KA sudah diperbarui sebelum mencetak atau menyimpan serah terima dinas.
                </span>

            </div>

        </div>

    </main>


    <script>

        /* =====================================================
           STORAGE REALISASI KA
        ===================================================== */

        const REALISASI_KA_STORAGE_KEY = "realisasi_ka_pjl";


        /* =====================================================
           AMBIL DATA REALISASI KA
        ===================================================== */

        function getDataRealisasiKA() {

            try {

                const savedData =
                    localStorage.getItem(
                        REALISASI_KA_STORAGE_KEY
                    );

                if (!savedData) {
                    return [];
                }

                const data =
                    JSON.parse(savedData);

                if (!Array.isArray(data)) {
                    return [];
                }

                return data;

            } catch (error) {

                console.error(
                    "Gagal membaca data Realisasi KA:",
                    error
                );

                return [];
            }
        }


        /* =====================================================
           AMBIL KA BERDASARKAN STATUS
        ===================================================== */

        function getKAByStatus(status) {

            const dataKA =
                getDataRealisasiKA();

            return dataKA.filter(function(ka) {

                return String(
                    ka.status || ""
                ).toUpperCase() === status;

            });
        }


        /* =====================================================
           FORMAT NOMOR KA
        ===================================================== */

        function formatNomorKA(ka) {

            let nomor =
                String(
                    ka?.nomor ?? ""
                ).trim();


            if (
                nomor === "" ||
                nomor === "-"
            ) {
                return "";
            }


            if (
                /^KA\s*/i.test(nomor)
            ) {
                return nomor;
            }


            return "KA " + nomor;
        }


        /* =====================================================
           RENDER DATA KA
        ===================================================== */

        function renderDataKA() {

            const kaBatal = getKAByStatus("BATAL");
            const kaBerjalan = getKAByStatus("JALAN");
            const kaBelumLewat = getKAByStatus("BELUM");

            const resultBatal = document.getElementById("kaBatal");
            const resultBerjalan = document.getElementById("kaBerjalan");
            const resultBelumLewat = document.getElementById("kaBelumLewat");

            const renderResult = (element, data, emptyText) => {
                if (!element) return;

                const nomorKA = data.map(formatNomorKA).filter(Boolean);

                if (nomorKA.length > 0) {
                    element.textContent = nomorKA.join(", ");
                    element.classList.remove("auto-ka-empty");
                } else {
                    element.textContent = emptyText;
                    element.classList.add("auto-ka-empty");
                }
            };

            renderResult(
                resultBatal,
                kaBatal,
                "Tidak ada KA yang batal."
            );

            renderResult(
                resultBerjalan,
                kaBerjalan,
                "Tidak ada KA yang sedang berjalan."
            );

            renderResult(
                resultBelumLewat,
                kaBelumLewat,
                "Tidak ada KA yang belum lewat."
            );
        }


        /* =====================================================
           MENU MOBILE
        ===================================================== */

        function toggleMobileMenu() {

            const menu =
                document.getElementById("mobileMenu");

            const overlay =
                document.getElementById("menuOverlay");

            const button =
                document.getElementById("menuButton");

            if (!menu || !overlay || !button) {
                return;
            }

            const isOpen =
                menu.classList.contains("show");

            if (isOpen) {

                closeMobileMenu();

                return;
            }

            menu.classList.add("show");

            overlay.classList.add("show");

            document.body.classList.add(
                "menu-open"
            );

            button.setAttribute(
                "aria-expanded",
                "true"
            );
        }


        function closeMobileMenu() {

            const menu =
                document.getElementById("mobileMenu");

            const overlay =
                document.getElementById("menuOverlay");

            const button =
                document.getElementById("menuButton");

            if (!menu || !overlay || !button) {
                return;
            }

            menu.classList.remove("show");

            overlay.classList.remove("show");

            document.body.classList.remove(
                "menu-open"
            );

            button.setAttribute(
                "aria-expanded",
                "false"
            );
        }


        document.addEventListener(
            "keydown",
            function(event) {

                if (event.key === "Escape") {
                    closeMobileMenu();
                }

            }
        );


        /* =====================================================
           SECURITY
        ===================================================== */

        function toggleSecurity(button) {

            const parent =
                button.parentElement;

            if (!parent) {
                return;
            }

            const buttons =
                parent.querySelectorAll(".toggle");

            buttons.forEach(
                function(btn) {

                    btn.classList.remove("active");

                    btn.textContent =
                        btn.textContent.replace(
                            /^✓\s*/,
                            ""
                        );

                }
            );

            button.classList.add("active");

            button.textContent =
                "✓ " +
                button.textContent.trim();
        }


        /* =====================================================
           INISIALISASI CANVAS
        ===================================================== */

        document
            .querySelectorAll(".signature-canvas")
            .forEach(function(canvas) {

                const ctx =
                    canvas.getContext("2d");

                const signatureArea =
                    canvas.closest(".signature-area");

                if (!signatureArea) {
                    return;
                }

                let drawing = false;


                function setupCanvas() {

                    ctx.lineWidth = 2;

                    ctx.lineCap = "round";

                    ctx.lineJoin = "round";

                    ctx.strokeStyle = "#1e3156";
                }


                signatureArea.addEventListener(
                    "click",
                    function() {

                        if (
                            signatureArea.classList.contains(
                                "show-result"
                            )
                        ) {
                            return;
                        }

                        if (
                            !signatureArea.classList.contains(
                                "active"
                            )
                        ) {

                            signatureArea.classList.add(
                                "active"
                            );

                            const placeholder =
                                signatureArea.querySelector(
                                    ".signature-placeholder"
                                );

                            if (placeholder) {

                                placeholder.style.display =
                                    "none";
                            }
                        }
                    }
                );


                function resizeCanvas() {

                    const rect =
                        canvas.getBoundingClientRect();

                    if (
                        rect.width === 0 ||
                        rect.height === 0
                    ) {
                        return;
                    }

                    let oldCanvas = null;

                    if (
                        canvas.width > 0 &&
                        canvas.height > 0
                    ) {

                        oldCanvas =
                            document.createElement(
                                "canvas"
                            );

                        oldCanvas.width =
                            canvas.width;

                        oldCanvas.height =
                            canvas.height;

                        const oldCtx =
                            oldCanvas.getContext(
                                "2d"
                            );

                        oldCtx.drawImage(
                            canvas,
                            0,
                            0
                        );
                    }

                    canvas.width =
                        rect.width;

                    canvas.height =
                        rect.height;

                    setupCanvas();

                    if (oldCanvas) {

                        ctx.drawImage(
                            oldCanvas,
                            0,
                            0,
                            oldCanvas.width,
                            oldCanvas.height,
                            0,
                            0,
                            canvas.width,
                            canvas.height
                        );
                    }
                }


                resizeCanvas();

                window.addEventListener(
                    "resize",
                    resizeCanvas
                );


                function getPosition(event) {

                    const rect =
                        canvas.getBoundingClientRect();

                    return {

                        x:
                            event.clientX -
                            rect.left,

                        y:
                            event.clientY -
                            rect.top

                    };
                }


                canvas.addEventListener(
                    "pointerdown",
                    function(event) {

                        if (
                            !signatureArea.classList.contains(
                                "active"
                            )
                        ) {
                            return;
                        }

                        drawing = true;

                        canvas.setPointerCapture(
                            event.pointerId
                        );

                        const position =
                            getPosition(event);

                        ctx.beginPath();

                        ctx.moveTo(
                            position.x,
                            position.y
                        );
                    }
                );


                canvas.addEventListener(
                    "pointermove",
                    function(event) {

                        if (!drawing) {
                            return;
                        }

                        const position =
                            getPosition(event);

                        ctx.lineTo(
                            position.x,
                            position.y
                        );

                        ctx.stroke();
                    }
                );


                function stopDrawing(event) {

                    drawing = false;

                    if (
                        event &&
                        canvas.hasPointerCapture(
                            event.pointerId
                        )
                    ) {

                        canvas.releasePointerCapture(
                            event.pointerId
                        );
                    }
                }


                canvas.addEventListener(
                    "pointerup",
                    stopDrawing
                );

                canvas.addEventListener(
                    "pointercancel",
                    stopDrawing
                );

            });


        /* =====================================================
           CEK TANDA TANGAN
        ===================================================== */

        function hasSignature(canvas) {

            if (!canvas) {
                return false;
            }

            const ctx =
                canvas.getContext("2d");

            const pixels =
                ctx.getImageData(
                    0,
                    0,
                    canvas.width,
                    canvas.height
                ).data;

            for (
                let i = 3;
                i < pixels.length;
                i += 4
            ) {

                if (pixels[i] > 0) {
                    return true;
                }
            }

            return false;
        }


        /* =====================================================
           BUAT NAMA FILE PNG
        ===================================================== */

        function getSignatureFileName(card) {

            const nameElement =
                card.querySelector(".signature-name");

            let name = "tanda-tangan";

            if (nameElement) {

                const text =
                    nameElement.innerText || "";

                const match =
                    text.match(
                        /Nama:\s*(.+)/i
                    );

                if (
                    match &&
                    match[1]
                ) {

                    name =
                        match[1].trim();

                }
            }

            name =
                name
                    .replace(
                        /[^a-zA-Z0-9\s-]/g,
                        ""
                    )
                    .trim()
                    .replace(
                        /\s+/g,
                        "-"
                    )
                    .toLowerCase();

            if (!name) {
                name = "tanda-tangan";
            }

            return "tanda-tangan-" + name + ".png";
        }


        /* =====================================================
           SIMPAN TANDA TANGAN
        ===================================================== */

        function saveSignature(button) {

            const card =
                button.closest(".signature-card");

            if (!card) {
                return;
            }

            const canvas =
                card.querySelector(
                    ".signature-canvas"
                );

            const signatureArea =
                card.querySelector(
                    ".signature-area"
                );

            const signatureResult =
                card.querySelector(
                    ".signature-result"
                );

            const placeholder =
                card.querySelector(
                    ".signature-placeholder"
                );

            const downloadButton =
                card.querySelector(
                    ".signature-download"
                );

            if (
                !canvas ||
                !signatureArea ||
                !signatureResult
            ) {
                return;
            }


            /* CEK TANDA TANGAN */

            if (!hasSignature(canvas)) {

                alert(
                    "Silakan buat tanda tangan terlebih dahulu."
                );

                return;
            }


            /* BUAT PNG */

            const signatureImage =
                canvas.toDataURL(
                    "image/png"
                );


            /* TAMPILKAN HASIL */

            signatureResult.src =
                signatureImage;

            signatureResult.dataset.signature =
                signatureImage;


            /* UBAH TAMPILAN */

            signatureArea.classList.remove(
                "active"
            );

            signatureArea.classList.add(
                "show-result",
                "saved"
            );


            if (placeholder) {

                placeholder.style.display =
                    "none";
            }


            canvas.dataset.saved =
                "true";


            /* TAMPILKAN DOWNLOAD */

            if (downloadButton) {

                downloadButton.style.display =
                    "inline-flex";
            }


            alert(
                "Tanda tangan berhasil disimpan."
            );
        }


        /* =====================================================
           DOWNLOAD TANDA TANGAN PNG
        ===================================================== */

        function downloadSignature(button) {

            const card =
                button.closest(".signature-card");

            if (!card) {
                return;
            }


            const signatureResult =
                card.querySelector(
                    ".signature-result"
                );


            if (!signatureResult) {

                alert(
                    "Tanda tangan belum tersedia."
                );

                return;
            }


            const imageData =
                signatureResult.dataset.signature;


            if (!imageData) {

                alert(
                    "Silakan simpan tanda tangan terlebih dahulu."
                );

                return;
            }


            const fileName =
                getSignatureFileName(card);


            const link =
                document.createElement("a");


            link.href =
                imageData;

            link.download =
                fileName;


            document.body.appendChild(link);

            link.click();

            document.body.removeChild(link);
        }


        /* =====================================================
           HAPUS TANDA TANGAN
        ===================================================== */

        function clearSignature(button) {

            const card =
                button.closest(".signature-card");

            if (!card) {
                return;
            }

            const canvas =
                card.querySelector(
                    ".signature-canvas"
                );

            const signatureArea =
                card.querySelector(
                    ".signature-area"
                );

            const signatureResult =
                card.querySelector(
                    ".signature-result"
                );

            const placeholder =
                card.querySelector(
                    ".signature-placeholder"
                );

            const downloadButton =
                card.querySelector(
                    ".signature-download"
                );


            if (
                !canvas ||
                !signatureArea
            ) {
                return;
            }


            const ctx =
                canvas.getContext("2d");


            ctx.clearRect(
                0,
                0,
                canvas.width,
                canvas.height
            );


            if (signatureResult) {

                signatureResult.src = "";

                signatureResult.dataset.signature =
                    "";
            }


            signatureArea.classList.remove(
                "active",
                "saved",
                "show-result"
            );


            canvas.dataset.saved =
                "false";


            if (placeholder) {

                placeholder.style.display =
                    "block";
            }


            /* SEMBUNYIKAN DOWNLOAD */

            if (downloadButton) {

                downloadButton.style.display =
                    "none";
            }
        }


        /* =====================================================
           DATA PJL PENERIMA
        ===================================================== */

        function updateDataPjlPenerima() {

            const namaInput =
                document.getElementById(
                    "namaPjlPenerima"
                );

            const nipInput =
                document.getElementById(
                    "nipPjlPenerima"
                );

            const dataPenerima =
                document.getElementById(
                    "dataPjlPenerima"
                );

            if (
                !namaInput ||
                !nipInput ||
                !dataPenerima
            ) {
                return;
            }

            const nama =
                namaInput.value.trim() || "-";

            const nip =
                nipInput.value.trim() || "-";

            dataPenerima.textContent = "";

            const namaText =
                document.createTextNode(
                    "Nama: " + nama
                );

            const br =
                document.createElement("br");

            const nipText =
                document.createTextNode(
                    "NIP: " + nip
                );

            dataPenerima.appendChild(namaText);
            dataPenerima.appendChild(br);
            dataPenerima.appendChild(nipText);
        }


        /* =====================================================
           SIMPAN DOKUMEN
        ===================================================== */

        function saveDocument() {

            alert(
                "Dokumen Serah Terima Dinas berhasil disimpan sementara."
            );
        }


        /* =====================================================
           INISIALISASI HALAMAN
        ===================================================== */

        document.addEventListener(
            "DOMContentLoaded",
            function() {

                renderDataKA();
                updateDataPjlPenerima();

                const namaPenerima =
                    document.getElementById(
                        "namaPjlPenerima"
                    );

                const nipPenerima =
                    document.getElementById(
                        "nipPjlPenerima"
                    );

                if (namaPenerima) {
                    namaPenerima.addEventListener(
                        "input",
                        updateDataPjlPenerima
                    );
                }

                if (nipPenerima) {
                    nipPenerima.addEventListener(
                        "input",
                        updateDataPjlPenerima
                    );
                }

            }
        );


        /* =====================================================
           UPDATE SAAT KEMBALI KE TAB
        ===================================================== */

        window.addEventListener(
            "focus",
            function() {

                renderDataKA();

            }
        );


        /* =====================================================
           UPDATE LOCAL STORAGE
        ===================================================== */

        window.addEventListener(
            "storage",
            function(event) {

                if (
                    event.key ===
                    REALISASI_KA_STORAGE_KEY
                ) {

                    renderDataKA();

                }

            }
        );


        /* =====================================================
           UPDATE VISIBILITY
        ===================================================== */

        document.addEventListener(
            "visibilitychange",
            function() {

                if (!document.hidden) {

                    renderDataKA();

                }

            }
        );

    </script>

</body>

</html>