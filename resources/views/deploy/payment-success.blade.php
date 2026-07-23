<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembayaran Berhasil</title>
    @include('layouts.partials.favicon')
    <style>
        body { font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #ecfdf5; color: #065f46; text-align: center; padding: 1rem; }
    </style>
</head>
<body>
    <div>
        <p style="font-size: 2rem; margin-bottom: 0.5rem;">✓</p>
        <p style="font-weight: 600;">Pembayaran berhasil</p>
        <p style="font-size: 0.875rem; color: #047857; margin-top: 0.5rem;">Deploy akan dimulai otomatis…</p>
    </div>
    <script>
        (function () {
            const ref = @json($ref);
            if (window.opener && ref) {
                window.opener.postMessage({ type: 'xendit-deploy-paid', ref }, window.location.origin);
                setTimeout(() => window.close(), 800);
            } else if (ref) {
                window.location.href = '/deploy?paid=' + encodeURIComponent(ref);
            }
        })();
    </script>
</body>
</html>
