@php($isActive = ($status ?? 'inactive') === 'active')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Connect Google Mail</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, Arial, sans-serif; margin:0; background:#0f172a; color:#e2e8f0; -webkit-font-smoothing: antialiased; }
        a { text-decoration:none; }
        .wrap { max-width:560px; margin:60px auto 80px; padding:40px 48px; background:#1e293b; border:1px solid #334155; border-radius:18px; }
        h1 { font-size:1.75rem; margin:0 0 8px; font-weight:600; letter-spacing:-0.5px; }
        p.lead { margin:0 0 28px; font-size:0.995rem; line-height:1.5; color:#94a3b8; }
        .badge { display:inline-flex; align-items:center; gap:6px; background:#334155; padding:6px 14px; border-radius:999px; font-size:0.72rem; letter-spacing:0.5px; font-weight:500; text-transform:uppercase; color:#cbd5e1; }
        .badge.active { background:#065f46; color:#dcfce7; }
        .actions { display:flex; gap:14px; flex-wrap:wrap; }
        button, .btn { cursor:pointer; font-family:inherit; font-size:0.95rem; font-weight:500; border:0; border-radius:10px; padding:12px 22px; display:inline-flex; align-items:center; gap:8px; line-height:1.1; transition:.25s background, .25s box-shadow, .25s color; }
        .btn-primary { background:#2563eb; color:#fff; }
        .btn-primary:hover { background:#1d4ed8; }
        .btn-secondary { background:#334155; color:#e2e8f0; }
        .btn-secondary:hover { background:#475569; }
        .note { margin-top:30px; font-size:0.75rem; line-height:1.3; color:#64748b; }
        footer { margin-top:40px; font-size:0.65rem; text-align:center; color:#475569; }
        .status-box { padding:14px 16px; border:1px solid #334155; background:#0f172a; border-radius:14px; display:flex; align-items:center; justify-content:space-between; margin:0 0 28px; }
        .pill { padding:6px 12px; border-radius:999px; font-size:0.7rem; letter-spacing:0.5px; font-weight:600; text-transform:uppercase; }
        .pill.inactive { background:#3f3646; color:#f0d3ff; }
        .pill.active { background:#065f46; color:#dcfce7; }
        .pill.expired { background:#7f1d1d; color:#fecaca; }
        .nav-top { max-width:560px; margin:26px auto -26px; display:flex; justify-content:flex-end; padding:0 4px; }
        .nav-top a { font-size:0.8rem; color:#64748b; padding:6px 12px; border-radius:8px; }
        .nav-top a:hover { background:#1e293b; color:#e2e8f0; }
        @media (max-width:640px) { .wrap { margin:32px 16px 64px; padding:32px 28px; } }
    </style>
</head>
<body>
    <div class="nav-top">
        <a href="{{ route('mail.index') }}">Mail Dashboard →</a>
    </div>
    <main class="wrap">
        <div class="status-box">
            <strong style="font-size:0.8rem; letter-spacing:0.5px; color:#94a3b8;">Integration Status</strong>
            <span class="pill {{ $status }}">{{ ucfirst($status) }}</span>
        </div>
        <h1>Google Mail Connection</h1>
        <p class="lead">Connect your Google account to enable sending transactional emails. We only request the minimal scope needed to send messages. You can revoke access anytime in your Google security settings.</p>

        <div class="actions">
            @if($isActive)
                <a class="btn btn-secondary" href="{{ route('mail.index') }}">View Dashboard</a>
            @else
                <form method="POST" action="{{ route('mail.connect') }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Connect Google</button>
                </form>
            @endif
            <a class="btn btn-secondary" href="{{ url('/') }}">Home</a>
        </div>

        <p class="note">We never store your Google password. A secure OAuth token is saved encrypted. If the token expires or is revoked you'll just reconnect here.</p>
        <footer>Minimal Gmail integration panel</footer>
    </main>
</body>
</html>
