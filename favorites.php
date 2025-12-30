<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

require_once __DIR__ . '/config/db.php';
$pdo = db();

$userId = (int)$_SESSION['user_id'];
$username = (string)($_SESSION['username'] ?? 'Utilisateur');

if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = (string)$_SESSION['csrf'];

$stmt = $pdo->prepare("
  SELECT m.id, m.title, m.alt_title, m.status, m.cover_url, f.created_at
  FROM favorites f
  JOIN mangas m ON m.id = f.manga_id
  WHERE f.user_id=?
  ORDER BY f.created_at DESC
");
$stmt->execute([$userId]);
$favs = $stmt->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mes favoris</title>
  <style>
    :root{
      --bg0:#0b0f18; --bg1:#0f172a;
      --card: rgba(255,255,255,.06); --text:#e5e7eb; --muted:#a7b0c0; --stroke: rgba(255,255,255,.10);
      --glow: rgba(99,102,241,.35); --glow2: rgba(236,72,153,.22);
    }
    *{box-sizing:border-box}
    body{
      margin:0; color:var(--text);
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, "Noto Sans", sans-serif;
      background:
        radial-gradient(1000px 600px at 20% -10%, var(--glow), transparent 60%),
        radial-gradient(900px 600px at 90% 10%, var(--glow2), transparent 55%),
        linear-gradient(180deg, var(--bg0), var(--bg1));
      min-height:100vh;
    }
    .topbar{
      max-width:1100px;margin:0 auto;padding:18px 16px 10px;
      display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;
    }
    .nav{display:flex;gap:10px;flex-wrap:wrap}
    .nav a{
      text-decoration:none;color:var(--text);
      border:1px solid var(--stroke);background:rgba(255,255,255,.04);
      padding:8px 12px;border-radius:999px;font-size:13px;
    }
    .wrap{max-width:1100px;margin:0 auto;padding:8px 16px 34px}
    .grid{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-top:12px}
    @media(max-width:1100px){.grid{grid-template-columns:repeat(5,1fr)}}
    @media(max-width:900px){.grid{grid-template-columns:repeat(3,1fr)}}
    @media(max-width:520px){.grid{grid-template-columns:repeat(2,1fr)}}
    .card{
      border:1px solid var(--stroke);background:rgba(255,255,255,.04);
      border-radius:16px;overflow:hidden;text-decoration:none;color:inherit;
      transition: transform .12s ease, border-color .12s ease;
    }
    .card:hover{transform:translateY(-2px);border-color:rgba(255,255,255,.18)}
    .thumb{aspect-ratio:3/4;background:#111;position:relative}
    .thumb img{width:100%;height:100%;object-fit:cover;display:block}
    .thumb:after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,transparent 55%,rgba(0,0,0,.70))}
    .meta{padding:10px 10px 12px}
    .t{font-weight:900;font-size:13px;line-height:1.2}
    .s{margin-top:6px;color:var(--muted);font-size:12px}
    .btn{
      border:1px solid var(--stroke);background:rgba(255,255,255,.06);
      color:var(--text);padding:8px 10px;border-radius:12px;font-weight:900;
      cursor:pointer;width:100%;margin-top:10px;
    }
    .empty{
      border:1px solid var(--stroke);background:rgba(255,255,255,.04);
      padding:14px;border-radius:16px;color:var(--muted);margin-top:12px;
    }
  </style>
</head>
<body>
<header class="topbar">
  <div>
    <div style="font-weight:900;font-size:18px;">⭐ Mes favoris</div>
    <div style="color:var(--muted);font-size:13px;">Connecté : <?= htmlspecialchars($username) ?></div>
  </div>
  <nav class="nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="catalog.php">Catalogue</a>
    <a href="logout.php">Déconnexion</a>
  </nav>
</header>

<main class="wrap">
  <?php if (!$favs): ?>
    <div class="empty">Aucun favori pour le moment. Va sur le catalogue puis ajoute des ⭐.</div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($favs as $m): ?>
        <?php
          $img = trim((string)($m['cover_url'] ?? ''));
          $src = $img !== '' ? $img : 'data:image/svg+xml;charset=UTF-8,' . rawurlencode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="420">
              <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
                <stop stop-color="#6366f1" offset="0"/><stop stop-color="#ec4899" offset="1"/>
              </linearGradient></defs>
              <rect width="100%" height="100%" fill="url(#g)"/>
            </svg>'
          );
        ?>
        <div class="card">
          <a href="manga.php?id=<?= (int)$m['id'] ?>" class="card" style="border:none;background:transparent;">
            <div class="thumb"><img src="<?= htmlspecialchars($src) ?>" alt=""></div>
            <div class="meta">
              <div class="t"><?= htmlspecialchars((string)$m['title']) ?></div>
              <div class="s"><?= htmlspecialchars((string)($m['status'] ?? '')) ?></div>
            </div>
          </a>

          <form method="post" action="favorite_toggle.php" style="margin:0;padding:0 10px 12px;">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="manga_id" value="<?= (int)$m['id'] ?>">
            <input type="hidden" name="back" value="favorites.php">
            <button class="btn" type="submit">Retirer des favoris</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
</body>
</html>
