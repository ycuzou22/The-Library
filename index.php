<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Library — Accueil</title>
  <style>
    :root{
      --bg0:#0b0f18;
      --bg1:#0f172a;
      --card: rgba(255,255,255,.06);
      --card2: rgba(255,255,255,.09);
      --text:#e5e7eb;
      --muted:#a7b0c0;
      --stroke: rgba(255,255,255,.10);
      --glow: rgba(99,102,241,.35);
      --glow2: rgba(236,72,153,.22);
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      min-height:100vh;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, "Noto Sans", "Liberation Sans", sans-serif;
      background:
        radial-gradient(1000px 600px at 20% -10%, var(--glow), transparent 60%),
        radial-gradient(900px 600px at 90% 10%, var(--glow2), transparent 55%),
        linear-gradient(180deg, var(--bg0), var(--bg1));
      color:var(--text);
      display:grid;
      place-items:center;
      padding:22px 14px;
    }

    .home{
      width:100%;
      max-width:420px;
      border:1px solid var(--stroke);
      background: linear-gradient(180deg, var(--card), rgba(255,255,255,.03));
      border-radius:18px;
      padding:22px 20px;
      text-align:center;
      box-shadow: 0 20px 60px rgba(0,0,0,.35);
      backdrop-filter: blur(10px);
      position:relative;
      overflow:hidden;
    }
    .home:before{
      content:"";
      position:absolute; inset:-2px;
      background:
        radial-gradient(500px 180px at 20% 0%, rgba(99,102,241,.22), transparent 60%),
        radial-gradient(500px 180px at 80% 0%, rgba(236,72,153,.16), transparent 60%);
      pointer-events:none;
    }

    .logo{
      width:54px;height:54px;
      border-radius:16px;
      margin:0 auto 12px;
      background: linear-gradient(135deg, rgba(99,102,241,.9), rgba(236,72,153,.75));
      box-shadow: 0 10px 30px rgba(0,0,0,.35);
    }

    h1{
      margin:0;
      font-size:22px;
      letter-spacing:.3px;
    }
    p{
      margin:8px 0 18px;
      color:var(--muted);
      font-size:13.5px;
      line-height:1.4;
    }

    .actions{
      display:grid;
      gap:10px;
      margin-top:8px;
    }
    a.btn{
      display:block;
      text-decoration:none;
      padding:12px;
      border-radius:12px;
      font-weight:900;
      border:1px solid var(--stroke);
      background: rgba(255,255,255,.08);
      color:var(--text);
      transition: transform .12s ease, background .12s ease, border-color .12s ease;
    }
    a.btn:hover{
      transform: translateY(-1px);
      border-color: rgba(255,255,255,.18);
      background: rgba(255,255,255,.12);
    }
    a.btn.primary{
      background: linear-gradient(135deg, rgba(99,102,241,.85), rgba(236,72,153,.75));
      border-color: transparent;
    }
    a.btn.primary:hover{
      filter: brightness(1.05);
    }

    footer{
      margin-top:16px;
      font-size:12px;
      color:var(--muted);
      display:flex;
      justify-content:center;
      gap:8px;
      flex-wrap:wrap;
    }
    .pill{
      border:1px solid var(--stroke);
      background: rgba(255,255,255,.04);
      padding:5px 10px;
      border-radius:999px;
    }
  </style>
</head>
<body>

  <div class="home">
    <div class="logo" aria-hidden="true"></div>

    <h1>Library</h1>
    <p>Bienvenue sur ta bibliothèque en ligne.<br>
       Lis, upload et gère tes mangas.</p>

    <div class="actions">
      <a href="login.php" class="btn primary">Se connecter</a>
      <a href="register.php" class="btn">Créer un compte</a>
    </div>

    <footer>
      <span class="pill">📚 Projet PHP</span>
      <span class="pill">⚡ Dashboard moderne</span>
    </footer>
  </div>

</body>
</html>
