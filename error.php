<?php
http_response_code(404);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>404 - Halaman Tidak Ditemukan</title>
    <style>
        :root{--bg:#f8fafc;--card:#fff;--muted:#6b7280;--accent:#1f2937}
        *{box-sizing:border-box}
        body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial;background:var(--bg);color:var(--accent);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}
        .card{background:var(--card);padding:2rem 2.5rem;border-radius:12px;box-shadow:0 8px 32px rgba(15,23,42,0.06);max-width:720px;width:100%;text-align:center}
        h1{font-size:clamp(2rem,5vw,3rem);margin:0 0 .5rem}
        p{margin:.25rem 0 1.25rem;color:var(--muted)}
        .actions{display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap}
        a.button{display:inline-block;padding:.6rem .95rem;border-radius:8px;text-decoration:none;color:#fff;background:#111827;font-weight:600}
        a.secondary{background:transparent;color:var(--accent);border:1px solid rgba(15,23,42,0.06);padding:.5rem .85rem}
        @media (prefers-color-scheme:dark){
            :root{--bg:#0b1220;--card:#071025;--muted:#9ca3af;--accent:#e6eef8}
            a.secondary{border-color:rgba(255,255,255,0.06)}
        }
    </style>
</head>
<body>
    <main class="card" role="main" aria-labelledby="title">
        <h1 id="title">404 — Halaman Tidak Ditemukan</h1>
        <p>Maaf, halaman yang Anda minta tidak tersedia atau telah dipindahkan.</p>
        <div class="actions">
            <a class="button" href="/">Kembali ke Beranda</a>
            <a class="secondary" href="javascript:history.back()">Kembali</a>
        </div>
        <p style="margin-top:1rem;font-size:.85rem;color:var(--muted)">
            Jika Anda yakin ini sebuah kesalahan, hubungi administrator situs.
        </p>
    </main>
</body>
</html>