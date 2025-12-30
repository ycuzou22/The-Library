<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/db.php';

$mangaId = (int)($_GET['manga'] ?? 0);
$chNum   = (int)($_GET['ch'] ?? 0);
$page    = max(1, (int)($_GET['page'] ?? 1));

if ($mangaId <= 0 || $chNum <= 0) {
    http_response_code(400);
    exit('Paramètres invalides.');
}

$pdo = db();



$stmt = $pdo->prepare("SELECT id, title FROM mangas WHERE id=?");
$stmt->execute([$mangaId]);
$manga = $stmt->fetch();
if (!$manga) { http_response_code(404); exit('Manga introuvable.'); }

$stmt = $pdo->prepare("SELECT id, number, title, pdf_url
                       FROM chapters
                       WHERE manga_id=? AND number=?");
$stmt->execute([$mangaId, $chNum]);
$chapter = $stmt->fetch();
if (!$chapter) { http_response_code(404); exit('Chapitre introuvable.'); }

$chapterId = (int)$chapter['id'];
$pdo->prepare("INSERT INTO chapter_reads (user_id, chapter_id) VALUES (?, ?)
               ON DUPLICATE KEY UPDATE read_at = CURRENT_TIMESTAMP")
    ->execute([(int)$_SESSION['user_id'], $chapterId]);

$pdfUrl = trim((string)($chapter['pdf_url'] ?? ''));
if ($pdfUrl === '') { http_response_code(404); exit("Aucun PDF pour ce chapitre."); }

// Chapitres prev/next (navigation comme un site de scan)
$stmt = $pdo->prepare("SELECT MIN(number) AS min_ch, MAX(number) AS max_ch FROM chapters WHERE manga_id=?");
$stmt->execute([$mangaId]);
$range = $stmt->fetch() ?: ['min_ch' => null, 'max_ch' => null];

$prevCh = ($range['min_ch'] !== null && $chNum > (int)$range['min_ch']) ? $chNum - 1 : null;
$nextCh = ($range['max_ch'] !== null && $chNum < (int)$range['max_ch']) ? $chNum + 1 : null;

$username = (string)($_SESSION['username'] ?? 'Utilisateur');

// URL PDF avec page/zoom (souvent supporté)
$pdfWithParams = $pdfUrl . '#page=' . $page . '&zoom=page-width';
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($manga['title']) ?> — Chapitre <?= (int)$chapter['number'] ?> (PDF)</title>
  <style>
    :root{
      --bg:#0b0f18; --panel:rgba(255,255,255,.06); --stroke:rgba(255,255,255,.12);
      --text:#e5e7eb; --muted:#a7b0c0;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:Arial;background:var(--bg);color:var(--text);min-height:100vh}
    header{
      position:sticky;top:0;z-index:10;
      background:rgba(10,14,24,.75);backdrop-filter:blur(10px);
      border-bottom:1px solid var(--stroke);
    }
    .inner{
      max-width:1400px;margin:0 auto;padding:10px 12px;
      display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;
    }
    .title{font-weight:800}
    .sub{color:var(--muted);font-size:12px;margin-top:2px}
    .btn{
      border:1px solid var(--stroke); background:rgba(255,255,255,.05);
      color:var(--text); padding:8px 12px;border-radius:999px;
      font-weight:700; font-size:13px; cursor:pointer; text-decoration:none;
      display:inline-flex; align-items:center; gap:8px;
    }
    .btn[disabled]{opacity:.5;cursor:not-allowed}
    .tools{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    .wrap{max-width:1400px;margin:0 auto;padding:12px}
    .viewer{
      border:1px solid var(--stroke); background:var(--panel);
      border-radius:16px; overflow:hidden;
      height: calc(100vh - 92px);
      min-height: 520px;
    }
    iframe{
      width:100%; height:100%;
      border:0;
      background:#111;
    }
    input[type="number"]{
      width:90px;
      padding:8px 10px;border-radius:999px;
      border:1px solid var(--stroke);
      background:rgba(0,0,0,.25);
      color:var(--text);
      outline:none;
      font-weight:700;
    }
    .hint{color:var(--muted);font-size:12px}
  </style>
</head>
<body>

<header>
  <div class="inner">
    <div>
      <div class="title"><?= htmlspecialchars($manga['title']) ?> — Chapitre <?= (int)$chapter['number'] ?></div>
      <div class="sub">Connecté : <?= htmlspecialchars($username) ?></div>
    </div>

    <div class="tools">
      <a class="btn" href="manga.php?id=<?= (int)$mangaId ?>">← Menu</a>

      <?php if ($prevCh !== null): ?>
        <a class="btn" href="pdf_viewer.php?manga=<?= (int)$mangaId ?>&ch=<?= (int)$prevCh ?>">⟵ Chap. <?= (int)$prevCh ?></a>
      <?php else: ?>
        <span class="btn" style="opacity:.5">⟵ Chap. -</span>
      <?php endif; ?>

      <?php if ($nextCh !== null): ?>
        <a class="btn" href="pdf_viewer.php?manga=<?= (int)$mangaId ?>&ch=<?= (int)$nextCh ?>">Chap. <?= (int)$nextCh ?> ⟶</a>
      <?php else: ?>
        <span class="btn" style="opacity:.5">Chap. - ⟶</span>
      <?php endif; ?>

      <form method="get" style="display:inline-flex;gap:8px;align-items:center;margin:0">
        <input type="hidden" name="manga" value="<?= (int)$mangaId ?>">
        <input type="hidden" name="ch" value="<?= (int)$chNum ?>">
        <span class="hint">Page</span>
        <input type="number" name="page" min="1" value="<?= (int)$page ?>">
        <button class="btn" type="submit">Aller</button>
      </form>

      <a class="btn" href="<?= htmlspecialchars($pdfUrl) ?>" target="_blank" rel="noopener">Ouvrir PDF ↗</a>
    </div>
  </div>
</header>

<main class="wrap">
  <section class="viewer">
    <!-- Viewer PDF natif du navigateur -->
    <iframe src="<?= htmlspecialchars($pdfWithParams) ?>"></iframe>
  </section>
</main>

</body>
</html>
