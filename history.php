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

// CSRF
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = (string)$_SESSION['csrf'];

$type = (string)($_GET['type'] ?? 'all'); // all|avatar|banner
if (!in_array($type, ['all','avatar','banner'], true)) $type = 'all';

// Actions (delete / clear)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postCsrf = (string)($_POST['csrf'] ?? '');
    if (!hash_equals($csrf, $postCsrf)) {
        http_response_code(403);
        exit("CSRF invalide.");
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'delete_one') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM user_media_history WHERE id=? AND user_id=?");
            $stmt->execute([$id, $userId]);
        }
        header("Location: history.php?type=" . urlencode($type));
        exit;
    }

    if ($action === 'clear') {
        // clear selon filtre
        if ($type === 'all') {
            $stmt = $pdo->prepare("DELETE FROM user_media_history WHERE user_id=?");
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM user_media_history WHERE user_id=? AND type=?");
            $stmt->execute([$userId, $type]);
        }
        header("Location: history.php?type=" . urlencode($type));
        exit;
    }
}

// Récup historique (pagination simple)
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$where = "WHERE user_id=?";
$params = [$userId];

if ($type !== 'all') {
    $where .= " AND type=?";
    $params[] = $type;
}

$stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM user_media_history $where");
$stmt->execute($params);
$total = (int)($stmt->fetch()['c'] ?? 0);

$stmt = $pdo->prepare("
    SELECT id, type, url, created_at
    FROM user_media_history
    $where
    ORDER BY created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pages = max(1, (int)ceil($total / $perPage));

function dayLabel(string $dt): string {
    $d = substr($dt, 0, 10); // YYYY-MM-DD
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    $yest  = (new DateTimeImmutable('yesterday'))->format('Y-m-d');
    if ($d === $today) return "Aujourd’hui";
    if ($d === $yest) return "Hier";
    return $d;
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Historique — <?= htmlspecialchars($username) ?></title>
  <style>
    :root{
      --bg0:#0b0f18; --bg1:#0f172a;
      --card: rgba(255,255,255,.06); --card2: rgba(255,255,255,.09);
      --text:#e5e7eb; --muted:#a7b0c0; --stroke: rgba(255,255,255,.10);
      --glow: rgba(99,102,241,.35); --glow2: rgba(236,72,153,.22);
      --danger: rgba(239,68,68,.18);
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
      padding:8px 12px; border-radius:999px; font-size:13px;
    }
    .wrap{max-width:1100px;margin:0 auto;padding:8px 16px 34px}

    .tabs{display:flex; gap:8px; flex-wrap:wrap; margin:10px 0 14px;}
    .tab{
      text-decoration:none; color:var(--text);
      border:1px solid var(--stroke);
      background: rgba(255,255,255,.04);
      padding:8px 12px; border-radius:999px; font-size:13px;
      opacity:.85;
    }
    .tab.active{opacity:1; background: rgba(255,255,255,.08);}

    .bar{
      display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;
      border:1px solid var(--stroke);
      background: rgba(255,255,255,.04);
      padding:12px 14px;
      border-radius:16px;
    }
    .bar .meta{color:var(--muted); font-size:13px;}
    .btn{
      border:1px solid var(--stroke); background:rgba(255,255,255,.06);
      color:var(--text); padding:8px 12px;border-radius:999px;
      font-weight:800; font-size:13px; cursor:pointer; text-decoration:none;
    }
    .btn.danger{background: var(--danger);}

    .groupTitle{margin:16px 0 8px;color:var(--muted);font-size:13px}

    .grid{
      display:grid;
      grid-template-columns: repeat(5, 1fr);
      gap:12px;
    }
    @media(max-width:980px){.grid{grid-template-columns:repeat(4,1fr)}}
    @media(max-width:760px){.grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:420px){.grid{grid-template-columns:1fr}}

    .card{
      border:1px solid var(--stroke);
      background: rgba(0,0,0,.18);
      border-radius:16px;
      overflow:hidden;
    }
    .thumb{aspect-ratio:16/9;background:#111}
    .thumb img{width:100%;height:100%;object-fit:cover;display:block}
    .cardBody{padding:10px 12px}
    .small{color:var(--muted);font-size:12px}
    .actions{display:flex; gap:8px; margin-top:10px; flex-wrap:wrap}
    .actions form{margin:0}
    .actions .btn{padding:8px 10px;border-radius:12px}
    .pager{margin-top:16px;display:flex;gap:8px;flex-wrap:wrap}
  </style>
</head>
<body>

<header class="topbar">
  <div class="nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="users.php">Mon profil</a>
  </div>
  <div class="nav">
    <a href="settings.php">Paramètres</a>
    <a href="logout.php">Déconnexion (<?= htmlspecialchars($username) ?>)</a>
  </div>
</header>

<main class="wrap">
  <h1 style="margin:6px 0 6px;font-size:20px;">Historique</h1>

  <div class="tabs">
    <a class="tab <?= $type==='all'?'active':'' ?>" href="history.php?type=all">Tout</a>
    <a class="tab <?= $type==='avatar'?'active':'' ?>" href="history.php?type=avatar">Avatars</a>
    <a class="tab <?= $type==='banner'?'active':'' ?>" href="history.php?type=banner">Bannières</a>
  </div>

  <div class="bar">
    <div class="meta">
      <?= $total ?> élément(s) • page <?= $page ?>/<?= $pages ?>
    </div>

    <form method="post" onsubmit="return confirm('Tout effacer ?');">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action" value="clear">
      <button class="btn danger" type="submit">Tout effacer</button>
    </form>
  </div>

  <?php if (!$rows): ?>
    <div style="margin-top:12px;border:1px solid var(--stroke);background:rgba(255,255,255,.04);padding:14px;border-radius:16px;color:var(--muted);">
      Aucun historique.
    </div>
  <?php else: ?>
    <?php
      $currentLabel = '';
      foreach ($rows as $r):
        $label = dayLabel((string)$r['created_at']);
        if ($label !== $currentLabel):
          if ($currentLabel !== '') echo '</div>';
          echo '<div class="groupTitle">'.htmlspecialchars($label).'</div><div class="grid">';
          $currentLabel = $label;
        endif;
    ?>
      <div class="card">
        <div class="thumb">
          <img src="<?= htmlspecialchars((string)$r['url']) ?>" alt="">
        </div>
        <div class="cardBody">
          <div style="font-weight:900;font-size:13px;">
            <?= htmlspecialchars((string)$r['type']) ?>
          </div>
          <div class="small"><?= htmlspecialchars((string)$r['created_at']) ?></div>

          <div class="actions">
            <form method="post" action="media_set.php">
              <input type="hidden" name="hist_id" value="<?= (int)$r['id'] ?>">
              <button class="btn" type="submit">Réutiliser</button>
            </form>

            <form method="post" onsubmit="return confirm('Supprimer cet élément ?');">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="action" value="delete_one">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn danger" type="submit">Supprimer</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($pages > 1): ?>
    <div class="pager">
      <?php if ($page > 1): ?>
        <a class="btn" href="history.php?type=<?= htmlspecialchars($type) ?>&page=<?= $page-1 ?>">← Précédent</a>
      <?php endif; ?>
      <?php if ($page < $pages): ?>
        <a class="btn" href="history.php?type=<?= htmlspecialchars($type) ?>&page=<?= $page+1 ?>">Suivant →</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</main>
</body>
</html>
