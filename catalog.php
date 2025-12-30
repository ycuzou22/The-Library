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

$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');

$sql = "SELECT m.id, m.title, m.alt_title, m.status, m.cover_url,
               (SELECT COUNT(*) FROM chapters c WHERE c.manga_id = m.id) AS chapters_count,
               (SELECT MAX(number) FROM chapters c WHERE c.manga_id = m.id) AS latest_chapter
        FROM mangas m
        WHERE 1=1";
$params = [];

if ($q !== '') {
    $sql .= " AND (m.title LIKE ? OR m.alt_title LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}
if ($status !== '') {
    $sql .= " AND m.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY m.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$mangas = $stmt->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Library — Catalogue</title>
  <style>
    :root{
      --bg0:#0b0f18; --bg1:#0f172a;
      --card: rgba(255,255,255,.06); --card2: rgba(255,255,255,.09);
      --text:#e5e7eb; --muted:#a7b0c0; --stroke: rgba(255,255,255,.10);
      --glow: rgba(99,102,241,.35); --glow2: rgba(236,72,153,.22);
      --good: rgba(34,197,94,.18); --end: rgba(239,68,68,.18);
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
    .topbar{max-width:1100px;margin:0 auto;padding:22px 18px 14px;display:flex;align-items:center;justify-content:space-between;gap:12px}
    .brand{display:flex;align-items:center;gap:12px}
    .logo{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg, rgba(99,102,241,.9), rgba(236,72,153,.75))}
    .title h1{font-size:18px;margin:0}
    .title p{margin:3px 0 0;color:var(--muted);font-size:13px}
    .nav{display:flex; gap:10px; flex-wrap:wrap}
    .nav a{text-decoration:none;color:var(--text);border:1px solid var(--stroke);background:rgba(255,255,255,.04);padding:8px 12px;border-radius:999px;font-size:13px}
    .wrap{max-width:1100px;margin:0 auto;padding:8px 18px 34px}
    .filters{display:flex;gap:10px;flex-wrap:wrap;align-items:center;border:1px solid var(--stroke);background:rgba(255,255,255,.04);border-radius:16px;padding:12px}
    .filters input,.filters select{background:rgba(0,0,0,.18);border:1px solid var(--stroke);color:var(--text);padding:10px 12px;border-radius:12px;outline:none}
    .filters button{padding:10px 12px;border-radius:12px;border:1px solid var(--stroke);background:rgba(255,255,255,.06);color:var(--text);cursor:pointer;font-weight:600}
    .meta{margin-top:10px;color:var(--muted);font-size:12.5px}

    .grid{margin-top:14px;display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:14px}
    @media (max-width: 1100px){ .grid{ grid-template-columns: repeat(4, 1fr);} }
    @media (max-width: 760px){ .grid{ grid-template-columns: repeat(2, 1fr);} }
    @media (max-width: 420px){ .grid{ grid-template-columns: 1fr;} }

    a.card{text-decoration:none;color:inherit;border:1px solid var(--stroke);background:linear-gradient(180deg,var(--card),rgba(255,255,255,.03));border-radius:18px;overflow:hidden;transition:transform .12s ease,border-color .12s ease,background .12s ease;display:flex;flex-direction:column;min-height:260px}
    a.card:hover{transform:translateY(-2px);border-color:rgba(255,255,255,.18);background:linear-gradient(180deg,var(--card2),rgba(255,255,255,.04))}
    .cover{height:160px;border-bottom:1px solid var(--stroke);position:relative;overflow:hidden}
    .cover img{width:100%;height:100%;object-fit:cover;display:block;filter:saturate(1.05)}
    .cover .fallback{position:absolute;inset:0;background:
        radial-gradient(600px 160px at 30% 0%, rgba(99,102,241,.25), transparent 60%),
        radial-gradient(600px 160px at 70% 0%, rgba(236,72,153,.18), transparent 60%),
        linear-gradient(135deg, rgba(255,255,255,.06), rgba(255,255,255,.02));}
    .badge{position:absolute;top:10px;left:10px;padding:6px 10px;border-radius:999px;border:1px solid var(--stroke);background:rgba(0,0,0,.25);font-size:12px}
    .badge.ok{background:var(--good)}
    .badge.end{background:var(--end)}
    .content{padding:12px 12px 14px;display:flex;flex-direction:column;gap:8px;flex:1}
    .name{font-weight:800;font-size:14px;line-height:1.2}
    .alt{color:var(--muted);font-size:12px}
    .row{display:flex;gap:8px;flex-wrap:wrap;margin-top:auto}
    .pill{font-size:12px;color:var(--muted);border:1px solid var(--stroke);background:rgba(255,255,255,.04);padding:6px 10px;border-radius:999px}
  </style>
</head>
<body>
<header class="topbar">
  <div class="brand">
    <div class="logo" aria-hidden="true"></div>
    <div class="title">
      <h1>Catalogue</h1>
      <p>Connecté : <?= htmlspecialchars($username) ?></p>
    </div>
  </div>
  <nav class="nav">
    <a href="dashboard.php">← Dashboard</a>
    <a href="upload_chapter.php">Upload chapitre</a>
    <a href="logout.php">Déconnexion</a>
  </nav>
</header>

<main class="wrap">
  <form class="filters" method="get">
    <input type="text" name="q" placeholder="Rechercher un manga..." value="<?= htmlspecialchars($q) ?>">
    <select name="status">
      <option value="">Tous les statuts</option>
      <option value="En cours" <?= $status === 'En cours' ? 'selected' : '' ?>>En cours</option>
      <option value="Terminé" <?= $status === 'Terminé' ? 'selected' : '' ?>>Terminé</option>
    </select>
    <button type="submit">Filtrer</button>
  </form>

  <div class="meta">
    <?= count($mangas) ?> manga(s). Clique sur une carte pour ouvrir le menu et lire les chapitres uploadés.
  </div>

  <section class="grid">
    <?php foreach ($mangas as $m): ?>
      <a class="card" href="manga.php?id=<?= (int)$m['id'] ?>">
        <div class="cover">
          <?php if (!empty($m['cover_url'])): ?>
            <img src="<?= htmlspecialchars((string)$m['cover_url']) ?>" alt="Cover">
          <?php else: ?>
            <div class="fallback" aria-hidden="true"></div>
          <?php endif; ?>
          <span class="badge <?= ($m['status'] === 'Terminé') ? 'end' : 'ok' ?>">
            <?= htmlspecialchars((string)$m['status']) ?>
          </span>
        </div>
        <div class="content">
          <div class="name"><?= htmlspecialchars((string)$m['title']) ?></div>
          <div class="alt"><?= htmlspecialchars((string)($m['alt_title'] ?? '')) ?></div>
          <div class="row">
            <span class="pill">📚 <?= (int)($m['chapters_count'] ?? 0) ?> ch.</span>
            <?php if (!empty($m['latest_chapter'])): ?>
              <span class="pill">🆕 ch. <?= (int)$m['latest_chapter'] ?></span>
            <?php endif; ?>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </section>
</main>
</body>
</html>
