<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Realisasi KA - Sistem PJL</title>

    <style>
        /* =========================================================
           RESET & GLOBAL
        ========================================================= */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            overflow-x: hidden;

            font-family: Arial, Helvetica, sans-serif;
            color: #1f2937;
            background: #f5f6f8;
        }

        body.menu-open {
            overflow: hidden;
        }

        button,
        input,
        select {
            font-family: inherit;
        }


        /* =========================================================
           HEADER
        ========================================================= */

        .top-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 3000;

            height: 60px;
            padding: 0 28px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            color: #fff;
            background: #1e3156;

            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.12);
        }

        .header-left,
        .header-right,
        .header-brand {
            display: flex;
            align-items: center;
        }

        .header-left {
            gap: 10px;
        }

        .header-right {
            gap: 12px;
        }

        .hamburger-btn {
            width: 36px;
            height: 36px;

            display: flex;
            align-items: center;
            justify-content: center;

            border: none;
            border-radius: 6px;

            color: #fff;
            background: transparent;

            font-size: 22px;
            line-height: 1;

            cursor: pointer;
            transition: 0.15s ease;
        }

        .hamburger-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .hamburger-btn:active {
            transform: scale(0.95);
        }

        .header-brand {
            gap: 10px;
        }

        .header-logo {
            width: 38px;
            height: 38px;

            display: flex;
            align-items: center;
            justify-content: center;

            overflow: hidden;

            border-radius: 6px;
            background: #fff;
        }

        .header-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .header-brand-text {
            display: flex;
            flex-direction: column;
        }

        .header-title {
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.2;
        }

        .header-subtitle {
            margin-top: 2px;

            color: #cbd5e1;
            font-size: 8px;
            line-height: 1.2;
        }

        .header-page {
            color: #cbd5e1;
            font-size: 9px;
            font-weight: 700;
        }

        .header-avatar {
            width: 34px;
            height: 34px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            color: #fff;
            background: #d9923e;

            font-size: 11px;
            font-weight: 800;
        }


        /* =========================================================
           SIDEBAR OVERLAY
        ========================================================= */

        .sidebar-overlay {
            position: fixed;
            inset: 0;
            z-index: 2000;

            background: rgba(0, 0, 0, 0.38);

            opacity: 0;
            visibility: hidden;
            pointer-events: none;

            transition: 0.25s ease;
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }


        /* =========================================================
           SIDEBAR
        ========================================================= */

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 2500;

            width: 235px;
            height: 100vh;

            display: flex;
            flex-direction: column;

            overflow-y: auto;

            color: #fff;
            background: #1e3156;

            transform: translateX(-105%);
            transition: transform 0.25s ease;

            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.18);
        }

        .sidebar.open {
            transform: translateX(0);
        }

        .sidebar-brand {
            min-height: 68px;
            padding: 17px 18px;

            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sidebar-title {
            color: #fff;
            font-size: 13px;
            font-weight: 800;
        }

        .sidebar-subtitle {
            margin-top: 4px;
            color: #cbd5e1;
            font-size: 8px;
        }

        .sidebar-menu {
            padding: 14px 10px;
        }

        .menu-item {
            min-height: 40px;
            margin-bottom: 4px;
            padding: 0 12px;

            display: flex;
            align-items: center;
            gap: 10px;

            border-radius: 6px;

            color: #e2e8f0;
            text-decoration: none;

            font-size: 10px;

            transition: 0.15s ease;
        }

        .menu-item:hover,
        .menu-item.active {
            color: #fff;
            background: #2d6796;
        }

        .menu-icon {
            width: 18px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            font-size: 13px;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding: 10px;
        }

        .profile-box {
            margin-bottom: 8px;
            padding: 11px;

            border-radius: 6px;
            background: rgba(255, 255, 255, 0.08);
        }

        .profile-label {
            margin-bottom: 5px;

            color: #cbd5e1;
            font-size: 7px;
            font-weight: 700;
        }

        .profile-name {
            margin-bottom: 3px;

            color: #fff;
            font-size: 10px;
            font-weight: 700;
        }

        .profile-nip,
        .profile-shift {
            color: #cbd5e1;
            font-size: 8px;
            line-height: 1.5;
        }

        .logout-link {
            min-height: 36px;
            padding: 0 12px;

            display: flex;
            align-items: center;
            gap: 10px;

            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 6px;

            color: #e2e8f0;
            text-decoration: none;

            font-size: 9px;

            transition: 0.15s ease;
        }

        .logout-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.08);
        }


        /* =========================================================
           MAIN
        ========================================================= */

        .main {
            width: 100%;
            min-height: 100vh;
            padding: 82px 28px 35px;
        }

        .page-card {
            width: 100%;
            max-width: 1500px;
            margin: 0 auto;
            padding: 24px;

            background: #fff;

            border: 1px solid #e1e5ea;
            border-radius: 10px;

            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
        }


        /* =========================================================
           PAGE HEADER
        ========================================================= */

        .company {
            margin-bottom: 4px;
            text-align: center;
        }

        .company-name {
            color: #1e3156;
            font-size: 12px;
            font-weight: 800;
        }

        .company-address {
            margin-top: 3px;
            color: #7b8492;
            font-size: 8px;
        }

        .page-title {
            margin-top: 10px;

            color: #1e3156;
            font-size: 20px;
            font-weight: 800;
            text-align: center;
        }

        .page-subtitle {
            margin-top: 4px;
            margin-bottom: 20px;

            color: #6b7280;
            font-size: 9px;
            text-align: center;
        }


        /* =========================================================
           FILTER
        ========================================================= */

        .filter-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1.1fr 0.7fr;
            gap: 12px;

            margin-bottom: 22px;
            padding: 14px;

            background: #f8f9fb;

            border: 1px solid #e5e8ed;
            border-radius: 8px;
        }

        .field-label {
            display: block;
            margin-bottom: 5px;

            color: #536174;
            font-size: 8px;
            font-weight: 700;
        }

        .field-input {
            width: 100%;
            height: 37px;
            padding: 0 10px;

            border: 1px solid #dce1e7;
            border-radius: 5px;

            outline: none;

            color: #374151;
            background: #fff;

            font-size: 10px;

            transition: 0.15s ease;
        }

        .field-input:focus {
            border-color: #55769a;
            box-shadow: 0 0 0 2px rgba(85, 118, 154, 0.08);
        }


        /* =========================================================
           SECTION TITLE
        ========================================================= */

        .section-title {
            margin-bottom: 4px;

            color: #1e3156;
            font-size: 13px;
            font-weight: 800;
        }

        .section-description {
            margin-bottom: 10px;

            color: #8a93a1;
            font-size: 8px;
            line-height: 1.5;
        }


        /* =========================================================
           INFO RENTANG WAKTU
        ========================================================= */

        .time-window-info {
            margin-bottom: 10px;
            padding: 8px 10px;

            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;

            color: #667085;
            background: #f8f9fb;

            border: 1px solid #e5e8ed;
            border-radius: 6px;

            font-size: 8px;
        }

        .time-window-info strong {
            color: #1e3156;
        }


        /* =========================================================
           TABLE
        ========================================================= */

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;

            border: 1px solid #e1e5ea;
            border-radius: 8px;

            -webkit-overflow-scrolling: touch;
            overscroll-behavior-x: contain;
        }

        table {
            width: 100%;
            min-width: 1100px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead {
            color: #fff;
            background: #1e3156;
        }

        th {
            height: 42px;
            padding: 7px 8px;

            color: #fff;
            font-size: 7px;
            font-weight: 700;

            text-align: left;
            vertical-align: middle;

            white-space: nowrap;
        }

        td {
            height: 54px;
            padding: 7px 8px;

            border-bottom: 1px solid #edf0f3;

            color: #374151;
            font-size: 8px;

            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #fafbfc;
        }

        th:nth-child(1),
        td:nth-child(1) {
            width: 4%;
            text-align: center;
        }

        th:nth-child(2),
        td:nth-child(2) {
            width: 8%;
        }

        th:nth-child(3),
        td:nth-child(3) {
            width: 14%;
        }

        th:nth-child(4),
        td:nth-child(4) {
            width: 8%;
        }

        th:nth-child(5),
        td:nth-child(5) {
            width: 10%;
        }

        th:nth-child(6),
        td:nth-child(6) {
            width: 8%;
        }

        th:nth-child(7),
        td:nth-child(7),
        th:nth-child(8),
        td:nth-child(8) {
            width: 9%;
        }

        th:nth-child(9),
        td:nth-child(9) {
            width: 11%;
        }

        th:nth-child(10),
        td:nth-child(10) {
            width: 9%;
        }

        th:nth-child(11),
        td:nth-child(11) {
            width: 12%;
        }


        /* =========================================================
           INPUT JAM AKTUAL
        ========================================================= */

        .actual-time {
            width: 100%;
            height: 30px;
            padding: 0 7px;

            border: 1px solid #e0e4e9;
            border-radius: 5px;

            outline: none;

            color: #374151;
            background: #f8f9fb;

            font-size: 9px;
        }

        .actual-time:focus {
            border-color: #55769a;
            background: #fff;
        }


        /* =========================================================
           STATUS
        ========================================================= */

        .status-select {
            width: 100%;
            height: 29px;
            padding: 0 6px;

            border: none;
            border-radius: 15px;

            color: #fff;

            font-size: 7px;
            font-weight: 700;
            text-align: center;

            cursor: pointer;
            outline: none;
        }

        .status-jalan {
            background: #22b573;
        }

        .status-batal {
            background: #ef5145;
        }

        .status-pass {
            background: #55769a;
        }

        .status-belum {
            color: #68758a;
            background: #dfe3e8;
        }


        /* =========================================================
           CHECK / CROSS
        ========================================================= */

        .check-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .check {
            width: 25px;
            height: 25px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            padding: 0;

            border: 1px solid #d5dce4;
            border-radius: 5px;

            background: #fff;

            font-size: 11px;
            font-weight: 700;

            cursor: pointer;
            transition: 0.15s ease;
        }

        .check:hover {
            transform: scale(1.05);
        }

        .active-yes {
            color: #22b573;
            border-color: #9de0be;
            background: #effbf5;
        }

        .active-no {
            color: #ef5145;
            border-color: #ffb8b2;
            background: #fff4f3;
        }

        .inactive {
            color: #b5bdc8;
            background: #f7f8fa;
        }


        /* =========================================================
           KETERANGAN
        ========================================================= */

        .note-input {
            width: 100%;
            height: 30px;
            padding: 0 7px;

            border: 1px solid #e0e4e9;
            border-radius: 5px;

            outline: none;

            color: #5d6878;
            background: #f8f9fb;

            font-size: 8px;
        }

        .note-input:focus {
            border-color: #55769a;
            background: #fff;
        }


        /* =========================================================
           TAMBAH KA
        ========================================================= */

        .add-section,
        .summary-section {
            margin-top: 20px;
        }

        .add-box,
        .summary-box {
            padding: 14px;

            background: #f8f9fb;

            border: 1px solid #e1e5ea;
            border-radius: 8px;
        }

        .add-heading {
            margin-bottom: 12px;

            color: #1e3156;
            font-size: 10px;
            font-weight: 800;
        }

        .add-grid {
            display: grid;

            grid-template-columns:
                0.75fr
                1.1fr
                0.75fr
                0.75fr
                0.75fr
                0.85fr
                0.85fr
                1.25fr;

            gap: 9px;
        }

        .small-field label {
            display: block;
            margin-bottom: 5px;

            color: #647084;
            font-size: 7px;
            font-weight: 700;
        }

        .small-input {
            width: 100%;
            height: 31px;
            padding: 0 7px;

            border: 1px solid #e0e4e9;
            border-radius: 5px;

            outline: none;

            color: #555;
            background: #fff;

            font-size: 8px;
        }

        .small-input:focus {
            border-color: #55769a;
        }

        .mini-checks {
            display: flex;
            gap: 4px;
        }

        .mini-check {
            width: 25px;
            height: 25px;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 0;

            border: 1px solid #d9dee5;
            border-radius: 5px;

            color: #8b95a4;
            background: #fff;

            font-size: 11px;

            cursor: pointer;
            transition: 0.15s ease;
        }

        .mini-check:hover {
            transform: scale(1.05);
        }

        .mini-check.active-yes {
            color: #22b573;
            border-color: #9de0be;
            background: #effbf5;
        }

        .mini-check.active-no {
            color: #ef5145;
            border-color: #ffb8b2;
            background: #fff4f3;
        }

        .add-button {
            margin-top: 11px;

            min-height: 34px;
            padding: 0 14px;

            border: 1px solid #1e3156;
            border-radius: 5px;

            color: #1e3156;
            background: #fff;

            font-size: 8px;
            font-weight: 700;

            cursor: pointer;
            transition: 0.15s ease;
        }

        .add-button:hover {
            color: #fff;
            background: #1e3156;
        }


        /* =========================================================
           SUMMARY
        ========================================================= */

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .summary-card {
            min-height: 62px;
            padding: 10px 11px;

            background: #fff;

            border: 1px solid #e4e7eb;
            border-radius: 6px;
        }

        .summary-label {
            margin-bottom: 4px;

            color: #758092;
            font-size: 7px;
            line-height: 1.3;
        }

        .summary-number {
            color: #1e3156;
            font-size: 18px;
            font-weight: 800;
        }

        .green {
            color: #22a96d;
        }

        .red {
            color: #ef5145;
        }

        .progress-title {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-top: 12px;
            margin-bottom: 5px;

            color: #536174;

            font-size: 8px;
            font-weight: 700;
        }

        .progress-bar {
            width: 100%;
            height: 7px;

            display: flex;
            overflow: hidden;

            border-radius: 5px;

            background: #e1e4e8;
        }

        .progress-green {
            background: #22b573;
        }

        .progress-red {
            background: #ef5145;
        }

        .progress-gray {
            background: #8f99a7;
        }

        .progress-description {
            margin-top: 6px;

            color: #9aa2ad;
            font-size: 7px;
        }


        /* =========================================================
           BOTTOM ACTION
        ========================================================= */

        .bottom-row {
            margin-top: 20px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 15px;
        }

        .digital-note {
            color: #8b95a4;
            font-size: 7px;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-btn {
            min-height: 34px;
            padding: 0 13px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            border-radius: 5px;

            font-size: 8px;
            font-weight: 700;

            cursor: pointer;
            transition: 0.15s ease;
        }

        .btn-outline {
            border: 1px solid #1e3156;
            color: #1e3156;
            background: #fff;
        }

        .btn-outline:hover {
            background: #f3f6fa;
        }

        .btn-dark {
            border: 1px solid #1e3156;
            color: #fff;
            background: #1e3156;
        }

        .btn-dark:hover {
            background: #263e6a;
        }

        .btn-orange {
            border: 1px solid #d9923e;
            color: #fff;
            background: #d9923e;
        }

        .btn-orange:hover {
            background: #c98232;
        }


        /* =========================================================
           PRINT
        ========================================================= */

        @media print {
            body {
                background: #fff;
            }

            .top-header,
            .sidebar,
            .sidebar-overlay,
            .add-section,
            .bottom-row,
            .filter-grid,
            .time-window-info {
                display: none !important;
            }

            .main {
                padding: 0;
            }

            .page-card {
                max-width: none;
                padding: 0;

                border: none;
                box-shadow: none;
            }

            .table-wrapper {
                overflow: visible;
            }

            table {
                width: 100%;
                min-width: 0;
            }

            th,
            td {
                font-size: 7px;
            }

            .actual-time,
            .status-select,
            .note-input {
                border: none;
                background: transparent;
            }

            .check {
                border: none;
            }
        }


        /* =========================================================
           TABLET
        ========================================================= */

        @media (max-width: 1050px) {
            .main {
                padding-left: 18px;
                padding-right: 18px;
            }

            .filter-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .add-grid {
                grid-template-columns: repeat(4, 1fr);
            }

            .summary-cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .bottom-row {
                flex-direction: column;
                align-items: stretch;
            }

            .actions {
                justify-content: flex-end;
            }

            .time-window-info {
                flex-direction: column;
                align-items: flex-start;
            }
        }


        /* =========================================================
           MOBILE
        ========================================================= */

        @media (max-width: 700px) {
            .top-header {
                height: 66px;
                padding: 0 15px;
            }

            .header-left {
                gap: 8px;
            }

            .hamburger-btn {
                width: 36px;
                height: 36px;
                font-size: 21px;
            }

            .header-logo {
                width: 34px;
                height: 34px;
            }

            .header-title {
                font-size: 13px;
            }

            .header-subtitle {
                font-size: 7px;
            }

            .header-page {
                display: none;
            }

            .header-avatar {
                width: 34px;
                height: 34px;
            }

            .sidebar {
                width: 235px;
                max-width: 82vw;
            }

            .main {
                padding: 77px 10px 25px;
            }

            .page-card {
                padding: 16px 12px 20px;
                border-radius: 7px;
            }

            .company-name {
                font-size: 9px;
                line-height: 1.3;
            }

            .company-address {
                font-size: 7px;
            }

            .page-title {
                margin-top: 8px;
                font-size: 15px;
                line-height: 1.3;
            }

            .page-subtitle {
                margin-bottom: 15px;
                font-size: 8px;
                line-height: 1.4;
            }

            .filter-grid {
                grid-template-columns: 1fr;
                gap: 10px;
                padding: 12px;
            }

            .field-label {
                font-size: 8px;
            }

            .field-input {
                height: 38px;
                font-size: 11px;
            }

            .section-title {
                font-size: 12px;
            }

            .section-description {
                font-size: 8px;
            }

            .time-window-info {
                padding: 8px;
                font-size: 8px;
                line-height: 1.5;
            }

            .table-wrapper {
                border-radius: 6px;
            }

            table {
                width: 1180px;
                min-width: 1180px;
            }

            th {
                height: 40px;
                padding: 7px;
                font-size: 8px;
            }

            td {
                height: 48px;
                padding: 7px;
                font-size: 8px;
            }

            .actual-time {
                height: 32px;
                font-size: 10px;
            }

            .status-select {
                height: 30px;
                font-size: 8px;
            }

            .check {
                width: 28px;
                height: 28px;
            }

            .note-input {
                height: 32px;
                font-size: 9px;
            }

            .add-box {
                padding: 12px;
            }

            .add-grid {
                grid-template-columns: 1fr;
                gap: 11px;
            }

            .small-input {
                height: 38px;
                font-size: 11px;
            }

            .mini-check {
                width: 32px;
                height: 32px;
                font-size: 13px;
            }

            .add-button {
                width: 100%;
                min-height: 40px;
                font-size: 10px;
            }

            .summary-box {
                padding: 12px;
            }

            .summary-cards {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }

            .summary-card {
                min-height: 60px;
                padding: 9px;
            }

            .summary-label {
                font-size: 7px;
            }

            .summary-number {
                font-size: 16px;
            }

            .progress-title {
                font-size: 8px;
            }

            .progress-description {
                font-size: 7px;
                line-height: 1.4;
            }

            .bottom-row {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .digital-note {
                font-size: 7px;
                line-height: 1.5;
            }

            .actions {
                width: 100%;
                flex-direction: column;
                gap: 8px;
            }

            .action-btn {
                width: 100%;
                min-height: 42px;
                font-size: 9px;
            }
        }


        /* =========================================================
           HP KECIL
        ========================================================= */

        @media (max-width: 400px) {
            .top-header {
                height: 64px;
                padding: 0 12px;
            }

            .header-logo {
                width: 32px;
                height: 32px;
            }

            .header-title {
                font-size: 12px;
            }

            .header-subtitle {
                font-size: 7px;
            }

            .header-avatar {
                width: 33px;
                height: 33px;
            }

            .main {
                padding: 74px 7px 20px;
            }

            .page-card {
                padding: 13px 9px 16px;
            }

            .page-title {
                font-size: 13px;
            }

            .page-subtitle {
                font-size: 7px;
            }

            .summary-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>


<body>

    <!-- =========================================================
         HEADER
    ========================================================= -->

    <header class="top-header">

        <div class="header-left">

            <button
                type="button"
                id="hamburgerBtn"
                class="hamburger-btn"
                onclick="toggleSidebar()"
                aria-label="Buka menu"
                aria-expanded="false">
                ☰
            </button>

            <div class="header-brand">

                <div class="header-logo">
                    <img
                        src="{{ asset('images/logo-kai.jpg') }}"
                        alt="Logo KAI">
                </div>

                <div class="header-brand-text">
                    <div class="header-title">
                        Sistem PJL
                    </div>

                    <div class="header-subtitle">
                        Realisasi KA
                    </div>
                </div>

            </div>

        </div>


        <div class="header-right">

            <div class="header-page">
                REALISASI KA
            </div>

            <div class="header-avatar">
                S
            </div>

        </div>

    </header>


    <!-- =========================================================
         SIDEBAR OVERLAY
    ========================================================= -->

    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
        onclick="closeSidebar()">
    </div>


    <!-- =========================================================
         SIDEBAR
    ========================================================= -->

    <aside
        class="sidebar"
        id="sidebar">

        <div class="sidebar-brand">

            <div class="sidebar-title">
                KAI OPERASIONAL
            </div>

            <div class="sidebar-subtitle">
                SISTEM DIGITAL PJL
            </div>

        </div>


        <nav class="sidebar-menu">

            <a
                href="/user/dashboard"
                class="menu-item">

                <span class="menu-icon">⌂</span>
                <span>Dashboard</span>

            </a>


            <a
                href="/user/realisasi-ka"
                class="menu-item active">

                <span class="menu-icon">▣</span>
                <span>Realisasi KA</span>

            </a>


            <a
                href="/user/serah-terima-dinas"
                class="menu-item">

                <span class="menu-icon">⇄</span>
                <span>Serah Terima Dinas</span>

            </a>


            <a
                href="/user/catatan-dinas"
                class="menu-item">

                <span class="menu-icon">▤</span>
                <span>Catatan Dinas</span>

            </a>

        </nav>


        <div class="sidebar-bottom">

            <div class="profile-box">

                <div class="profile-label">
                    PJL AKTIF
                </div>

                <div class="profile-name">
                    Budi Santoso
                </div>

                <div class="profile-nip">
                    NIP: 199208242015031002
                </div>

                <div class="profile-shift">
                    Shift B · 08:00–16:00
                </div>

            </div>


            <a
                href="/login"
                class="logout-link">

                <span>↪</span>
                <span>Keluar</span>

            </a>

        </div>

    </aside>


    <!-- =========================================================
         MAIN
    ========================================================= -->

    <main class="main">

        <section class="page-card">

            <!-- PAGE HEADER -->

            <div class="company">

                <div class="company-name">
                    PT KERETA API INDONESIA (PERSERO)
                </div>

                <div class="company-address">
                    DIREKTORAT KESELAMATAN DAN KEAMANAN
                </div>

            </div>


            <h1 class="page-title">
                REALISASI KA YANG LEWAT JPL
            </h1>


            <div class="page-subtitle">
                Buku Catatan Harian Penjagaan Jalan Perlintasan
            </div>


            <!-- =====================================================
                 FILTER
            ====================================================== -->

            <div class="filter-grid">

                <div>
                    <label
                        for="tanggal"
                        class="field-label">
                        HARI / TANGGAL
                    </label>

                    <input
                        type="date"
                        id="tanggal"
                        class="field-input"
                        value="2026-10-24">
                </div>


                <div>
                    <label
                        for="nomorJpl"
                        class="field-label">
                        NOMOR JPL
                    </label>

                    <input
                        id="nomorJpl"
                        type="text"
                        class="field-input"
                        value="JPL 45">
                </div>


                <div>
                    <label
                        for="stasiunRegional"
                        class="field-label">
                        STASIUN REGIONAL
                    </label>

                    <input
                        id="stasiunRegional"
                        type="text"
                        class="field-input"
                        value="Cirebon (CN)">
                </div>


                <div>
                    <label
                        for="shift"
                        class="field-label">
                        SHIFT
                    </label>

                    <select
                        id="shift"
                        class="field-input">

                        <option value="Pagi">Pagi</option>
                        <option value="Siang">Siang</option>
                        <option value="Malam">Malam</option>

                    </select>
                </div>

            </div>


            <!-- =====================================================
                 DAFTAR KA
            ====================================================== -->

            <div class="section-title">
                Daftar KA Terjadwal
            </div>

            <div class="section-description">
                Menampilkan KA berdasarkan jam jadwal dalam rentang
                1 jam sebelum sampai 1 jam sesudah waktu sekarang.
                Semua status mengikuti rentang waktu yang sama.
            </div>


            <div class="time-window-info">

                <span>
                    Semua KA mengikuti
                    <strong>Jam Jadwal ±1 jam</strong>.
                    KA manual tanpa jadwal selalu ditampilkan.
                </span>

                <span id="windowRange">
                    Memuat waktu...
                </span>

            </div>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>
                            <th>NO</th>
                            <th>NOMOR KA</th>
                            <th>NAMA KA</th>
                            <th>JAM JADWAL</th>
                            <th>JAM AKTUAL</th>
                            <th>STATUS</th>
                            <th>SEMBOYAN 2.1</th>
                            <th>GENTA</th>
                            <th>PINTU PERLINTASAN</th>
                            <th>SEMBOYAN 3.5</th>
                            <th>KETERANGAN</th>
                        </tr>

                    </thead>

                    <tbody id="kaTableBody"></tbody>

                </table>

            </div>


            <!-- =====================================================
                 TAMBAH KA MANUAL
            ====================================================== -->

            <section class="add-section">

                <div class="add-box">

                    <div class="add-heading">
                        Tambah KA Manual
                    </div>


                    <div class="add-grid">

                        <div class="small-field">

                            <label for="manualNomor">
                                NOMOR KA
                            </label>

                            <input
                                id="manualNomor"
                                type="text"
                                class="small-input"
                                placeholder="Masukkan nomor KA">

                        </div>


                        <div class="small-field">

                            <label for="manualNama">
                                NAMA KA
                            </label>

                            <input
                                id="manualNama"
                                type="text"
                                class="small-input"
                                placeholder="Masukkan nama KA">

                        </div>


                        <div class="small-field">

                            <label for="manualAktual">
                                JAM AKTUAL
                            </label>

                            <input
                                id="manualAktual"
                                type="time"
                                class="small-input">

                        </div>


                        <div class="small-field">

                            <label>SEMBOYAN 2.1</label>

                            <div class="mini-checks">

                                <button
                                    type="button"
                                    class="mini-check inactive"
                                    data-group="manual21"
                                    data-value="yes"
                                    onclick="setManualCheck(this)">
                                    ✓
                                </button>

                                <button
                                    type="button"
                                    class="mini-check inactive"
                                    data-group="manual21"
                                    data-value="no"
                                    onclick="setManualCheck(this)">
                                    ×
                                </button>

                            </div>

                        </div>


                        <div class="small-field">

                            <label>GENTA</label>

                            <div class="mini-checks">

                                <button
                                    type="button"
                                    class="mini-check inactive"
                                    data-group="manualGenta"
                                    data-value="yes"
                                    onclick="setManualCheck(this)">
                                    ✓
                                </button>

                                <button
                                    type="button"
                                    class="mini-check inactive"
                                    data-group="manualGenta"
                                    data-value="no"
                                    onclick="setManualCheck(this)">
                                    ×
                                </button>

                            </div>

                        </div>


                        <div class="small-field">

                            <label>PINTU PERLINTASAN</label>

                            <div class="mini-checks">

                                <button
                                    type="button"
                                    class="mini-check inactive"
                                    data-group="manualPintu"
                                    data-value="yes"
                                    onclick="setManualCheck(this)">
                                    ✓
                                </button>

                                <button
                                    type="button"
                                    class="mini-check inactive"
                                    data-group="manualPintu"
                                    data-value="no"
                                    onclick="setManualCheck(this)">
                                    ×
                                </button>

                            </div>

                        </div>


                        <div class="small-field">

                            <label>SEMBOYAN 3.5</label>

                            <div class="mini-checks">

                                <button
                                    type="button"
                                    class="mini-check inactive"
                                    data-group="manual35"
                                    data-value="yes"
                                    onclick="setManualCheck(this)">
                                    ✓
                                </button>

                                <button
                                    type="button"
                                    class="mini-check inactive"
                                    data-group="manual35"
                                    data-value="no"
                                    onclick="setManualCheck(this)">
                                    ×
                                </button>

                            </div>

                        </div>


                        <div class="small-field">

                            <label for="manualKeterangan">
                                KETERANGAN
                            </label>

                            <input
                                id="manualKeterangan"
                                type="text"
                                class="small-input"
                                placeholder="Masukkan keterangan">

                        </div>

                    </div>


                    <button
                        type="button"
                        class="add-button"
                        onclick="tambahKAManual()">

                        ＋ Tambah KA Manual

                    </button>

                </div>

            </section>


            <!-- =====================================================
                 SUMMARY
            ====================================================== -->

            <section class="summary-section">

                <div class="section-title">
                    Ringkasan Realisasi Shift
                </div>

                <div class="section-description">
                    Ringkasan otomatis berdasarkan status seluruh data KA.
                </div>


                <div class="summary-box">

                    <div class="summary-cards">

                        <div class="summary-card">

                            <div class="summary-label">
                                TOTAL KA TERJADWAL
                            </div>

                            <div
                                id="totalKA"
                                class="summary-number">
                                0
                            </div>

                        </div>


                        <div class="summary-card">

                            <div class="summary-label">
                                KA SUDAH LEWAT (JALAN)
                            </div>

                            <div
                                id="jalanKA"
                                class="summary-number green">
                                0
                            </div>

                        </div>


                        <div class="summary-card">

                            <div class="summary-label">
                                KA BATAL
                            </div>

                            <div
                                id="batalKA"
                                class="summary-number red">
                                0
                            </div>

                        </div>


                        <div class="summary-card">

                            <div class="summary-label">
                                KA BELUM DIPROSES
                            </div>

                            <div
                                id="belumKA"
                                class="summary-number">
                                0
                            </div>

                        </div>

                    </div>


                    <div class="progress-title">

                        <span>Progress realisasi</span>

                        <span id="progressText">
                            0%
                        </span>

                    </div>


                    <div class="progress-bar">

                        <div
                            id="progressGreen"
                            class="progress-green">
                        </div>

                        <div
                            id="progressRed"
                            class="progress-red">
                        </div>

                        <div
                            id="progressGray"
                            class="progress-gray">
                        </div>

                    </div>


                    <div class="progress-description">
                        Ringkasan ini otomatis digunakan untuk laporan serah terima dinas.
                    </div>

                </div>

            </section>


            <!-- =====================================================
                 BOTTOM ACTION
            ====================================================== -->

            <div class="bottom-row">

                <div class="digital-note">
                    Formulir realisasi digital sesuai regulasi KAI Dinasan PJL
                </div>


                <div class="actions">

                    <button
                        type="button"
                        class="action-btn btn-outline"
                        onclick="cetakDokumen()">

                        ▣ &nbsp; Cetak Dokumen JPL

                    </button>


                    <button
                        type="button"
                        class="action-btn btn-dark"
                        onclick="simpanRealisasi()">

                        ▣ &nbsp; Simpan Realisasi

                    </button>


                    <a
                        href="/serah-terima-dinas"
                        class="action-btn btn-orange"
                        style="text-decoration: none;">

                        Selesai &amp; Generate Laporan Serah Terima

                    </a>

                </div>

            </div>

        </section>

    </main>


    <!-- =========================================================
         JAVASCRIPT
    ========================================================= -->

    <script>
        /* =========================================================
           DATA DEFAULT
        ========================================================= */

        const defaultDataKA = [
            {
                no: 1,
                nomor: "KA 122",
                nama: "Argo Bromo Anggrek",
                jadwal: "08:45",
                aktual: "08:45",
                status: "JALAN",
                semboyan21: "yes",
                genta: "yes",
                pintu: "yes",
                semboyan35: "yes",
                keterangan: "Normal"
            },
            {
                no: 2,
                nomor: "KA 125",
                nama: "Argo Sindoro",
                jadwal: "09:10",
                aktual: "09:10",
                status: "JALAN",
                semboyan21: "yes",
                genta: "yes",
                pintu: "yes",
                semboyan35: "yes",
                keterangan: "Normal"
            },
            {
                no: 3,
                nomor: "KA 128",
                nama: "Argo Muria",
                jadwal: "09:35",
                aktual: "09:35",
                status: "JALAN",
                semboyan21: "yes",
                genta: "yes",
                pintu: "yes",
                semboyan35: "yes",
                keterangan: "Normal"
            },
            {
                no: 4,
                nomor: "KA 131",
                nama: "Argo Parahyangan",
                jadwal: "10:05",
                aktual: "10:05",
                status: "JALAN",
                semboyan21: "yes",
                genta: "yes",
                pintu: "yes",
                semboyan35: "yes",
                keterangan: "Normal"
            },
            {
                no: 5,
                nomor: "KA 134",
                nama: "Argo Jati",
                jadwal: "10:30",
                aktual: "10:30",
                status: "PASS",
                semboyan21: "yes",
                genta: "yes",
                pintu: "yes",
                semboyan35: "yes",
                keterangan: "Sudah melewati JPL"
            },
            {
                no: 6,
                nomor: "KA 137",
                nama: "Argo Lawu",
                jadwal: "11:00",
                aktual: "",
                status: "BATAL",
                semboyan21: "no",
                genta: "no",
                pintu: "no",
                semboyan35: "no",
                keterangan: "Palang mati - gangguan listrik"
            },
            {
                no: 7,
                nomor: "KA 140",
                nama: "Argo Dwipangga",
                jadwal: "11:25",
                aktual: "11:28",
                status: "JALAN",
                semboyan21: "no",
                genta: "yes",
                pintu: "yes",
                semboyan35: "yes",
                keterangan: "Semboyan 2.1 tidak terlihat"
            },
            {
                no: 8,
                nomor: "KA 143",
                nama: "Argo Wilis",
                jadwal: "11:50",
                aktual: "",
                status: "BELUM",
                semboyan21: "",
                genta: "",
                pintu: "",
                semboyan35: "",
                keterangan: ""
            },
            {
                no: 9,
                nomor: "KA 146",
                nama: "Argo Cheribon",
                jadwal: "12:15",
                aktual: "",
                status: "BELUM",
                semboyan21: "",
                genta: "",
                pintu: "",
                semboyan35: "",
                keterangan: ""
            },
            {
                no: 10,
                nomor: "KA 149",
                nama: "Argo Semeru",
                jadwal: "12:40",
                aktual: "",
                status: "BELUM",
                semboyan21: "",
                genta: "",
                pintu: "",
                semboyan35: "",
                keterangan: ""
            }
        ];


        /* =========================================================
           LOCAL STORAGE
        ========================================================= */

        const STORAGE_KEY = "realisasi_ka_pjl";

        function loadDataKA() {
            try {
                const savedData = localStorage.getItem(STORAGE_KEY);

                if (savedData) {
                    const parsedData = JSON.parse(savedData);

                    if (Array.isArray(parsedData)) {
                        return parsedData;
                    }
                }
            } catch (error) {
                console.error("Gagal membaca data tersimpan:", error);
            }

            return JSON.parse(JSON.stringify(defaultDataKA));
        }

        let dataKA = loadDataKA();


        function saveDataKA() {
            try {
                localStorage.setItem(
                    STORAGE_KEY,
                    JSON.stringify(dataKA)
                );
            } catch (error) {
                console.error("Gagal menyimpan data KA:", error);
            }
        }


        /* =========================================================
           HELPER
        ========================================================= */

        function escapeHtml(value) {
            return String(value ?? "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }


        /* =========================================================
           KONVERSI WAKTU
        ========================================================= */

        function timeToMinutes(time) {
            if (!time || time === "-") {
                return null;
            }

            const parts = time.split(":");

            if (parts.length !== 2) {
                return null;
            }

            const hours = Number(parts[0]);
            const minutes = Number(parts[1]);

            if (
                Number.isNaN(hours) ||
                Number.isNaN(minutes) ||
                hours < 0 ||
                hours > 23 ||
                minutes < 0 ||
                minutes > 59
            ) {
                return null;
            }

            return hours * 60 + minutes;
        }


        /* =========================================================
           WAKTU WIB
        ========================================================= */

        function getWIBTime() {
            const formatter = new Intl.DateTimeFormat("en-GB", {
                timeZone: "Asia/Jakarta",
                hour: "2-digit",
                minute: "2-digit",
                second: "2-digit",
                hour12: false
            });

            const parts = formatter.formatToParts(new Date());
            const result = {};

            parts.forEach(part => {
                if (
                    part.type === "hour" ||
                    part.type === "minute" ||
                    part.type === "second"
                ) {
                    result[part.type] = Number(part.value);
                }
            });

            return result;
        }


        function getCurrentMinutes() {
            const now = getWIBTime();
            return now.hour * 60 + now.minute;
        }


        /* =========================================================
           FILTER ±1 JAM
        ========================================================= */

        function isWithinOneHour(ka) {
            // KA manual selalu ditampilkan
            if (!ka.jadwal || ka.jadwal === "-") {
                return true;
            }

            const jadwalMinutes = timeToMinutes(ka.jadwal);

            // Jadwal tidak valid → tetap tampil
            if (jadwalMinutes === null) {
                return true;
            }

            const currentMinutes = getCurrentMinutes();

            let difference = Math.abs(
                jadwalMinutes - currentMinutes
            );

            // Menangani pergantian hari
            difference = Math.min(
                difference,
                1440 - difference
            );

            return difference <= 60;
        }


        function getVisibleKA() {
            return dataKA
                .map((ka, index) => ({ ka, index }))
                .filter(item => isWithinOneHour(item.ka));
        }


        /* =========================================================
           FORMAT RENTANG WAKTU
        ========================================================= */

        function formatMinutes(totalMinutes) {
            totalMinutes = (totalMinutes + 1440) % 1440;

            const hours = Math.floor(totalMinutes / 60);
            const minutes = totalMinutes % 60;

            return (
                String(hours).padStart(2, "0") +
                ":" +
                String(minutes).padStart(2, "0")
            );
        }


        function updateWindowRange() {
            const element = document.getElementById("windowRange");

            if (!element) {
                return;
            }

            const now = getWIBTime();

            const currentMinutes =
                now.hour * 60 + now.minute;

            const startMinutes = currentMinutes - 60;
            const endMinutes = currentMinutes + 60;

            const currentTime =
                String(now.hour).padStart(2, "0") +
                ":" +
                String(now.minute).padStart(2, "0") +
                ":" +
                String(now.second).padStart(2, "0");

            element.innerHTML =
                `<strong>${currentTime} WIB</strong>` +
                ` · Rentang ${formatMinutes(startMinutes)}` +
                ` – ${formatMinutes(endMinutes)}`;
        }


        /* =========================================================
           CHECK GROUP
        ========================================================= */

        function createCheckGroup(value) {
            const yesClass =
                value === "yes"
                    ? "active-yes"
                    : "inactive";

            const noClass =
                value === "no"
                    ? "active-no"
                    : "inactive";

            return `
                <div class="check-group">

                    <button
                        type="button"
                        class="check ${yesClass}"
                        onclick="setCheck(this, 'yes')"
                        aria-label="Ya">
                        ✓
                    </button>

                    <button
                        type="button"
                        class="check ${noClass}"
                        onclick="setCheck(this, 'no')"
                        aria-label="Tidak">
                        ×
                    </button>

                </div>
            `;
        }


        /* =========================================================
           STATUS
        ========================================================= */

        function getStatusClass(status) {
            switch (status) {
                case "JALAN":
                    return "status-jalan";

                case "BATAL":
                    return "status-batal";

                case "PASS":
                    return "status-pass";

                case "BELUM":
                    return "status-belum";

                default:
                    return "status-belum";
            }
        }


        function createStatus(status) {
            return `
                <select
                    class="status-select ${getStatusClass(status)}"
                    onchange="updateStatus(this)">

                    <option
                        value="JALAN"
                        ${status === "JALAN" ? "selected" : ""}>
                        JALAN
                    </option>

                    <option
                        value="BATAL"
                        ${status === "BATAL" ? "selected" : ""}>
                        BATAL
                    </option>

                    <option
                        value="PASS"
                        ${status === "PASS" ? "selected" : ""}>
                        PASS
                    </option>

                    <option
                        value="BELUM"
                        ${status === "BELUM" ? "selected" : ""}>
                        BELUM
                    </option>

                </select>
            `;
        }


        /* =========================================================
           RENDER TABLE
        ========================================================= */

        let lastVisibleIds = "";

        function getVisibleIds() {
            return getVisibleKA()
                .map(item => item.index)
                .join(",");
        }


        function renderTable() {
            const tbody = document.getElementById("kaTableBody");

            if (!tbody) {
                return;
            }

            const visibleKA = getVisibleKA();

            lastVisibleIds = visibleKA
                .map(item => item.index)
                .join(",");

            tbody.innerHTML = "";


            /* Tidak ada KA */
            if (visibleKA.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td
                            colspan="11"
                            style="
                                height:80px;
                                text-align:center;
                                color:#8a93a1;
                                font-size:9px;
                            ">
                            Tidak ada KA dalam rentang waktu
                            ±1 jam dari waktu sekarang.
                        </td>
                    </tr>
                `;

                updateSummary();
                updateWindowRange();

                return;
            }


            /* Render KA */
            visibleKA.forEach(({ ka, index }) => {
                const row = document.createElement("tr");

                row.dataset.index = index;

                row.innerHTML = `
                    <td>
                        ${escapeHtml(ka.no)}
                    </td>

                    <td>
                        <strong>
                            ${escapeHtml(ka.nomor)}
                        </strong>
                    </td>

                    <td>
                        ${escapeHtml(ka.nama)}
                    </td>

                    <td>
                        ${escapeHtml(ka.jadwal)}
                    </td>

                    <td>
                        <input
                            type="time"
                            class="actual-time"
                            value="${escapeHtml(ka.aktual)}"
                            onchange="updateActualTime(
                                ${index},
                                this.value
                            )">
                    </td>

                    <td>
                        ${createStatus(ka.status)}
                    </td>

                    <td>
                        ${createCheckGroup(ka.semboyan21)}
                    </td>

                    <td>
                        ${createCheckGroup(ka.genta)}
                    </td>

                    <td>
                        ${createCheckGroup(ka.pintu)}
                    </td>

                    <td>
                        ${createCheckGroup(ka.semboyan35)}
                    </td>

                    <td>
                        <input
                            type="text"
                            class="note-input"
                            value="${escapeHtml(ka.keterangan)}"
                            placeholder="Keterangan"
                            onchange="updateKeterangan(
                                ${index},
                                this.value
                            )">
                    </td>
                `;

                tbody.appendChild(row);
            });


            updateSummary();
            updateWindowRange();
        }


        /* =========================================================
           UPDATE JAM AKTUAL
        ========================================================= */

        function updateActualTime(index, value) {
            if (!dataKA[index]) {
                return;
            }

            dataKA[index].aktual = value;
            saveDataKA();
        }


        /* =========================================================
           UPDATE STATUS
        ========================================================= */

        function updateStatus(select) {
            const row = select.closest("tr");

            if (!row) {
                return;
            }

            const index = Number(row.dataset.index);

            if (!dataKA[index]) {
                return;
            }

            const newStatus = select.value;

            dataKA[index].status = newStatus;


            // Update warna status
            select.classList.remove(
                "status-jalan",
                "status-batal",
                "status-pass",
                "status-belum"
            );

            select.classList.add(
                getStatusClass(newStatus)
            );


            /* =====================================================
               JIKA BATAL
            ===================================================== */

            if (newStatus === "BATAL") {

                dataKA[index].semboyan21 = "no";
                dataKA[index].genta = "no";
                dataKA[index].pintu = "no";
                dataKA[index].semboyan35 = "no";


                const groups =
                    row.querySelectorAll(".check-group");


                groups.forEach(group => {

                    const buttons =
                        group.querySelectorAll(".check");


                    buttons.forEach(button => {
                        button.classList.remove(
                            "active-yes",
                            "active-no"
                        );

                        button.classList.add(
                            "inactive"
                        );
                    });


                    const noButton = buttons[1];

                    if (noButton) {
                        noButton.classList.remove(
                            "inactive"
                        );

                        noButton.classList.add(
                            "active-no"
                        );
                    }
                });
            }


            /*
             * Jangan render ulang tabel.
             * Jadi baris tidak langsung hilang.
             */

            saveDataKA();
            updateSummary();
        }


        /* =========================================================
           UPDATE CHECK / CROSS
        ========================================================= */

        function setCheck(button, value) {
            const group = button.parentElement;

            if (!group) {
                return;
            }

            const buttons =
                group.querySelectorAll(".check");


            buttons.forEach(btn => {
                btn.classList.remove(
                    "active-yes",
                    "active-no"
                );

                btn.classList.add("inactive");
            });


            button.classList.remove("inactive");

            button.classList.add(
                value === "yes"
                    ? "active-yes"
                    : "active-no"
            );


            const row = button.closest("tr");

            if (!row) {
                return;
            }

            const index = Number(row.dataset.index);

            if (!dataKA[index]) {
                return;
            }


            const groups =
                row.querySelectorAll(".check-group");


            if (
                groups[0] &&
                groups[0].contains(button)
            ) {
                dataKA[index].semboyan21 = value;

            } else if (
                groups[1] &&
                groups[1].contains(button)
            ) {
                dataKA[index].genta = value;

            } else if (
                groups[2] &&
                groups[2].contains(button)
            ) {
                dataKA[index].pintu = value;

            } else if (
                groups[3] &&
                groups[3].contains(button)
            ) {
                dataKA[index].semboyan35 = value;
            }


            saveDataKA();
        }


        /* =========================================================
           UPDATE KETERANGAN
        ========================================================= */

        function updateKeterangan(index, value) {
            if (!dataKA[index]) {
                return;
            }

            dataKA[index].keterangan = value;
            saveDataKA();
        }


        /* =========================================================
           SUMMARY
        ========================================================= */

        function updateSummary() {
            const total = dataKA.length;

            const jalan = dataKA.filter(
                ka => ka.status === "JALAN"
            ).length;

            const batal = dataKA.filter(
                ka => ka.status === "BATAL"
            ).length;

            const belum = dataKA.filter(
                ka => ka.status === "BELUM"
            ).length;


            document.getElementById("totalKA").textContent =
                total;

            document.getElementById("jalanKA").textContent =
                jalan;

            document.getElementById("batalKA").textContent =
                batal;

            document.getElementById("belumKA").textContent =
                belum;


            const selesai = jalan + batal;

            const progress =
                total > 0
                    ? Math.round((selesai / total) * 100)
                    : 0;


            document.getElementById("progressText").textContent =
                `${progress}%`;


            document.getElementById("progressGreen").style.width =
                `${total > 0 ? (jalan / total) * 100 : 0}%`;

            document.getElementById("progressRed").style.width =
                `${total > 0 ? (batal / total) * 100 : 0}%`;

            document.getElementById("progressGray").style.width =
                `${total > 0 ? (belum / total) * 100 : 0}%`;
        }


        /* =========================================================
           SIMPAN & CETAK
        ========================================================= */

        function simpanRealisasi() {
            saveDataKA();

            console.log("Data realisasi:", dataKA);

            alert("Realisasi KA berhasil disimpan.");
        }


        function cetakDokumen() {
            window.print();
        }


        /* =========================================================
           KA MANUAL
        ========================================================= */

        let manualCheck = {
            semboyan21: "",
            genta: "",
            pintu: "",
            semboyan35: ""
        };


        function setManualCheck(button) {
            const group = button.dataset.group;
            const value = button.dataset.value;

            document
                .querySelectorAll(`[data-group="${group}"]`)
                .forEach(btn => {
                    btn.classList.remove(
                        "active-yes",
                        "active-no"
                    );

                    btn.classList.add("inactive");
                });


            button.classList.remove("inactive");

            button.classList.add(
                value === "yes"
                    ? "active-yes"
                    : "active-no"
            );


            switch (group) {
                case "manual21":
                    manualCheck.semboyan21 = value;
                    break;

                case "manualGenta":
                    manualCheck.genta = value;
                    break;

                case "manualPintu":
                    manualCheck.pintu = value;
                    break;

                case "manual35":
                    manualCheck.semboyan35 = value;
                    break;
            }
        }


        function resetManualChecks() {
            manualCheck = {
                semboyan21: "",
                genta: "",
                pintu: "",
                semboyan35: ""
            };


            document
                .querySelectorAll(".mini-check")
                .forEach(button => {
                    button.classList.remove(
                        "active-yes",
                        "active-no"
                    );

                    button.classList.add("inactive");
                });
        }


        function tambahKAManual() {
            const nomor =
                document.getElementById("manualNomor")
                    .value.trim();

            const nama =
                document.getElementById("manualNama")
                    .value.trim();

            const aktual =
                document.getElementById("manualAktual")
                    .value;

            const keterangan =
                document.getElementById("manualKeterangan")
                    .value.trim();


            if (!nomor) {
                alert("Nomor KA wajib diisi.");
                document.getElementById("manualNomor").focus();
                return;
            }

            if (!nama) {
                alert("Nama KA wajib diisi.");
                document.getElementById("manualNama").focus();
                return;
            }

            if (!aktual) {
                alert("Jam aktual wajib diisi.");
                document.getElementById("manualAktual").focus();
                return;
            }


            dataKA.push({
                no: dataKA.length + 1,
                nomor: nomor,
                nama: nama,

                // "-" = selalu ditampilkan
                jadwal: "-",

                aktual: aktual,
                status: "JALAN",

                semboyan21:
                    manualCheck.semboyan21,

                genta:
                    manualCheck.genta,

                pintu:
                    manualCheck.pintu,

                semboyan35:
                    manualCheck.semboyan35,

                keterangan:
                    keterangan || "KA tambahan"
            });


            saveDataKA();
            renderTable();


            // Reset form
            document.getElementById("manualNomor").value = "";
            document.getElementById("manualNama").value = "";
            document.getElementById("manualAktual").value = "";
            document.getElementById("manualKeterangan").value = "";

            resetManualChecks();


            alert(
                `KA ${nomor} berhasil ditambahkan.`
            );
        }


        /* =========================================================
           SIDEBAR
        ========================================================= */

        function toggleSidebar() {
            const sidebar =
                document.getElementById("sidebar");

            const overlay =
                document.getElementById("sidebarOverlay");

            const button =
                document.getElementById("hamburgerBtn");


            if (sidebar.classList.contains("open")) {
                closeSidebar();
                return;
            }


            sidebar.classList.add("open");
            overlay.classList.add("active");

            document.body.classList.add("menu-open");


            button.setAttribute(
                "aria-expanded",
                "true"
            );

            button.setAttribute(
                "aria-label",
                "Tutup menu"
            );
        }


        function closeSidebar() {
            const sidebar =
                document.getElementById("sidebar");

            const overlay =
                document.getElementById("sidebarOverlay");

            const button =
                document.getElementById("hamburgerBtn");


            if (!sidebar || !overlay || !button) {
                return;
            }


            sidebar.classList.remove("open");
            overlay.classList.remove("active");

            document.body.classList.remove("menu-open");


            button.setAttribute(
                "aria-expanded",
                "false"
            );

            button.setAttribute(
                "aria-label",
                "Buka menu"
            );
        }


        /* =========================================================
           SIDEBAR MENU & ESCAPE
        ========================================================= */

        document
            .querySelectorAll(".sidebar .menu-item")
            .forEach(item => {
                item.addEventListener(
                    "click",
                    closeSidebar
                );
            });


        const logoutLink =
            document.querySelector(".logout-link");


        if (logoutLink) {
            logoutLink.addEventListener(
                "click",
                closeSidebar
            );
        }


        document.addEventListener(
            "keydown",
            event => {
                if (event.key === "Escape") {
                    closeSidebar();
                }
            }
        );


        /* =========================================================
           REFRESH FILTER WAKTU
        ========================================================= */

        function refreshTimeFilter() {
            updateWindowRange();

            const currentVisibleIds =
                getVisibleIds();


            if (
                currentVisibleIds !==
                lastVisibleIds
            ) {
                renderTable();
            }
        }


        /* =========================================================
           INITIALIZE
        ========================================================= */

        renderTable();

        setInterval(
            refreshTimeFilter,
            1000
        );
    </script>

</body>

</html>