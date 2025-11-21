<?php
$redirectTo = 'index.php';
$delaySeconds = 3000; // milliseconds
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Memuat…</title>

    <!-- favicon / logo -->
    <link rel="icon" type="image/x-icon" href="../../public/icon.svg">

    <style>
        :root{
            --bg1:#0f172a;
            --bg2:#0b7cff;
            --accent:#00d4ff;
        }
        html,body{height:100%;margin:0;font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial;}
        body{
            display:flex;
            align-items:center;
            justify-content:center;
            background:linear-gradient(135deg,var(--bg1),#07102b 40%,var(--bg2));
            color:#fff;
            overflow:hidden;
        }
        .card{
            text-align:center;
            padding:36px 40px;
            border-radius:16px;
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            box-shadow: 0 10px 30px rgba(2,6,23,.6);
            background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01));
            width: min(520px, 92%);
        }
        .logo{
            width:120px;
            height:120px;
            margin:0 auto 18px;
            display:block;
            animation: pop 900ms cubic-bezier(.2,.9,.3,1) both;
        }
        @keyframes pop{
            0%{transform: scale(.6);opacity:0}
            60%{transform: scale(1.06);opacity:1}
            100%{transform: scale(1);opacity:1}
        }
        h1{margin:0 0 6px;font-size:20px;letter-spacing:.2px}
        p{margin:0 0 18px;opacity:.85}
        .progress{
            height:8px;
            width:100%;
            background: rgba(255,255,255,0.06);
            border-radius:999px;
            overflow:hidden;
        }
        .bar{
            height:100%;
            width:0%;
            background: linear-gradient(90deg,var(--accent), #7be6ff);
            border-radius:999px;
            transition: width 300ms linear;
            box-shadow: 0 4px 16px rgba(0,212,255,0.12);
        }
        .enter{
            margin-top:14px;
            display:inline-block;
            color:#04243a;
            background: #fff;
            padding:8px 14px;
            border-radius:999px;
            text-decoration:none;
            font-weight:600;
            font-size:13px;
        }
        .meta{
            margin-top:12px;
            font-size:12px;
            opacity:.6;
        }
        @media (prefers-reduced-motion:reduce){
            .logo{animation:none}
            .bar{transition:none}
        }
    </style>

    <script>
        // Redirect after delay while animating progress bar.
        const delay = <?php echo (int)$delaySeconds; ?>;
        const redirectTo = <?php echo json_encode($redirectTo); ?>;

        function animateAndRedirect() {
            const bar = document.querySelector('.bar');
            const steps = 60;
            let i = 0;
            const interval = delay / steps;
            const timer = setInterval(() => {
                i++;
                bar.style.width = Math.min(100, Math.round((i/steps)*100)) + '%';
                if (i >= steps) {
                    clearInterval(timer);
                    window.location.href = redirectTo;
                }
            }, interval);
        }

        document.addEventListener('DOMContentLoaded', animateAndRedirect, {once:true});
    </script>
</head>
<body>
    <main class="card" role="main" aria-live="polite">
        <img class="logo" src="../../public/icon.svg" alt="Logo">
        <h1>Aplikasi Peminjaman Ruangan</h1>
        <p>Memuat data dan menyiapkan aplikasi. Mohon tunggu…</p>

        <div class="progress" aria-hidden="true">
            <div class="bar"></div>
        </div>

        <div class="meta">
            <a class="enter" href="<?php echo htmlspecialchars($redirectTo, ENT_QUOTES, 'UTF-8'); ?>">Masuk Sekarang</a>
        </div>
    </main>

    <noscript>
        <meta http-equiv="refresh" content="<?php echo max(1, intval($delaySeconds/1000)); ?>;url=<?php echo htmlspecialchars($redirectTo, ENT_QUOTES, 'UTF-8'); ?>">
        <style> .card {padding:20px} </style>
        <div style="position:fixed;left:12px;bottom:12px;color:#fff;font-size:13px;">JavaScript dimatikan — dialihkan otomatis.</div>
    </noscript>
</body>
</html></noscript>