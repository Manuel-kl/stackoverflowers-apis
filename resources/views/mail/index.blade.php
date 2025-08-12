@php($flashError = session('error'))
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Mail Dashboard</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing:border-box; }
        body { font-family:'Inter', system-ui, Arial, sans-serif; margin:0; background:#0f172a; color:#e2e8f0; }
        a { text-decoration:none; }
        header { max-width:1000px; margin:30px auto 0; padding:0 32px; display:flex; justify-content:space-between; align-items:center; }
        header nav a { font-size:0.8rem; color:#64748b; padding:6px 12px; border-radius:8px; }
        header nav a:hover { background:#1e293b; color:#e2e8f0; }
        main { max-width:1000px; margin:24px auto 80px; padding:0 32px; display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:28px; }
        h1 { font-size:1.55rem; margin:0 0 4px; font-weight:600; letter-spacing:-0.5px; }
        .card { background:#1e293b; border:1px solid #334155; border-radius:18px; padding:26px 28px; display:flex; flex-direction:column; gap:14px; }
        .muted { color:#94a3b8; font-size:0.83rem; line-height:1.4; }
        .status-pill { padding:6px 13px; border-radius:999px; font-size:0.7rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; display:inline-block; }
        .status-active { background:#065f46; color:#dcfce7; }
        .status-inactive { background:#3f3646; color:#f0d3ff; }
        .status-expired { background:#7f1d1d; color:#fecaca; }
        button, .btn { cursor:pointer; font-family:inherit; font-size:0.85rem; font-weight:500; border:0; border-radius:10px; padding:10px 18px; display:inline-flex; align-items:center; gap:6px; line-height:1.1; transition:.25s background; }
        .btn-primary { background:#2563eb; color:#fff; }
        .btn-primary:hover { background:#1d4ed8; }
        .btn-secondary { background:#334155; color:#e2e8f0; }
        .btn-secondary:hover { background:#475569; }
        .grid-row { display:flex; flex-direction:column; gap:26px; }
        .token-table { width:100%; border-collapse:collapse; font-size:0.75rem; }
        .token-table th { text-align:left; font-weight:500; color:#94a3b8; padding:4px 0 10px; font-size:0.65rem; letter-spacing:0.5px; text-transform:uppercase; }
        .token-table td { padding:5px 0; border-top:1px solid #334155; }
        .flash { background:#7f1d1d; color:#fecaca; padding:10px 16px; border-radius:10px; font-size:0.75rem; margin:0 0 18px; }
        footer { grid-column:1/-1; text-align:center; font-size:0.65rem; color:#475569; margin-top:22px; }
        @media (max-width:720px){ header, main { padding:0 20px; } main { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <header>
        <div>
            <h1>Mail Dashboard</h1>
            <div class="muted">Monitor and manage your Google Mail integration.</div>
        </div>
        <nav>
            <a href="{{ route('mail.connect.view') }}">Connect</a>
            <a href="{{ url('/') }}">Home</a>
        </nav>
    </header>

    <main>
        <section class="card" style="grid-column: span 2;">
            @if($flashError)
                <div class="flash">{{ $flashError }}</div>
            @endif
            <h2 style="margin:0; font-size:1rem;">Integration Status</h2>
            <span class="status-pill status-{{ $status }}">{{ ucfirst($status) }}</span>
            <p class="muted">Current Gmail authorization status. If inactive, connect to start sending emails through the API.</p>
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                @if($status !== 'active')
                <form method="POST" action="{{ route('mail.connect') }}">@csrf<button class="btn btn-primary" type="submit">Connect Google</button></form>
                @else
                <form method="POST" action="#" onsubmit="event.preventDefault(); alert('Placeholder: send test email');"><button class="btn btn-secondary" type="submit">Send Test Email</button></form>
                <form method="POST" action="#" onsubmit="event.preventDefault(); alert('Placeholder: disconnect');"><button class="btn btn-secondary" type="submit">Disconnect</button></form>
                @endif
            </div>
        </section>

        <section class="card">
            <h3 style="margin:0; font-size:0.95rem;">Token Details</h3>
            @if(isset($token) && $token)
                <table class="token-table">
                    <tr><th>Project</th><td>{{ $token->project_id }}</td></tr>
                    <tr><th>Created</th><td>{{ $token->created_at->diffForHumans() }}</td></tr>
                    <tr><th>Updated</th><td>{{ $token->updated_at->diffForHumans() }}</td></tr>
                    <tr><th>Expires</th><td>{{ $token->expires_at ? $token->expires_at->diffForHumans() : 'Unknown' }}</td></tr>
                </table>
            @else
                <p class="muted">No token stored yet.</p>
            @endif
        </section>

        <section class="card">
            <h3 style="margin:0; font-size:0.95rem;">Next Steps</h3>
            <ul style="margin:0; padding-left:18px; line-height:1.5; font-size:0.8rem; color:#94a3b8;">
                <li>Connect your Google account if inactive.</li>
                <li>Send a test email (placeholder action for now).</li>
                <li>Implement real disconnect & test mail endpoints.</li>
            </ul>
        </section>

        <footer>Minimal dashboard • Extend as needed</footer>
    </main>
</body>
</html>
