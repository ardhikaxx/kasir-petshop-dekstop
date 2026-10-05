<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terjadi Kendala - Kasir Pet Shop Desktop</title>
    <link rel="stylesheet" href="{{ asset('css/petshop-ui.css') }}">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f1f5f9;
        }
        .error-card {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 2.5rem;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: var(--shadow-lg);
        }
        .error-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background-color: var(--warning-light);
            color: var(--warning);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">
            <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>

        <h1 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-main);">
            Terjadi Kendala Teknis
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem; line-height: 1.5;">
            {{ $message ?? 'Aplikasi kasir mendeteksi kesalahan operasional. Data transaksi Anda tersimpan aman di database lokal SQLite.' }}
        </p>

        <div style="display: flex; gap: 0.5rem; justify-content: center;">
            <a href="{{ route('pos.index') }}" class="btn btn-primary btn-lg">Kembali ke Kasir POS</a>
            <a href="{{ route('dashboard.index') }}" class="btn btn-outline btn-lg">Buka Dashboard</a>
        </div>
    </div>
</body>
</html>
