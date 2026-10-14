<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Realisasi KA - Sistem PJL</title>

    <style>
        :root {
            --navy: #1e3156;
            --navy-hover: #2d6796;
            --bg: #f5f6f8;
            --border: #e1e5ea;
            --text: #1f2937;
            --muted: #7b8492;
            --green: #22b573;
            --red: #ef5145;
            --orange: #d9923e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            overflow-x: hidden;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--text);
            background: var(--bg);
        }

        body.menu-open {
            overflow: hidden;
        }

        button,
        input,
        select {
            font-family: inherit;
        }

        button {
            cursor: pointer;
        }

        button:disabled {
            opacity: .65;
            cursor: not-allowed;
        }

        /* HEADER */
        .top-header {
            position: fixed;
            inset: 0 0 auto;
            z-index: 3000;
            height: 60px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: white;
            background: var(--navy);
            box-shadow: 0 2px 8px #0f172a1f;
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

        .header-brand {
            gap: 10px;
        }

        .hamburger-btn {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border: 0;
            border-radius: 6px;
            color: white;
            background: transparent;
            font-size: 22px;
        }

        .hamburger-btn:hover {
            background: #ffffff1a;
        }

        .header-logo {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            overflow: hidden;
            border-radius: 6px;
            background: white;
        }

        .header-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .header-title {
            font-size: 14px;
            font-weight: 800;
        }

        .header-subtitle {
            margin-top: 3px;
            color: #cbd5e1;
            font-size: 9px;
        }

        .header-page {
            color: #cbd5e1;
            font-size: 9px;
            font-weight: 700;
        }

        .header-avatar {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            color: white;
            background: var(--orange);
            font-size: 12px;
            font-weight: 800;
        }

        /* SIDEBAR */
        .sidebar-overlay {
            position: fixed;
            inset: 0;
            z-index: 2000;
            background: #0006;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: .25s;
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 2500;
            width: 235px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            color: white;
            background: var(--navy);
            transform: translateX(-105%);
            transition: transform .25s;
            box-shadow: 4px 0 15px #0003;
        }

        .sidebar.open {
            transform: translateX(0);
        }

        .sidebar-brand {
            padding: 19px 18px;
            border-bottom: 1px solid #ffffff15;
        }

        .sidebar-title {
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
            font-size: 11px;
            transition: .15s;
        }

        .menu-item:hover,
        .menu-item.active {
            color: white;
            background: var(--navy-hover);
        }

        .menu-icon {
            width: 18px;
            display: inline-flex;
            justify-content: center;
            font-size: 15px;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding: 10px;
        }

        .profile-box {
            margin-bottom: 8px;
            padding: 11px;
            border-radius: 6px;
            background: #ffffff14;
        }

        .profile-label {
            margin-bottom: 5px;
            color: #cbd5e1;
            font-size: 8px;
            font-weight: 700;
        }

        .profile-name {
            margin-bottom: 3px;
            color: white;
            font-size: 11px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .profile-nip,
        .profile-shift {
            color: #cbd5e1;
            font-size: 9px;
            line-height: 1.6;
        }

        .logout-form {
            width: 100%;
        }

        .logout-btn {
            width: 100%;
            min-height: 40px;
            padding: 0 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #ffffff26;
            border-radius: 6px;
            color: #e2e8f0;
            background: transparent;
            font-size: 11px;
            text-align: left;
        }

        .logout-btn:hover {
            color: white;
            background: #ffffff15;
        }

        /* MAIN */
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
            background: white;
            border: 1px solid var(--border);
            border-radius: 10px;
            box-shadow: 0 2px 8px #0f172a08;
        }

        .company {
            margin-bottom: 4px;
            text-align: center;
        }

        .company-name {
            color: var(--navy);
            font-size: 12px;
            font-weight: 800;
        }

        .company-address {
            margin-top: 4px;
            color: var(--muted);
            font-size: 9px;
        }

        .page-title {
            margin-top: 12px;
            color: var(--navy);
            font-size: 20px;
            font-weight: 800;
            text-align: center;
        }

        .page-subtitle {
            margin: 5px 0 22px;
            color: #6b7280;
            font-size: 10px;
            text-align: center;
        }

        /* INFORMASI DINAS */
        .filter-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1.1fr .7fr;
            gap: 12px;
            margin-bottom: 22px;
            padding: 15px;
            background: #f8f9fb;
            border: 1px solid #e5e8ed;
            border-radius: 8px;
        }

        .field-label {
            display: block;
            margin-bottom: 6px;
            color: #536174;
            font-size: 9px;
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
            background: white;
            font-size: 11px;
        }

        .field-input:focus,
        .small-input:focus,
        .actual-time:focus,
        .note-input:focus {
            border-color: #55769a;
            box-shadow: 0 0 0 2px #55769a14;
        }

        .section-title {
            margin-bottom: 5px;
            color: var(--navy);
            font-size: 14px;
            font-weight: 800;
        }

        .section-description {
            margin-bottom: 12px;
            color: #8a93a1;
            font-size: 10px;
            line-height: 1.6;
        }

        .time-window-info {
            margin-bottom: 12px;
            padding: 11px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            color: #667085;
            background: #f8f9fb;
            border: 1px solid #e5e8ed;
            border-radius: 6px;
            font-size: 10px;
        }

        .time-window-info strong {
            color: var(--navy);
        }

        /* TABEL */
        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 8px;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            min-width: 1100px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead {
            background: var(--navy);
        }

        th {
            height: 44px;
            padding: 8px;
            color: white;
            font-size: 9px;
            font-weight: 700;
            text-align: left;
            vertical-align: middle;
            white-space: nowrap;
        }

        td {
            height: 55px;
            padding: 8px;
            border-bottom: 1px solid #edf0f3;
            color: #374151;
            font-size: 10px;
            vertical-align: middle;
            overflow-wrap: anywhere;
        }

        tbody tr:last-child td {
            border-bottom: 0;
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
            width: 13%;
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

        .actual-time,
        .note-input {
            width: 100%;
            min-width: 75px;
            height: 32px;
            padding: 0 7px;
            border: 1px solid #e0e4e9;
            border-radius: 5px;
            outline: none;
            color: #374151;
            background: #f8f9fb;
            font-size: 10px;
        }

        .status-select {
            width: 100%;
            min-width: 68px;
            height: 30px;
            padding: 0 5px;
            border: 0;
            border-radius: 15px;
            color: white;
            font-size: 9px;
            font-weight: 700;
            text-align: center;
        }

        .status-jalan {
            background: #22b573;
        }

        .status-batal {
            background: #ef5145;
        }

        .status-belum {
            color: #68758a;
            background: #dfe3e8;
        }

        .check-group,
        .mini-checks {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .check,
        .mini-check {
            width: 27px;
            height: 27px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: 1px solid #d5dce4;
            border-radius: 5px;
            background: white;
            font-size: 13px;
            font-weight: 700;
            transition: .15s;
        }

        .check:hover,
        .mini-check:hover {
            transform: scale(1.04);
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
            color: #aeb7c3;
            background: #f7f8fa;
        }

        /* TAMBAH KA MANUAL */
        .add-section,
        .summary-section {
            margin-top: 22px;
        }

        .add-box,
        .summary-box {
            padding: 16px;
            background: #f8f9fb;
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .add-heading {
            margin-bottom: 14px;
            color: var(--navy);
            font-size: 12px;
            font-weight: 800;
        }

        .add-grid {
            display: grid;
            grid-template-columns: .9fr 1.1fr .8fr .8fr .8fr .9fr .9fr 1.2fr;
            gap: 10px;
        }

        .small-field label {
            display: block;
            margin-bottom: 6px;
            color: #647084;
            font-size: 9px;
            font-weight: 700;
        }

        .small-input {
            width: 100%;
            height: 34px;
            padding: 0 8px;
            border: 1px solid #e0e4e9;
            border-radius: 5px;
            outline: none;
            color: #374151;
            background: white;
            font-size: 10px;
        }

        .add-button {
            min-height: 36px;
            margin-top: 13px;
            padding: 0 15px;
            border: 1px solid var(--navy);
            border-radius: 5px;
            color: var(--navy);
            background: white;
            font-size: 10px;
            font-weight: 700;
        }

        .add-button:hover {
            color: white;
            background: var(--navy);
        }

        /* RINGKASAN */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .summary-card {
            min-height: 75px;
            padding: 12px;
            background: white;
            border: 1px solid #e4e7eb;
            border-radius: 7px;
        }

        .summary-label {
            margin-bottom: 7px;
            color: #758092;
            font-size: 9px;
            line-height: 1.4;
        }

        .summary-number {
            color: var(--navy);
            font-size: 22px;
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
            justify-content: space-between;
            margin: 15px 0 7px;
            color: #536174;
            font-size: 10px;
            font-weight: 700;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
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
            margin-top: 7px;
            color: #9aa2ad;
            font-size: 9px;
        }

        /* TOMBOL BAWAH */
        .bottom-row {
            margin-top: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .digital-note {
            color: #8b95a4;
            font-size: 9px;
            line-height: 1.6;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-btn {
            min-height: 37px;
            padding: 0 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            font-size: 10px;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-outline {
            border: 1px solid var(--navy);
            color: var(--navy);
            background: white;
        }

        .btn-dark {
            border: 1px solid var(--navy);
            color: white;
            background: var(--navy);
        }

        .btn-orange {
            border: 1px solid var(--orange);
            color: white;
            background: var(--orange);
        }

        .btn-outline:hover {
            background: #f3f6fa;
        }

        .btn-dark:hover {
            background: #263e6a;
        }

        .btn-orange:hover {
            background: #c98232;
        }

        /* RESPONSIVE */
        @media (max-width: 1100px) {
            .main {
                padding-right: 18px;
                padding-left: 18px;
            }

            .filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .add-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .summary-cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .bottom-row {
                flex-direction: column;
                align-items: stretch;
            }

            .actions {
                flex-wrap: wrap;
                justify-content: flex-end;
            }

            .time-window-info {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 700px) {
            .top-header {
                height: 64px;
                padding: 0 12px;
            }

            .header-logo {
                width: 34px;
                height: 34px;
            }

            .header-title {
                font-size: 13px;
            }

            .header-subtitle {
                font-size: 8px;
            }

            .header-page {
                display: none;
            }

            .sidebar {
                max-width: 82vw;
            }

            .main {
                padding: 76px 10px 25px;
            }

            .page-card {
                padding: 16px 12px 20px;
            }

            .company-name {
                font-size: 10px;
                line-height: 1.5;
            }

            .page-title {
                font-size: 16px;
                line-height: 1.4;
            }

            .page-subtitle {
                font-size: 9px;
                line-height: 1.5;
            }

            .filter-grid,
            .add-grid {
                grid-template-columns: 1fr;
            }

            .field-input {
                height: 39px;
                font-size: 12px;
            }

            table {
                width: 1180px;
                min-width: 1180px;
            }

            th,
            td {
                font-size: 9px;
            }

            .actual-time,
            .note-input {
                height: 34px;
                font-size: 11px;
            }

            .status-select {
                height: 32px;
                font-size: 9px;
            }

            .check {
                width: 29px;
                height: 29px;
            }

            .small-input {
                height: 39px;
                font-size: 12px;
            }

            .mini-check {
                width: 34px;
                height: 34px;
                font-size: 14px;
            }

            .add-button {
                width: 100%;
                min-height: 42px;
                font-size: 11px;
            }

            .actions {
                flex-direction: column;
                align-items: stretch;
            }

            .action-btn {
                width: 100%;
                min-height: 42px;
                white-space: normal;
                text-align: center;
            }

            .digital-note {
                font-size: 9px;
            }
        }

        @media (max-width: 400px) {
            .main {
                padding: 74px 7px 20px;
            }

            .page-card {
                padding: 13px 9px 16px;
            }

            .page-title {
                font-size: 14px;
            }

            .summary-cards {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            body {
                background: white;
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
                border: 0;
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
                padding: 4px;
                font-size: 7px;
            }

            .actual-time,
            .status-select,
            .note-input {
                border: 0;
                background: transparent;
            }

            .check {
                border: 0;
            }
        }

        
/* =====================================================
   PERBAIKAN TAMPILAN DAN RESPONSIVITAS REALISASI KA
   Letakkan di bagian paling bawah CSS
===================================================== */

/* Ukuran dasar */
html {
    font-size: 16px;
    -webkit-text-size-adjust: 100%;
}

body {
    font-size: 14px;
    line-height: 1.5;
}

/* Header */
.top-header {
    height: 68px;
    padding: 0 28px;
}

.header-left {
    gap: 14px;
}

.hamburger-btn {
    width: 42px;
    height: 42px;
    font-size: 25px;
}

.header-logo {
    width: 44px;
    height: 44px;
}

.header-title {
    font-size: 18px;
}

.header-subtitle {
    font-size: 12px;
}

.header-page {
    font-size: 12px;
}

.header-avatar {
    width: 42px;
    height: 42px;
    font-size: 15px;
}

/* Konten utama */
.main {
    padding: 92px 32px 40px;
}

.page-card {
    max-width: 1700px;
    padding: 32px;
    border-radius: 14px;
}

.company-name {
    font-size: 16px;
}

.company-address {
    font-size: 12px;
}

.page-title {
    margin-top: 16px;
    font-size: clamp(22px, 2vw, 30px);
    line-height: 1.35;
}

.page-subtitle {
    margin: 8px 0 28px;
    font-size: 14px;
}

/* Informasi dinas */
.filter-grid {
    gap: 16px;
    margin-bottom: 28px;
    padding: 20px;
    border-radius: 10px;
}

.field-label {
    margin-bottom: 8px;
    font-size: 12px;
}

.field-input {
    height: 46px;
    padding: 0 13px;
    border-radius: 7px;
    font-size: 14px;
}

/* Judul dan keterangan bagian */
.section-title {
    margin-bottom: 8px;
    font-size: 19px;
}

.section-description {
    margin-bottom: 16px;
    font-size: 13px;
    line-height: 1.7;
}

/* Informasi rentang waktu */
.time-window-info {
    margin-bottom: 16px;
    padding: 14px 16px;
    gap: 12px;
    border-radius: 8px;
    font-size: 12px;
    line-height: 1.6;
}

/* Tabel */
.table-wrapper {
    border-radius: 10px;
}

table {
    min-width: 1250px;
}

th {
    height: 54px;
    padding: 12px 10px;
    font-size: 11px;
    line-height: 1.4;
}

td {
    height: 66px;
    padding: 10px;
    font-size: 13px;
    line-height: 1.5;
}

.actual-time,
.note-input {
    min-width: 90px;
    height: 40px;
    padding: 0 9px;
    border-radius: 7px;
    font-size: 13px;
}

.status-select {
    min-width: 82px;
    height: 38px;
    padding: 0 7px;
    font-size: 11px;
}

.check {
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    border-radius: 7px;
    font-size: 16px;
}

.check-group {
    gap: 5px;
}

/* Tambah KA manual */
.add-section,
.summary-section {
    margin-top: 28px;
}

.add-box,
.summary-box {
    padding: 22px;
    border-radius: 10px;
}

.add-heading {
    margin-bottom: 18px;
    font-size: 17px;
}

.add-grid {
    gap: 14px;
}

.small-field label {
    margin-bottom: 8px;
    font-size: 11px;
    line-height: 1.5;
}

.small-input {
    height: 42px;
    padding: 0 10px;
    border-radius: 7px;
    font-size: 13px;
}

.mini-checks {
    min-height: 42px;
    gap: 7px;
}

.mini-check {
    width: 34px;
    height: 34px;
    flex-shrink: 0;
    font-size: 16px;
}

.add-button {
    min-height: 44px;
    margin-top: 18px;
    padding: 0 20px;
    border-radius: 7px;
    font-size: 13px;
}

/* Ringkasan */
.summary-cards {
    gap: 16px;
}

.summary-card {
    min-height: 100px;
    padding: 18px;
    border-radius: 9px;
}

.summary-label {
    margin-bottom: 10px;
    font-size: 12px;
}

.summary-number {
    font-size: 30px;
}

.progress-title {
    margin: 20px 0 10px;
    font-size: 13px;
}

.progress-bar {
    height: 10px;
}

.progress-description {
    margin-top: 10px;
    font-size: 12px;
    line-height: 1.6;
}

/* Tombol aksi */
.bottom-row {
    margin-top: 28px;
    gap: 20px;
}

.digital-note {
    font-size: 12px;
}

.actions {
    gap: 10px;
}

.action-btn {
    min-height: 44px;
    padding: 0 17px;
    border-radius: 7px;
    font-size: 12px;
}

/* Tablet */
@media (max-width: 1100px) {
    .main {
        padding: 88px 20px 30px;
    }

    .page-card {
        padding: 24px;
    }

    .filter-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .add-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .table-wrapper {
        max-width: 100%;
        overflow-x: auto;
        overscroll-behavior-x: contain;
    }

    table {
        min-width: 1250px;
    }

    .bottom-row {
        align-items: stretch;
    }
}

/* HP */
@media (max-width: 700px) {
    .top-header {
        height: 60px;
        padding: 0 12px;
    }

    .header-left {
        gap: 8px;
        min-width: 0;
    }

    .hamburger-btn {
        width: 36px;
        height: 36px;
        font-size: 22px;
    }

    .header-logo {
        width: 36px;
        height: 36px;
    }

    .header-title {
        font-size: 15px;
    }

    .header-subtitle {
        font-size: 10px;
    }

    .header-avatar {
        width: 36px;
        height: 36px;
    }

    .main {
        padding: 74px 10px 24px;
    }

    .page-card {
        padding: 20px 14px;
        border-radius: 10px;
    }

    .company-name {
        font-size: 13px;
    }

    .company-address {
        font-size: 10px;
        line-height: 1.6;
    }

    .page-title {
        font-size: 21px;
    }

    .page-subtitle {
        margin-bottom: 22px;
        font-size: 12px;
    }

    .filter-grid {
        grid-template-columns: minmax(0, 1fr);
        gap: 14px;
        padding: 15px;
    }

    .field-label {
        font-size: 12px;
    }

    .field-input {
        height: 44px;
        font-size: 14px;
    }

    .section-title {
        font-size: 17px;
    }

    .section-description {
        font-size: 12px;
    }

    .time-window-info {
        padding: 12px;
        font-size: 12px;
        overflow-wrap: anywhere;
    }

    .table-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    table {
        width: 1250px;
        min-width: 1250px;
        table-layout: fixed;
    }

    th {
        height: 48px;
        padding: 10px;
        font-size: 11px;
    }

    td {
        height: 60px;
        padding: 9px;
        font-size: 12px;
    }

    .actual-time,
    .note-input {
        height: 40px;
        font-size: 13px;
    }

    .status-select {
        height: 38px;
        font-size: 11px;
    }

    .check {
        width: 32px;
        height: 32px;
    }

    .add-box,
    .summary-box {
        padding: 16px;
    }

    .add-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px 12px;
    }

    .small-field label {
        font-size: 11px;
    }

    .small-input {
        height: 42px;
        font-size: 13px;
    }

    .mini-checks {
        justify-content: flex-start;
    }

    .add-button {
        width: 100%;
    }

    .summary-cards {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .summary-card {
        min-height: 90px;
        padding: 13px;
    }

    .summary-label {
        font-size: 11px;
    }

    .summary-number {
        font-size: 26px;
    }

    .bottom-row {
        flex-direction: column;
        align-items: stretch;
    }

    .actions {
        flex-direction: column;
        align-items: stretch;
    }

    .action-btn {
        width: 100%;
        min-height: 44px;
        white-space: normal;
        text-align: center;
    }
}

/* HP sangat kecil */
@media (max-width: 380px) {
    .main {
        padding-right: 7px;
        padding-left: 7px;
    }

    .page-card {
        padding: 16px 10px;
    }

    .page-title {
        font-size: 18px;
    }

    .add-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .summary-cards {
        grid-template-columns: minmax(0, 1fr);
    }
}

/* Menghormati pengaturan aksesibilitas */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        scroll-behavior: auto !important;
        transition-duration: 0.01ms !important;
        animation-duration: 0.01ms !important;
    }
}


/* ===== PERBAIKAN UKURAN SIDEBAR REALISASI KA ===== */

.sidebar {
    width: 270px;
}

.sidebar-brand {
    padding: 24px 20px;
}

.sidebar-title {
    font-size: 17px;
}

.sidebar-subtitle {
    font-size: 11px;
}

.sidebar-menu {
    padding: 18px 12px;
}

.menu-item {
    min-height: 48px;
    margin-bottom: 6px;
    padding: 0 14px;
    gap: 12px;
    border-radius: 8px;
    font-size: 14px;
}

.menu-icon {
    width: 22px;
    font-size: 19px;
    flex-shrink: 0;
}

.sidebar-bottom {
    padding: 12px;
}

.profile-box {
    padding: 14px;
    border-radius: 8px;
}

.profile-label {
    font-size: 10px;
}

.profile-name {
    font-size: 14px;
}

.profile-nip,
.profile-shift {
    font-size: 11px;
    line-height: 1.7;
}

.logout-btn {
    min-height: 46px;
    padding: 0 14px;
    gap: 12px;
    border-radius: 8px;
    font-size: 13px;
}

/* Sidebar di HP */
@media (max-width: 700px) {
    .sidebar {
        width: min(290px, 85vw);
        max-width: 85vw;
    }

    .sidebar-title {
        font-size: 16px;
    }

    .menu-item {
        min-height: 46px;
        font-size: 14px;
    }

    .profile-name {
        font-size: 13px;
    }
}

    </style>
</head>

<body>

    <!-- HEADER -->
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
                    <img src="{{ asset('images/logo-kai.jpg') }}" alt="Logo KAI">
                </div>

                <div>
                    <div class="header-title">Sistem PJL</div>
                    <div class="header-subtitle">Realisasi KA</div>
                </div>
            </div>
        </div>

        <div class="header-right">
            <div class="header-page">REALISASI KA</div>
            <div class="header-avatar">
                {{ strtoupper(substr(auth()->user()->name ?? 'P', 0, 1)) }}
            </div>
        </div>
    </header>

    <!-- OVERLAY SIDEBAR -->
    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
        onclick="closeSidebar()">
    </div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-title">KAI OPERASIONAL</div>
            <div class="sidebar-subtitle">SISTEM DIGITAL PJL</div>
        </div>

        <nav class="sidebar-menu">
            <a href="{{ url('/user/dashboard') }}" class="menu-item">
                <span class="menu-icon">⌂</span>
                <span>Dashboard</span>
            </a>

            <a href="{{ url('/user/realisasi-ka') }}" class="menu-item active">
                <span class="menu-icon">▣</span>
                <span>Realisasi KA</span>
            </a>

            <a href="{{ url('/user/serah-terima-dinas') }}" class="menu-item">
                <span class="menu-icon">⇄</span>
                <span>Serah Terima Dinas</span>
            </a>

            <a href="{{ url('/user/catatan-dinas') }}" class="menu-item">
                <span class="menu-icon">▤</span>
                <span>Catatan Dinas</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="profile-box">
                <div class="profile-label">PENGGUNA LOGIN</div>

                <div class="profile-name">
                    {{ auth()->user()->name ?? 'Pengguna PJL' }}
                </div>

                <div class="profile-nip">
                    @if (auth()->user() && auth()->user()->nip)
                        NIP: {{ auth()->user()->nip }}
                    @else
                        Akun PJL
                    @endif
                </div>

                <div class="profile-shift">
                    @if ($dinasAktif ?? false)
                        Dinas aktif ·
                        {{ \Carbon\Carbon::parse($dinasAktif->tanggal_dinas)->format('d-m-Y') }}
                    @else
                        Tidak ada dinas aktif
                    @endif
                </div>
            </div>

            <form action="{{ route('logout') }}" method="POST" class="logout-form">
                @csrf
                <button type="submit" class="logout-btn">
                    <span>↪</span>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="main">
        <section class="page-card">

            <div class="company">
                <div class="company-name">PT KERETA API INDONESIA (PERSERO)</div>
                <div class="company-address">DIREKTORAT KESELAMATAN DAN KEAMANAN</div>
            </div>

            <h1 class="page-title">REALISASI KA YANG LEWAT JPL</h1>

            <div class="page-subtitle">
                Buku Catatan Harian Penjagaan Jalan Perlintasan
            </div>

            <!-- INFORMASI DINAS -->
            <div class="filter-grid">
                <div>
                    <label for="tanggal" class="field-label">HARI / TANGGAL</label>

                    <input
                        type="text"
                        id="tanggal"
                        class="field-input"
                        value="{{ ($dinasAktif ?? null)
                            ? \Carbon\Carbon::parse($dinasAktif->tanggal_dinas)->locale('id')->translatedFormat('l, d F Y')
                            : \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y') }}"
                        readonly>
                </div>

                <div>
                    <label for="nomorJpl" class="field-label">NOMOR JPL</label>
                    <input
                        id="nomorJpl"
                        type="text"
                        class="field-input"
                        value="{{ ($dinasAktif ?? null) ? ($dinasAktif->jpl?->kode_jpl ?? '-') : '-' }}"
                        readonly>
                </div>

                <div>
                    <label for="stasiunRegional" class="field-label">STASIUN / REGIONAL</label>
                    <input
                        id="stasiunRegional"
                        type="text"
                        class="field-input"
                        value="{{ ($dinasAktif ?? null) ? ($dinasAktif->jpl?->nama_jpl ?? '-') : '-' }}"
                        readonly>
                </div>

                <div>
                    <label for="shift" class="field-label">SHIFT</label>
                    <input
                        id="shift"
                        type="text"
                        class="field-input"
                        value="{{ ($dinasAktif ?? null) ? ($dinasAktif->shift?->nama_shift ?? '-') : '-' }}"
                        readonly>
                </div>
            </div>

            <!-- DAFTAR KA -->
            <div class="section-title">Daftar KA Terjadwal</div>

            <div class="section-description">
                Jadwal diambil dari database untuk JPL yang sedang dijaga.
                KA terjadwal hanya ditampilkan dalam rentang ±1 jam dari waktu WIB sekarang.
                KA manual tanpa jadwal selalu ditampilkan.
            </div>

            <div class="time-window-info">
                <span>
                    Filter jadwal: <strong>±1 jam dari waktu sekarang</strong>.
                    KA manual tetap ditampilkan.
                </span>

                <span id="windowRange">Memuat waktu...</span>
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

            <!-- TAMBAH KA MANUAL -->
            <section class="add-section">
                <div class="add-box">
                    <div class="add-heading">Tambah KA Manual</div>

                    <div class="add-grid">
                        <div class="small-field">
                            <label for="manualNomor">NOMOR KA</label>
                            <input
                                id="manualNomor"
                                type="text"
                                class="small-input"
                                placeholder="Nomor KA">
                        </div>

                        <div class="small-field">
                            <label for="manualNama">NAMA KA</label>
                            <input
                                id="manualNama"
                                type="text"
                                class="small-input"
                                placeholder="Nama KA">
                        </div>

                        <div class="small-field">
                            <label for="manualAktual">JAM AKTUAL</label>
                            <input
                                id="manualAktual"
                                type="time"
                                class="small-input">
                        </div>

                        <div class="small-field">
                            <label>SEMBOYAN 2.1</label>
                            <div class="mini-checks">
                                <button type="button" class="mini-check inactive"
                                    data-group="manual21" data-value="yes"
                                    onclick="setManualCheck(this)">✓</button>
                                <button type="button" class="mini-check inactive"
                                    data-group="manual21" data-value="no"
                                    onclick="setManualCheck(this)">×</button>
                            </div>
                        </div>

                        <div class="small-field">
                            <label>GENTA</label>
                            <div class="mini-checks">
                                <button type="button" class="mini-check inactive"
                                    data-group="manualGenta" data-value="yes"
                                    onclick="setManualCheck(this)">✓</button>
                                <button type="button" class="mini-check inactive"
                                    data-group="manualGenta" data-value="no"
                                    onclick="setManualCheck(this)">×</button>
                            </div>
                        </div>

                        <div class="small-field">
                            <label>PINTU PERLINTASAN</label>
                            <div class="mini-checks">
                                <button type="button" class="mini-check inactive"
                                    data-group="manualPintu" data-value="yes"
                                    onclick="setManualCheck(this)">✓</button>
                                <button type="button" class="mini-check inactive"
                                    data-group="manualPintu" data-value="no"
                                    onclick="setManualCheck(this)">×</button>
                            </div>
                        </div>

                        <div class="small-field">
                            <label>SEMBOYAN 3.5</label>
                            <div class="mini-checks">
                                <button type="button" class="mini-check inactive"
                                    data-group="manual35" data-value="yes"
                                    onclick="setManualCheck(this)">✓</button>
                                <button type="button" class="mini-check inactive"
                                    data-group="manual35" data-value="no"
                                    onclick="setManualCheck(this)">×</button>
                            </div>
                        </div>

                        <div class="small-field">
                            <label for="manualKeterangan">KETERANGAN</label>
                            <input
                                id="manualKeterangan"
                                type="text"
                                class="small-input"
                                placeholder="Keterangan">
                        </div>
                    </div>

                    <button type="button" class="add-button" onclick="tambahKAManual()">
                        ＋ Tambah KA Manual
                    </button>
                </div>
            </section>

            <!-- RINGKASAN -->
            <section class="summary-section">
                <div class="section-title">Ringkasan Realisasi Shift</div>

                <div class="section-description">
                    Ringkasan otomatis berdasarkan KA yang sedang tampil pada tabel.
                </div>

                <div class="summary-box">
                    <div class="summary-cards">
                        <div class="summary-card">
                            <div class="summary-label">TOTAL KA</div>
                            <div id="totalKA" class="summary-number">0</div>
                        </div>

                        <div class="summary-card">
                            <div class="summary-label">KA SUDAH LEWAT (JALAN)</div>
                            <div id="jalanKA" class="summary-number green">0</div>
                        </div>

                        <div class="summary-card">
                            <div class="summary-label">KA BATAL</div>
                            <div id="batalKA" class="summary-number red">0</div>
                        </div>

                        <div class="summary-card">
                            <div class="summary-label">KA BELUM DIPROSES</div>
                            <div id="belumKA" class="summary-number">0</div>
                        </div>
                    </div>

                    <div class="progress-title">
                        <span>Progress realisasi</span>
                        <span id="progressText">0%</span>
                    </div>

                    <div class="progress-bar">
                        <div id="progressGreen" class="progress-green" style="width: 0%"></div>
                        <div id="progressRed" class="progress-red" style="width: 0%"></div>
                        <div id="progressGray" class="progress-gray" style="width: 100%"></div>
                    </div>

                    <div class="progress-description">
                        Progress dihitung dari jumlah KA yang sudah berstatus JALAN atau BATAL.
                    </div>
                </div>
            </section>

            <!-- AKSI -->
            <div class="bottom-row">
                <div class="digital-note">
                    Formulir realisasi digital untuk pencatatan dinas PJL.
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
                        id="simpanBtn"
                        class="action-btn btn-dark"
                        onclick="simpanRealisasi()">
                        ▣ &nbsp; Simpan Realisasi
                    </button>

                    <a
                        href="{{ url('/user/serah-terima-dinas') }}"
                        class="action-btn btn-orange">
                        Selesai &amp; Generate Laporan Serah Terima
                    </a>
                </div>
            </div>

        </section>
    </main>

    <script>
        /* =====================================================
           DATA DARI CONTROLLER LARAVEL
        ===================================================== */

        let dataKA = @json($dataKA ?? []);

        dataKA = dataKA.map((ka, index) => ({
            no: ka.no ?? index + 1,
            nomor: ka.nomor ?? '',
            nama: ka.nama ?? '',
            jadwal: ka.jadwal ?? '-',
            aktual: ka.aktual ?? '',
            status: ['JALAN', 'BATAL', 'BELUM'].includes(ka.status)
                ? ka.status
                : 'BELUM',
            semboyan21: ka.semboyan21 ?? '',
            genta: ka.genta ?? '',
            pintu: ka.pintu ?? '',
            semboyan35: ka.semboyan35 ?? '',
            keterangan: ka.keterangan ?? '',
            jadwal_ka_id: ka.jadwal_ka_id ?? null,

            // ID realisasi dari database untuk mempertahankan identitas data.
            realisasi_ka_id: ka.realisasi_ka_id ?? null,

            is_manual: Boolean(ka.is_manual)
        }));

        let manualCheck = {
            semboyan21: '',
            genta: '',
            pintu: '',
            semboyan35: ''
        };

        /* ESCAPE HTML */
        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        /* WAKTU WIB */
        function getWIBTime() {
            const parts = new Intl.DateTimeFormat('en-GB', {
                timeZone: 'Asia/Jakarta',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            }).formatToParts(new Date());

            const result = {};

            parts.forEach(part => {
                if (['hour', 'minute', 'second'].includes(part.type)) {
                    result[part.type] = Number(part.value);
                }
            });

            return result;
        }

        function getCurrentMinutes() {
            const now = getWIBTime();
            return now.hour * 60 + now.minute;
        }

        function timeToMinutes(time) {
            if (!time || time === '-') {
                return null;
            }

            const parts = String(time).split(':');

            if (parts.length < 2) {
                return null;
            }

            const hours = Number(parts[0]);
            const minutes = Number(parts[1]);

            if (
                !Number.isInteger(hours) ||
                !Number.isInteger(minutes) ||
                hours < 0 || hours > 23 ||
                minutes < 0 || minutes > 59
            ) {
                return null;
            }

            return hours * 60 + minutes;
        }

        function formatMinutes(totalMinutes) {
            totalMinutes = (totalMinutes + 1440) % 1440;

            return String(Math.floor(totalMinutes / 60)).padStart(2, '0')
                + ':'
                + String(totalMinutes % 60).padStart(2, '0');
        }

        function updateWindowRange() {
            const element = document.getElementById('windowRange');

            if (!element) {
                return;
            }

            const now = getWIBTime();
            const currentMinutes = now.hour * 60 + now.minute;

            const currentTime =
                String(now.hour).padStart(2, '0') + ':' +
                String(now.minute).padStart(2, '0') + ':' +
                String(now.second).padStart(2, '0');

            element.textContent =
                `${currentTime} WIB · Rentang ` +
                `${formatMinutes(currentMinutes - 60)} – ` +
                `${formatMinutes(currentMinutes + 60)}`;
        }

        /*
         * KA terjadwal tampil jika jadwalnya berjarak maksimal
         * 60 menit dari waktu sekarang.
         *
         * KA manual selalu tampil.
         *
         * Filter ini hanya memengaruhi tampilan tabel.
         * Semua data dalam dataKA tetap dikirim saat disimpan.
         */
        function isWithinOneHour(ka) {
            if (ka.is_manual || !ka.jadwal || ka.jadwal === '-') {
                return true;
            }

            const jadwalMinutes = timeToMinutes(ka.jadwal);

            if (jadwalMinutes === null) {
                return false;
            }

            let difference = Math.abs(jadwalMinutes - getCurrentMinutes());

            // Menangani pergantian hari, misalnya 23:45 dan 00:15.
            difference = Math.min(difference, 1440 - difference);

            return difference <= 60;
        }

        function getVisibleKA() {
            return dataKA
                .map((ka, index) => ({ ka, index }))
                .filter(item => isWithinOneHour(item.ka));
        }

        /* TOMBOL SEMBOYAN */
        function createCheckGroup(value, index, field) {
            const yesClass = value === 'yes' ? 'active-yes' : 'inactive';
            const noClass = value === 'no' ? 'active-no' : 'inactive';

            return `
                <div class="check-group">
                    <button type="button"
                        class="check ${yesClass}"
                        onclick="setCheck(this, ${index}, '${field}', 'yes')"
                        aria-label="Ya"
                        title="Ya">✓</button>

                    <button type="button"
                        class="check ${noClass}"
                        onclick="setCheck(this, ${index}, '${field}', 'no')"
                        aria-label="Tidak"
                        title="Tidak">×</button>
                </div>
            `;
        }

        /* STATUS KA */
        function getStatusClass(status) {
            if (status === 'JALAN') return 'status-jalan';
            if (status === 'BATAL') return 'status-batal';
            return 'status-belum';
        }

        function createStatus(status, index) {
            return `
                <select class="status-select ${getStatusClass(status)}"
                    onchange="updateStatus(this, ${index})">
                    <option value="JALAN" ${status === 'JALAN' ? 'selected' : ''}>JALAN</option>
                    <option value="BATAL" ${status === 'BATAL' ? 'selected' : ''}>BATAL</option>
                    <option value="BELUM" ${status === 'BELUM' ? 'selected' : ''}>BELUM</option>
                </select>
            `;
        }

        /* RENDER TABEL */
        function renderTable() {
            const tbody = document.getElementById('kaTableBody');

            if (!tbody) {
                return;
            }

            const visibleKA = getVisibleKA();
            tbody.innerHTML = '';

            if (visibleKA.length === 0) {
                const message = dataKA.length === 0
                    ? 'Belum ada jadwal KA untuk dinas aktif ini.'
                    : 'Tidak ada KA dalam rentang waktu ±1 jam dari waktu sekarang.';

                tbody.innerHTML = `
                    <tr>
                        <td colspan="11"
                            style="height:85px;text-align:center;color:#8a93a1">
                            ${escapeHtml(message)}
                        </td>
                    </tr>
                `;

                updateSummary();
                updateWindowRange();
                return;
            }

            visibleKA.forEach(({ ka, index }, visibleIndex) => {
                const row = document.createElement('tr');
                row.dataset.index = index;

                row.innerHTML = `
                    <td>${visibleIndex + 1}</td>

                    <td>
                        <strong>${escapeHtml(ka.nomor)}</strong>
                        ${ka.is_manual
                            ? '<div style="margin-top:3px;color:#d9923e;font-size:8px">MANUAL</div>'
                            : ''}
                    </td>

                    <td>${escapeHtml(ka.nama)}</td>
                    <td>${escapeHtml(ka.jadwal)}</td>

                    <td>
                        <input type="time"
                            class="actual-time"
                            value="${escapeHtml(ka.aktual)}"
                            onchange="updateActualTime(${index}, this.value)">
                    </td>

                    <td>${createStatus(ka.status, index)}</td>

                    <td>${createCheckGroup(ka.semboyan21, index, 'semboyan21')}</td>
                    <td>${createCheckGroup(ka.genta, index, 'genta')}</td>
                    <td>${createCheckGroup(ka.pintu, index, 'pintu')}</td>
                    <td>${createCheckGroup(ka.semboyan35, index, 'semboyan35')}</td>

                    <td>
                        <input type="text"
                            class="note-input"
                            value="${escapeHtml(ka.keterangan)}"
                            placeholder="Keterangan"
                            onchange="updateKeterangan(${index}, this.value)">
                    </td>
                `;

                tbody.appendChild(row);
            });

            updateSummary();
            updateWindowRange();
        }

        /* UPDATE JAM AKTUAL */
        function updateActualTime(index, value) {
            if (!dataKA[index]) {
                return;
            }

            dataKA[index].aktual = value;
        }

        /* UPDATE STATUS */
        function updateStatus(select, index) {
            if (!dataKA[index]) {
                return;
            }

            const status = select.value;
            dataKA[index].status = status;

            select.className = `status-select ${getStatusClass(status)}`;

            /*
             * Jika status dibatalkan, semua indikator semboyan
             * otomatis berubah menjadi tidak (×).
             */
            if (status === 'BATAL') {
                const fields = [
                    'semboyan21',
                    'genta',
                    'pintu',
                    'semboyan35'
                ];

                fields.forEach(field => {
                    dataKA[index][field] = 'no';
                });

                const row = select.closest('tr');

                if (row) {
                    row.querySelectorAll('.check-group').forEach(group => {
                        const buttons = group.querySelectorAll('.check');

                        buttons.forEach(button => {
                            button.classList.remove('active-yes', 'active-no');
                            button.classList.add('inactive');
                        });

                        if (buttons[1]) {
                            buttons[1].classList.remove('inactive');
                            buttons[1].classList.add('active-no');
                        }
                    });
                }
            }

            updateSummary();
        }

        /* UPDATE SEMBOYAN */
        function setCheck(button, index, field, value) {
            if (!dataKA[index]) {
                return;
            }

            const group = button.parentElement;

            group.querySelectorAll('.check').forEach(item => {
                item.classList.remove('active-yes', 'active-no');
                item.classList.add('inactive');
            });

            button.classList.remove('inactive');
            button.classList.add(
                value === 'yes' ? 'active-yes' : 'active-no'
            );

            dataKA[index][field] = value;
        }

        /* UPDATE KETERANGAN */
        function updateKeterangan(index, value) {
            if (!dataKA[index]) {
                return;
            }

            dataKA[index].keterangan = value;
        }

        /* RINGKASAN BERDASARKAN KA YANG TAMPIL */
        function updateSummary() {
            const visibleData = getVisibleKA().map(item => item.ka);

            const total = visibleData.length;
            const jalan = visibleData.filter(ka => ka.status === 'JALAN').length;
            const batal = visibleData.filter(ka => ka.status === 'BATAL').length;
            const belum = visibleData.filter(ka => ka.status === 'BELUM').length;

            document.getElementById('totalKA').textContent = total;
            document.getElementById('jalanKA').textContent = jalan;
            document.getElementById('batalKA').textContent = batal;
            document.getElementById('belumKA').textContent = belum;

            const selesai = jalan + batal;
            const progress = total
                ? Math.round((selesai / total) * 100)
                : 0;

            document.getElementById('progressText').textContent = `${progress}%`;

            document.getElementById('progressGreen').style.width =
                `${total ? (jalan / total) * 100 : 0}%`;

            document.getElementById('progressRed').style.width =
                `${total ? (batal / total) * 100 : 0}%`;

            document.getElementById('progressGray').style.width =
                `${total ? (belum / total) * 100 : 0}%`;
        }

        /* SIMPAN REALISASI KE DATABASE */
        async function simpanRealisasi() {
            const tombol = document.getElementById('simpanBtn');

            if (dataKA.length === 0) {
                alert('Belum ada data KA untuk disimpan.');
                return;
            }

            tombol.disabled = true;
            tombol.textContent = 'Menyimpan...';

            try {
                /*
                 * Seluruh dataKA dikirim, bukan hanya data yang terlihat
                 * pada filter ±1 jam.
                 */
                const response = await fetch(
                    @json(route('user.realisasi-ka.store')),
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector(
                                'meta[name="csrf-token"]'
                            ).content
                        },
                        body: JSON.stringify({
                            data: dataKA
                        })
                    }
                );

                const contentType = response.headers.get('content-type') || '';

                if (!contentType.includes('application/json')) {
                    if (response.status === 419) {
                        throw new Error(
                            'Sesi berakhir atau token CSRF tidak valid. Muat ulang halaman, lalu coba lagi.'
                        );
                    }

                    if (response.status === 401 || response.status === 403) {
                        throw new Error(
                            'Akses ditolak. Silakan login kembali dan periksa hak akses akun.'
                        );
                    }

                    throw new Error(
                        'Server tidak mengembalikan respons JSON. Periksa route dan log Laravel.'
                    );
                }

                const result = await response.json();

                if (!response.ok) {
                    const pesanValidasi = result.errors
                        ? Object.values(result.errors).flat().join('\n')
                        : result.message;

                    throw new Error(
                        pesanValidasi || 'Gagal menyimpan realisasi KA.'
                    );
                }

                /*
                 * Backend diharapkan mengembalikan:
                 * {
                 *   message: "...",
                 *   data: [
                 *     {
                 *       index: 0,
                 *       realisasi_ka_id: 123
                 *     }
                 *   ]
                 * }
                 *
                 * ID yang dikembalikan disimpan pada dataKA di browser.
                 * Dengan begitu, penyimpanan berikutnya bisa mengenali
                 * record realisasi yang sama.
                 */
                if (Array.isArray(result.data)) {
                    result.data.forEach(item => {
                        const index = Number(item.index);

                        if (
                            Number.isInteger(index) &&
                            index >= 0 &&
                            dataKA[index] &&
                            item.realisasi_ka_id !== undefined &&
                            item.realisasi_ka_id !== null
                        ) {
                            dataKA[index].realisasi_ka_id =
                                item.realisasi_ka_id;
                        }
                    });
                }

                alert(
                    result.message ||
                    'Realisasi KA berhasil disimpan ke database.'
                );

            } catch (error) {
                console.error('Gagal menyimpan realisasi:', error);

                alert(
                    'Realisasi belum berhasil disimpan.\n\n' +
                    error.message
                );
            } finally {
                tombol.disabled = false;
                tombol.textContent = '▣  Simpan Realisasi';
            }
        }

        /* CETAK DOKUMEN */
        function cetakDokumen() {
            window.print();
        }

        /* CHECK MANUAL */
        function setManualCheck(button) {
            const group = button.dataset.group;
            const value = button.dataset.value;

            document.querySelectorAll(`[data-group="${group}"]`).forEach(item => {
                item.classList.remove('active-yes', 'active-no');
                item.classList.add('inactive');
            });

            button.classList.remove('inactive');
            button.classList.add(
                value === 'yes' ? 'active-yes' : 'active-no'
            );

            const fieldMap = {
                manual21: 'semboyan21',
                manualGenta: 'genta',
                manualPintu: 'pintu',
                manual35: 'semboyan35'
            };

            manualCheck[fieldMap[group]] = value;
        }

        function resetManualChecks() {
            manualCheck = {
                semboyan21: '',
                genta: '',
                pintu: '',
                semboyan35: ''
            };

            document.querySelectorAll('.mini-check').forEach(button => {
                button.classList.remove('active-yes', 'active-no');
                button.classList.add('inactive');
            });
        }

        /* TAMBAH KA MANUAL */
        function tambahKAManual() {
            const nomor = document.getElementById('manualNomor').value.trim();
            const nama = document.getElementById('manualNama').value.trim();
            const aktual = document.getElementById('manualAktual').value;
            const keterangan = document.getElementById('manualKeterangan').value.trim();

            if (!nomor) {
                alert('Nomor KA wajib diisi.');
                document.getElementById('manualNomor').focus();
                return;
            }

            if (!nama) {
                alert('Nama KA wajib diisi.');
                document.getElementById('manualNama').focus();
                return;
            }

            if (!aktual) {
                alert('Jam aktual wajib diisi.');
                document.getElementById('manualAktual').focus();
                return;
            }

            dataKA.push({
                no: dataKA.length + 1,
                nomor,
                nama,
                jadwal: '-',
                aktual,
                status: 'JALAN',
                semboyan21: manualCheck.semboyan21,
                genta: manualCheck.genta,
                pintu: manualCheck.pintu,
                semboyan35: manualCheck.semboyan35,
                keterangan: keterangan || 'KA tambahan',
                jadwal_ka_id: null,

                // Record baru belum mempunyai ID realisasi database.
                realisasi_ka_id: null,

                is_manual: true
            });

            renderTable();

            document.getElementById('manualNomor').value = '';
            document.getElementById('manualNama').value = '';
            document.getElementById('manualAktual').value = '';
            document.getElementById('manualKeterangan').value = '';

            resetManualChecks();

            alert(
                `KA ${nomor} berhasil ditambahkan ke tabel. ` +
                'Klik "Simpan Realisasi" agar data tersimpan ke database.'
            );
        }

        /* SIDEBAR */
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');

            if (sidebar.classList.contains('open')) {
                closeSidebar();
                return;
            }

            sidebar.classList.add('open');

            document.getElementById('sidebarOverlay').classList.add('active');
            document.body.classList.add('menu-open');

            document.getElementById('hamburgerBtn').setAttribute(
                'aria-expanded',
                'true'
            );

            document.getElementById('hamburgerBtn').setAttribute(
                'aria-label',
                'Tutup menu'
            );
        }

        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const button = document.getElementById('hamburgerBtn');

            if (!sidebar || !overlay || !button) {
                return;
            }

            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            document.body.classList.remove('menu-open');

            button.setAttribute('aria-expanded', 'false');
            button.setAttribute('aria-label', 'Buka menu');
        }

        document.querySelectorAll('.sidebar .menu-item').forEach(item => {
            item.addEventListener('click', closeSidebar);
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                closeSidebar();
            }
        });

        /* REFRESH FILTER WAKTU */
        let lastVisibleIds = '';

        function getVisibleIds() {
            return getVisibleKA()
                .map(item => item.index)
                .join(',');
        }

        function refreshTimeFilter() {
            updateWindowRange();

            const currentVisibleIds = getVisibleIds();

            if (currentVisibleIds !== lastVisibleIds) {
                lastVisibleIds = currentVisibleIds;
                renderTable();
            }
        }

        /* MULAI */
        renderTable();
        lastVisibleIds = getVisibleIds();
        updateWindowRange();

        // Periksa perubahan waktu setiap detik.
        setInterval(refreshTimeFilter, 1000);
    </script>

</body>
</html>