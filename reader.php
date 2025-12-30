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
if ($mangaId <= 0 || $chNum <= 0) {
    http_response_code(400);
    echo "Paramètres invalides.";
    exit;
}

$pdo = db();

// Marquer comme lu


$stmt = $pdo->prepare("SELECT id, title FROM mangas WHERE id = ?");
$stmt->execute([$mangaId]);
$manga = $stmt->fetch();
if (!$manga) { http_response_code(404); exit("Manga introuvable."); }

$stmt = $pdo->prepare("SELECT id, number, title, published_at, pdf_url
                       FROM chapters
                       WHERE manga_id = ? AND number = ?");
$stmt->execute([$mangaId, $chNum]);
$chapter = $stmt->fetch();
if (!$chapter) { http_response_code(404); exit("Chapitre introuvable."); }

$chapterId = (int)$chapter['id'];
$pdo->prepare("INSERT INTO chapter_reads (user_id, chapter_id) VALUES (?, ?)
               ON DUPLICATE KEY UPDATE read_at = CURRENT_TIMESTAMP")
    ->execute([(int)$_SESSION['user_id'], $chapterId]);

$pdfUrl = $chapter['pdf_url'] ?? null;

$username = (string)($_SESSION['username'] ?? 'Utilisateur');
$title = (string)$manga['title'];

$pages = [];
if (!$pdfUrl) {
    $stmt = $pdo->prepare("SELECT page_number, image_url
                           FROM pages
                           WHERE chapter_id = ?
                           ORDER BY page_number ASC");
    $stmt->execute([(int)$chapter['id']]);
    $pages = $stmt->fetchAll();
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($title) ?> — Chapitre <?= (int)$chapter['number'] ?></title>
  <style>
    body{margin:0;font-family:Arial;background:#0b0f18;color:#e5e7eb}
    .bar{position:sticky;top:0;background:rgba(10,14,24,.75);backdrop-filter:blur(10px);
         border-bottom:1px solid rgba(255,255,255,.12);padding:10px 12px;z-index:5}
    .bar .row{max-width:1100px;margin:0 auto;display:flex;gap:10px;align-items:center;justify-content:space-between;flex-wrap:wrap}
    .btn{color:#e5e7eb;text-decoration:none;border:1px solid rgba(255,255,255,.12);
         padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.05);font-weight:700;font-size:13px}
    .wrap{max-width:1100px;margin:0 auto;padding:14px}
    .pages{display:flex;flex-direction:column;gap:12px;align-items:center}
    .page{width:min(900px,100%);border:1px solid rgba(255,255,255,.12);border-radius:16px;overflow:hidden;background:rgba(255,255,255,.05)}
    img{display:block;width:100%;height:auto}
    .meta{padding:10px 12px;color:#a7b0c0;font-size:12px;border-top:1px solid rgba(255,255,255,.12)}

    /* PDF viewer */
    .pdfBox{width:min(920px, 100%);border:1px solid rgba(255,255,255,.12);
            background:rgba(255,255,255,.05);border-radius:16px;overflow:hidden}
    .pdfTools{display:flex;gap:10px;align-items:center;justify-content:center;flex-wrap:wrap;
              padding:10px;border-bottom:1px solid rgba(255,255,255,.12)}
    .pdfTools button{cursor:pointer;padding:8px 12px;border-radius:999px;border:1px solid rgba(255,255,255,.12);
                     background:rgba(255,255,255,.06);color:#e5e7eb;font-weight:800}
    .pdfTools .info{color:#a7b0c0;font-size:13px}
    canvas{display:block;margin:0 auto;max-width:100%;height:auto;background:#111}
    .empty{color:#a7b0c0;text-align:center;padding:16px}
  </style>
</head>
<body>

<div class="bar">
  <div class="row">
    <div>
      <b><?= htmlspecialchars($title) ?></b> — Chapitre <?= (int)$chapter['number'] ?>
      <span style="color:#a7b0c0;font-size:12px;">(<?= htmlspecialchars($username) ?>)</span>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <a class="btn" href="manga.php?id=<?= (int)$mangaId ?>">← Menu manga</a>
      <a class="btn" href="catalog.php">Catalogue</a>
    </div>
  </div>
</div>

<div class="wrap">
  <?php if ($pdfUrl): ?>
    <div class="pdfBox">
      <div class="pdfTools">
        <button id="prev">⟵ Prev</button>
        <button id="next">Next ⟶</button>
        <div class="info">
          Page <span id="page_num">1</span> / <span id="page_count">?</span>
        </div>
      </div>
      <canvas id="the-canvas"></canvas>
      <div class="meta">Lecture PDF : <?= htmlspecialchars((string)$pdfUrl) ?></div>
    </div>

    <!-- PDF.js via CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.6.82/pdf.min.js"></script>
    <script>
      const url = <?= json_encode($pdfUrl) ?>;

      // Config worker
      pdfjsLib.GlobalWorkerOptions.workerSrc =
        "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.6.82/pdf.worker.min.js";

      let pdfDoc = null;
      let pageNum = 1;
      let pageRendering = false;
      let pageNumPending = null;
      const scale = 1.35;

      const canvas = document.getElementById('the-canvas');
      const ctx = canvas.getContext('2d');

      function renderPage(num) {
        pageRendering = true;
        pdfDoc.getPage(num).then(function(page) {
          const viewport = page.getViewport({ scale });
          canvas.height = viewport.height;
          canvas.width = viewport.width;

          const renderContext = { canvasContext: ctx, viewport: viewport };
          const renderTask = page.render(renderContext);

          renderTask.promise.then(function() {
            pageRendering = false;
            document.getElementById('page_num').textContent = num;
            if (pageNumPending !== null) {
              renderPage(pageNumPending);
              pageNumPending = null;
            }
          });
        });
      }

      function queueRenderPage(num) {
        if (pageRendering) pageNumPending = num;
        else renderPage(num);
      }

      function onPrevPage() {
        if (pageNum <= 1) return;
        pageNum--;
        queueRenderPage(pageNum);
      }
      function onNextPage() {
        if (pageNum >= pdfDoc.numPages) return;
        pageNum++;
        queueRenderPage(pageNum);
      }

      document.getElementById('prev').addEventListener('click', onPrevPage);
      document.getElementById('next').addEventListener('click', onNextPage);

      pdfjsLib.getDocument(url).promise.then(function(pdfDoc_) {
        pdfDoc = pdfDoc_;
        document.getElementById('page_count').textContent = pdfDoc.numPages;
        renderPage(pageNum);
      });
    </script>

  <?php else: ?>

    <?php if (!$pages): ?>
      <div class="empty">
        Aucune page image et aucun PDF pour ce chapitre.<br>
        Va sur <a class="btn" href="upload_chapter.php">Upload</a>
      </div>
    <?php else: ?>
      <div class="pages">
        <?php foreach ($pages as $p): ?>
          <div class="page">
            <img src="<?= htmlspecialchars((string)$p['image_url']) ?>" alt="Page <?= (int)$p['page_number'] ?>" loading="lazy">
            <div class="meta">Page <?= (int)$p['page_number'] ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php endif; ?>
</div>
</body>
</html>
