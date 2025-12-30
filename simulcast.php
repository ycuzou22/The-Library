<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/db.php';
$pdo = db();

$username = (string)($_SESSION['username'] ?? 'Utilisateur');

// On prend published_at si dispo, sinon on utilise created_at (si ta table l’a)
// Si ta table chapters n’a pas created_at, laisse seulement published_at.
$stmt = $pdo->query("
  SELECT
    c.id AS chapter_id,
    c.manga_id,
    c.number,
    c.title AS chapter_title,
    c.pdf_url,
    c.created_at,
    m.title AS manga_title,
    m.cover_url
  FROM chapters c
  JOIN mangas m ON m.id = c.manga_id
  WHERE c.created_at >= (NOW() - INTERVAL 48 HOUR)
  ORDER BY c.created_at DESC
  LIMIT 80
");
$rows = $stmt->fetchAll();

function chapterHref(int $mangaId, int $chNum, string $pdfUrl): string {
    if (trim($pdfUrl) !== '') {
        return "pdf_viewer.php?manga={$mangaId}&ch={$chNum}";
    }
    return "reader.php?manga={$mangaId}&ch={$chNum}";
}

function coverSrc(array $row): string {
    $img = trim((string)($row['cover_url'] ?? ''));
    if ($img !== '') return $img;

    // fallback
    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode(
        '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="420">
          <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
            <stop stop-color="#6366f1" offset="0"/><stop stop-color="#ec4899" offset="1"/>
          </linearGradient></defs>
          <rect width="100%" height="100%" fill="url(#g)"/>
        </svg>'
    );
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Simulcast — 48h</title>
  <style>
    :root{
      --bg0:#0b0f18; --bg1:#0f172a;
      --text:#e5e7eb; --muted:#a7b0c0; --stroke: rgba(255,255,255,.10);
      --glow: rgba(99,102,241,.35); --glow2: rgba(236,72,153,.22);
      --card: rgba(255,255,255,.06);
      --new: rgba(34,197,94,.18);
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
      max-width:1100px; margin:0 auto; padding:18px 16px 10px;
      display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
    }
    .nav{display:flex; gap:10px; flex-wrap:wrap}
    .nav a{
      text-decoration:none; color:var(--text);
      border:1px solid var(--stroke);
      background: rgba(255,255,255,.04);
      padding:8px 12px; border-radius:999px;
      font-size:13px;
    }
    .wrap{max-width:1100px;margin:0 auto;padding:8px 16px 34px}

    .headerCard{
      border:1px solid var(--stroke);
      background: linear-gradient(180deg, var(--card), rgba(255,255,255,.03));
      border-radius:18px;
      padding:14px 14px;
      display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;
    }
    .headerCard h1{margin:0;font-size:18px}
    .headerCard p{margin:6px 0 0;color:var(--muted);font-size:13px}

    .list{margin-top:12px;display:flex;flex-direction:column;gap:10px}
    .item{
      border:1px solid var(--stroke);
      background: rgba(255,255,255,.04);
      border-radius:16px;
      overflow:hidden;
      display:grid;
      grid-template-columns: 90px 1fr auto;
      gap:0;
      text-decoration:none;
      color:inherit;
    }
    @media(max-width:720px){ .item{ grid-template-columns: 80px 1fr; } .right{display:none;} }

    .cover{background:#111; position:relative;}
    .cover img{width:100%;height:100%;object-fit:cover;display:block}
    .cover:after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,transparent 40%,rgba(0,0,0,.55))}
    .content{padding:10px 12px}
    .title{font-weight:900;font-size:14px;line-height:1.2}
    .meta{margin-top:6px;color:var(--muted);font-size:12.5px}
    .badge{
      display:inline-flex;align-items:center;gap:6px;
      margin-left:8px;
      padding:2px 8px;border-radius:999px;
      border:1px solid rgba(255,255,255,.14);
      background: var(--new);
      font-size:11px;font-weight:900;
    }
    .right{
      padding:10px 12px;
      display:flex;align-items:center;justify-content:center;
      color:rgba(229,231,235,.9);
      font-weight:900;
      white-space:nowrap;
    }
    .empty{
      margin-top:12px;
      border:1px solid var(--stroke);
      background: rgba(255,255,255,.04);
      border-radius:16px;
      padding:14px;
      color:var(--muted);
    }
    @keyframes pulseNew {
        0% { opacity: .4; }
        50% { opacity: 1; }
        100% { opacity: .4; }
    }
    .badge {
        animation: pulseNew 2.5s ease-in-out infinite;
    }

  </style>
</head>
<body>

<header class="topbar">
  <div class="nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="catalog.php">Catalogue</a>
  </div>
  <div class="nav">
    <a href="logout.php">Déconnexion (<?= htmlspecialchars($username) ?>)</a>
  </div>
</header>

<main class="wrap">
  <section class="headerCard">
    <div>
      <h1>⚡ Simulcast</h1>
      <p>Chapitres publiés dans les dernières 48 heures.</p>
    </div>
    <div style="color:var(--muted);font-size:13px;">
      <?= count($rows) ?> chapitre(s)
    </div>
  </section>

  <?php if (!$rows): ?>
    <div class="empty">Aucun chapitre publié dans les dernières 48h (selon <code>chapters.created_at</code>).</div>
  <?php else: ?>
    <div class="list">
      <?php foreach ($rows as $r): ?>
        <?php
          $mangaId = (int)$r['manga_id'];
          $chNum = (int)$r['number'];
          $href = chapterHref($mangaId, $chNum, (string)($r['pdf_url'] ?? ''));
          $when = (string)($r['created_at'] ?? '');
        ?>
        <a class="item" href="<?= htmlspecialchars($href) ?>">
          <div class="cover">
            <img src="<?= htmlspecialchars(coverSrc($r)) ?>" alt="">
          </div>
          <div class="content">
            <div class="title">
              <?= htmlspecialchars((string)$r['manga_title']) ?>
              <span class="badge">NEW</span>
            </div>
            <div class="meta">
              Chapitre <?= $chNum ?><?= !empty($r['chapter_title']) ? ' — ' . htmlspecialchars((string)$r['chapter_title']) : '' ?>
              • <?= $when !== '' ? 'Publié : ' . htmlspecialchars($when) : '' ?>
            </div>
          </div>
          <div class="right">Lire →</div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

</body>
</html>
