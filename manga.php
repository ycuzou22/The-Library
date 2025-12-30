<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/db.php';

$username = (string)($_SESSION['username'] ?? 'Utilisateur');
$mangaId = (int)($_GET['id'] ?? 0);

if ($mangaId <= 0) {
    http_response_code(400);
    exit("ID manga invalide.");
}

$pdo = db();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = (string)$_SESSION['csrf'];
$userId = (int)$_SESSION['user_id'];

$isFav = false;
$stmt = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id=? AND manga_id=? LIMIT 1");
$stmt->execute([(int)$_SESSION['user_id'], $mangaId]);
$isFav = (bool)$stmt->fetchColumn();


/** Manga */
$stmt = $pdo->prepare("
    SELECT id, title, alt_title, status, synopsis, cover_url
    FROM mangas
    WHERE id = ?
");
$stmt->execute([$mangaId]);
$manga = $stmt->fetch();
$coverUrl = trim((string)($manga['cover_url'] ?? ''));

if (!$manga) {
    http_response_code(404);
    exit("Manga introuvable.");
}

/** Chapitres (réellement uploadés) */
$stmt = $pdo->prepare("
  SELECT
    c.id,
    c.number,
    c.title,
    c.published_at,
    c.pdf_url,
    (SELECT COUNT(*) FROM pages p WHERE p.chapter_id = c.id) AS pages_count,
    cr.read_at AS read_at
  FROM chapters c
  LEFT JOIN chapter_reads cr
    ON cr.chapter_id = c.id AND cr.user_id = ?
  WHERE c.manga_id = ?
  ORDER BY c.number DESC
");
$stmt->execute([$userId, $mangaId]);
$chapters = $stmt->fetchAll();

function chapterLink(int $mangaId, array $ch): string
{
    $num = (int)($ch['number'] ?? 0);
    $pdf = trim((string)($ch['pdf_url'] ?? ''));

    if ($num <= 0) {
        return '#';
    }

    // PDF -> pdf_viewer
    if ($pdf !== '') {
        return "pdf_viewer.php?manga={$mangaId}&ch={$num}";
    }

    // Images -> reader
    return "reader.php?manga={$mangaId}&ch={$num}";
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars((string)$manga['title']) ?> — Menu</title>
  <style>
    :root{
      --bg0:#0b0f18; --bg1:#0f172a;
      --card: rgba(255,255,255,.06); --card2: rgba(255,255,255,.09);
      --text:#e5e7eb; --muted:#a7b0c0; --stroke: rgba(255,255,255,.10);
      --glow: rgba(99,102,241,.35); --glow2: rgba(236,72,153,.22);
      --good: rgba(34,197,94,.18);
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
      max-width:1100px; margin:0 auto; padding:22px 18px 14px;
      display:flex; align-items:center; justify-content:space-between; gap:12px;
    }
    .nav{display:flex; gap:10px; flex-wrap:wrap}
    .nav a{
      text-decoration:none; color:var(--text);
      border:1px solid var(--stroke);
      background: rgba(255,255,255,.04);
      padding:8px 12px; border-radius:999px;
      font-size:13px;
    }
    .wrap{max-width:1100px;margin:0 auto;padding:8px 18px 34px}

    .hero{
      border:1px solid var(--stroke);
      background: linear-gradient(180deg, var(--card), rgba(255,255,255,.03));
      border-radius:18px;
      overflow:hidden;
      display:grid;
      grid-template-columns: 280px 1fr;
      gap:0;
    }
    @media (max-width: 820px){ .hero{ grid-template-columns: 1fr; } }

      .cover{
      min-height:220px;
      position:relative;
      border-right:1px solid var(--stroke);
      display:flex; align-items:flex-end; justify-content:flex-start;
      padding:12px;
      overflow:hidden;
      background:
        radial-gradient(600px 220px at 30% 0%, rgba(99,102,241,.25), transparent 60%),
        radial-gradient(600px 220px at 70% 0%, rgba(236,72,153,.18), transparent 60%),
        linear-gradient(135deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
    }
    @media (max-width: 820px){ .cover{ border-right:none; border-bottom:1px solid var(--stroke);} }

    .cover img{
      position:absolute;
      inset:0;
      width:100%;
      height:100%;
      object-fit:cover;
      filter: saturate(1.05) contrast(1.02);
      transform: scale(1.02);
    }

    /* petit voile pour garder le texte lisible */
    .cover:after{
      content:"";
      position:absolute;
      inset:0;
      background: linear-gradient(180deg, rgba(0,0,0,.15), rgba(0,0,0,.55));
    }

    .badge{
      position:relative;
      z-index:2;
    }

    .badge.ok{ background: var(--good); }

    .info{ padding:16px 16px 18px; }
    .info h1{ margin:0; font-size:22px; }
    .alt{ margin-top:6px; color:var(--muted); }
    .pills{ display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
    .pill{
      font-size:12px; color:var(--muted);
      border:1px solid var(--stroke);
      background: rgba(255,255,255,.04);
      padding:6px 10px; border-radius:999px;
    }

    .synopsis{
      margin-top:12px;
      color:var(--muted);
      line-height:1.45;
      font-size:13.5px;
      white-space:pre-wrap;
    }

    .sectionTitle{
      margin:18px 0 10px;
      font-size:14px;
      color:var(--muted);
      display:flex; align-items:center; justify-content:space-between;
      gap:10px; flex-wrap:wrap;
    }
    .sectionTitle a{
      text-decoration:none;
      color:var(--text);
      border:1px solid var(--stroke);
      background: rgba(255,255,255,.04);
      padding:8px 12px; border-radius:999px;
      font-size:13px;
    }

    .chapters{
      border:1px solid var(--stroke);
      background: rgba(255,255,255,.04);
      border-radius:18px;
      overflow:hidden;
    }
    .chapter{
      display:flex; align-items:center; justify-content:space-between;
      padding:12px 14px;
      border-top:1px solid var(--stroke);
      text-decoration:none; color:inherit;
      background: rgba(255,255,255,.00);
      transition: background .12s ease;
    }
    .chapter:first-child{ border-top:none; }
    .chapter:hover{ background: rgba(255,255,255,.06); }
    .left{ display:flex; flex-direction:column; gap:3px; }
    .chTitle{ font-weight:800; font-size:13.5px; }
    .chMeta{ color:var(--muted); font-size:12px; }
    .readBtn{
      border:1px solid var(--stroke);
      background: rgba(255,255,255,.06);
      padding:8px 10px;
      border-radius:12px;
      font-size:12px;
      color:var(--text);
      white-space:nowrap;
    }
    .empty{
      padding:16px;
      color:var(--muted);
      font-size:13px;
    }
    code{background:rgba(255,255,255,.06);padding:2px 6px;border-radius:8px;border:1px solid var(--stroke)}
        .chapter.read .chTitle,
    .chapter.read .chMeta { opacity: .55; }
    .newBadge{
      display:inline-flex;
      align-items:center;
      gap:6px;
      margin-left:8px;
      padding:2px 8px;
      border-radius:999px;
      border:1px solid rgba(255,255,255,.16);
      background: rgba(34,197,94,.16);
      font-size:11px;
      font-weight:900;
    }
    @keyframes slowBlink {
      0%, 100% { opacity: 1; }
      50% { opacity: .35; }
    }
    .newBadge.blink { animation: slowBlink 1.8s ease-in-out infinite; }

    .actionsRight{
      display:flex; gap:8px; align-items:center; flex-wrap:wrap;
    }
    .smallBtn{
      border:1px solid var(--stroke);
      background: rgba(255,255,255,.05);
      color: var(--text);
      padding:7px 10px;
      border-radius:12px;
      font-size:12px;
      font-weight:900;
      cursor:pointer;
    }
    .smallBtn.danger{ background: rgba(239,68,68,.14); }
  </style>
</head>
<body>

<header class="topbar">
  <div class="nav">
    <a href="catalog.php">← Catalogue</a>
    <a href="dashboard.php">Dashboard</a>
  </div>
  <div class="nav">
    <a href="upload_chapter.php">Upload chapitre</a>
    <a href="logout.php">Déconnexion (<?= htmlspecialchars($username) ?>)</a>
    <form method="post" action="favorite_toggle.php" style="margin:0;display:inline;">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="manga_id" value="<?= (int)$mangaId ?>">
      <input type="hidden" name="back" value="<?= htmlspecialchars("manga.php?id={$mangaId}") ?>">
      <button type="submit" style="border:1px solid var(--stroke);background:rgba(255,255,255,.04);color:var(--text);padding:8px 12px;border-radius:999px;font-size:13px;cursor:pointer;">
        <?= $isFav ? '★ Retirer' : '☆ Ajouter' ?>
      </button>
    </form>
  </div>
</header>

<main class="wrap">
  <section class="hero">
    <div class="cover" aria-hidden="true">
      <?php if ($coverUrl !== ''): ?>
        <img src="<?= htmlspecialchars($coverUrl) ?>" alt="">
      <?php endif; ?>
      <span class="badge ok"><?= htmlspecialchars((string)$manga['status']) ?></span>
    </div>
    <div class="info">
      <h1><?= htmlspecialchars((string)$manga['title']) ?></h1>
      <div class="alt"><?= htmlspecialchars((string)($manga['alt_title'] ?? '')) ?></div>

      <div class="pills">
        <span class="pill">ID: <?= (int)$manga['id'] ?></span>
        <span class="pill">📚 <?= count($chapters) ?> chapitre(s)</span>
      </div>

      <?php if (!empty($manga['synopsis'])): ?>
        <div class="synopsis"><?= htmlspecialchars((string)$manga['synopsis']) ?></div>
      <?php endif; ?>
    </div>
  </section>
  <div class="sectionTitle">
    <span>Chapitres (uploadés)</span>

    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
      <a href="upload_chapter.php">+ Uploader</a>

      <form method="post" action="read_reset.php" style="margin:0;">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="action" value="reset_all">
        <input type="hidden" name="manga_id" value="<?= (int)$mangaId ?>">
        <input type="hidden" name="back" value="<?= htmlspecialchars("manga.php?id={$mangaId}") ?>">
        <button class="smallBtn danger" type="submit"
                onclick="return confirm('Réinitialiser le statut LU de tous les chapitres ?');">
          Tout réinitialiser
        </button>
      </form>
    </div>
  </div>
  <section class="chapters">
    <?php if (!$chapters): ?>
      <div class="empty">
        Aucun chapitre uploadé.<br>
        Va sur <a href="upload_chapter.php" style="color:inherit;">upload_chapter.php</a> puis upload un PDF ou des images.
      </div>
    <?php else: ?>
      <?php foreach ($chapters as $ch): ?>
        <?php
          $href = chapterLink($mangaId, $ch);
          $isPdf = trim((string)($ch['pdf_url'] ?? '')) !== '';
          $isRead = !empty($ch['read_at']);
          $chapterId = (int)$ch['id'];
        ?>
        <div class="chapter <?= $isRead ? 'read' : '' ?>" style="display:flex;align-items:center;justify-content:space-between;">
          <a class="chapter" href="<?= htmlspecialchars($href) ?>" style="flex:1;border-top:none;">
            <div class="left">
              <div class="chTitle">
                Chapitre <?= (int)$ch['number'] ?>
                <?= $ch['title'] ? ' — ' . htmlspecialchars((string)$ch['title']) : '' ?>
                <?= $isPdf ? ' (PDF)' : '' ?>

                <?php if (!$isRead): ?>
                  <span class="newBadge blink">NEW</span>
                <?php endif; ?>
              </div>
              <div class="chMeta">
                <?= $ch['published_at'] ? 'Publié le ' . htmlspecialchars((string)$ch['published_at']) . ' • ' : '' ?>
                <?= $isPdf ? 'Lecteur PDF' : ((int)$ch['pages_count'] . ' page(s)') ?>
                <?= $isRead ? ' • Lu' : '' ?>
              </div>
            </div>
            <div class="readBtn">Lire →</div>
          </a>

          <div class="actionsRight" style="padding:0 12px;">
            <form method="post" action="read_reset.php" style="margin:0;">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="action" value="reset_one">
              <input type="hidden" name="chapter_id" value="<?= $chapterId ?>">
              <input type="hidden" name="back" value="<?= htmlspecialchars("manga.php?id={$mangaId}") ?>">
              <button class="smallBtn" type="submit" <?= $isRead ? '' : 'disabled' ?>
                      title="Remettre ce chapitre en non-lu">
                Reset
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>
</main>

</body>
</html>
