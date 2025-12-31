<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/db.php';
$pdo = db();

$errors = [];
$successMsg = null;

function ensureDir(string $path): void
{
    if (!is_dir($path)) {
        if (!mkdir($path, 0775, true) && !is_dir($path)) {
            throw new RuntimeException("Impossible de créer le dossier: $path");
        }
    }
}

function normalizeFilesArray(array $files): array
{
    if (!isset($files['name'])) return [];

    if (!is_array($files['name'])) {
        return [[
            'name' => $files['name'] ?? '',
            'type' => $files['type'] ?? '',
            'tmp_name' => $files['tmp_name'] ?? '',
            'error' => $files['error'] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'] ?? 0,
        ]];
    }

    $out = [];
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        $out[] = [
            'name' => $files['name'][$i] ?? '',
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];
    }
    return $out;
}

function extFromMime(string $mime, string $fallbackName): ?string
{
    $mime = strtolower(trim($mime));
    return match ($mime) {
        'image/jpeg', 'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
        default => (function () use ($fallbackName) {
            $e = strtolower(pathinfo($fallbackName, PATHINFO_EXTENSION));
            return in_array($e, ['jpg','jpeg','png','webp','pdf'], true) ? ($e === 'jpeg' ? 'jpg' : $e) : null;
        })(),
    };
}

function pageFileName(int $num, string $ext): string
{
    return str_pad((string)$num, 3, '0', STR_PAD_LEFT) . '.' . $ext;
}

// Liste mangas
$mangas = $pdo->query("SELECT id, title FROM mangas ORDER BY id DESC")->fetchAll();

// POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdfProvided = isset($_FILES['pdf']) && ($_FILES['pdf']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
    $imagesProvided = false;

    $mangaId = (int)($_POST['manga_id'] ?? 0);
    $chapterNum = (int)($_POST['chapter_number'] ?? 0);
    $chapterTitle = trim($_POST['chapter_title'] ?? '');
    $publishedAt = trim($_POST['published_at'] ?? '');

    if ($mangaId <= 0) $errors[] = "Choisis un manga.";
    if ($chapterNum <= 0) $errors[] = "Numéro de chapitre invalide.";

    if (isset($_FILES['pages'])) {
        $tmp = normalizeFilesArray($_FILES['pages']);
        foreach ($tmp as $f) {
            if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $imagesProvided = true;
                break;
            }
        }
    }

    $mode = $pdfProvided ? 'pdf' : ($imagesProvided ? 'images' : '');
    if ($mode === '') $errors[] = "Ajoute un PDF OU des images.";
    if ($pdfProvided && $imagesProvided) $errors[] = "Choisis soit un PDF, soit des images (pas les deux).";

    try {
        if (!$errors) {
            // Vérifier manga
            $stmt = $pdo->prepare("SELECT id FROM mangas WHERE id = ?");
            $stmt->execute([$mangaId]);
            if (!$stmt->fetch()) {
                throw new RuntimeException("Manga introuvable.");
            }

            $pdo->beginTransaction();

            // Récupérer ou créer chapitre
            $stmt = $pdo->prepare("SELECT id, pdf_url FROM chapters WHERE manga_id = ? AND number = ?");
            $stmt->execute([$mangaId, $chapterNum]);
            $chapter = $stmt->fetch();

            if (!$chapter) {
                $stmt = $pdo->prepare("INSERT INTO chapters (manga_id, number, title, published_at, pdf_url)
                                       VALUES (?, ?, ?, ?, NULL)");
                $stmt->execute([
                    $mangaId,
                    $chapterNum,
                    $chapterTitle !== '' ? $chapterTitle : null,
                    $publishedAt !== '' ? $publishedAt : null,
                ]);
                $chapterId = (int)$pdo->lastInsertId();
                $currentPdfUrl = null;
            } else {
                $chapterId = (int)$chapter['id'];
                $currentPdfUrl = $chapter['pdf_url'] ?? null;
            }

            // Dossier
            $baseDir = __DIR__ . "/uploads/manga/{$mangaId}/ch{$chapterNum}";
            ensureDir($baseDir);

            if ($mode === 'pdf') {
                $f = $_FILES['pdf'];

                if (($f['size'] ?? 0) > 128 * 1024 * 1024) {
                    throw new RuntimeException("PDF trop gros (max 128MB).");
                }

                $mime = @mime_content_type((string)$f['tmp_name']) ?: (string)($f['type'] ?? '');
                $ext = extFromMime($mime, (string)($f['name'] ?? ''));
                if ($ext !== 'pdf') {
                    throw new RuntimeException("Le fichier doit être un PDF.");
                }

                $destPath = $baseDir . "/chapter.pdf";
                if (!move_uploaded_file((string)$f['tmp_name'], $destPath)) {
                    throw new RuntimeException("Impossible d'enregistrer le PDF.");
                }

                $publicUrl = "/uploads/manga/{$mangaId}/ch{$chapterNum}/chapter.pdf";

                $stmt = $pdo->prepare("UPDATE chapters SET pdf_url = ? WHERE id = ?");
                $stmt->execute([$publicUrl, $chapterId]);

                // éviter mélange PDF+images
                $pdo->prepare("DELETE FROM pages WHERE chapter_id = ?")->execute([$chapterId]);

                $pdo->commit();
                $successMsg = "PDF uploadé ✅ Lire : reader.php?manga={$mangaId}&ch={$chapterNum}";
            }

            if ($mode === 'images') {
                $files = normalizeFilesArray($_FILES['pages']);
                $files = array_values(array_filter($files, fn($f) => ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE));
                if (count($files) === 0) {
                    throw new RuntimeException("Aucun fichier sélectionné.");
                }

                usort($files, fn($a, $b) => strnatcasecmp((string)$a['name'], (string)$b['name']));

                if ($currentPdfUrl) {
                    $pdo->prepare("UPDATE chapters SET pdf_url = NULL WHERE id = ?")->execute([$chapterId]);
                }

                $stmt = $pdo->prepare("SELECT COALESCE(MAX(page_number), 0) AS maxp FROM pages WHERE chapter_id = ?");
                $stmt->execute([$chapterId]);
                $pageNumber = (int)($stmt->fetch()['maxp'] ?? 0) + 1;

                $insert = $pdo->prepare("INSERT INTO pages (chapter_id, page_number, image_url) VALUES (?, ?, ?)");

                foreach ($files as $f) {
                    if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                        throw new RuntimeException("Erreur upload pour " . ($f['name'] ?? 'fichier'));
                    }

                    $mime = @mime_content_type((string)$f['tmp_name']) ?: (string)($f['type'] ?? '');
                    $ext = extFromMime($mime, (string)($f['name'] ?? ''));
                    if ($ext === null || $ext === 'pdf') {
                        throw new RuntimeException("Format autorisé : jpg/png/webp (pas PDF en mode images).");
                    }

                    $filename = pageFileName($pageNumber, $ext);
                    $destPath = $baseDir . '/' . $filename;

                    if (!move_uploaded_file((string)$f['tmp_name'], $destPath)) {
                        throw new RuntimeException("Impossible de déplacer le fichier : " . ($f['name'] ?? ''));
                    }

                    $publicUrl = "/uploads/manga/{$mangaId}/ch{$chapterNum}/{$filename}";
                    $insert->execute([$chapterId, $pageNumber, $publicUrl]);
                    $pageNumber++;
                }

                $pdo->commit();
                $successMsg = "Images uploadées ✅ Lire : reader.php?manga={$mangaId}&ch={$chapterNum}";
            }
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errors[] = "Erreur : " . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Upload chapitre (PDF ou Images)</title>
  <style>
    *{box-sizing:border-box}
    body{margin:0;font-family:Arial;background:#0b0f18;color:#e5e7eb}
    .wrap{max-width:900px;margin:0 auto;padding:22px 14px}
    .card{border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);border-radius:16px;padding:16px;margin-top:14px}
    label{display:block;margin-top:12px;font-weight:700}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    @media(max-width:720px){.row{grid-template-columns:1fr}}
    .nav a{color:#e5e7eb;text-decoration:none;border:1px solid rgba(255,255,255,.12);padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.05);margin-right:8px;display:inline-block}
    button{width:100%;margin-top:14px;padding:12px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.08);color:#e5e7eb;font-weight:800;cursor:pointer}
    .msg{padding:10px 12px;border-radius:12px;margin-top:10px;border:1px solid rgba(255,255,255,.12)}
    .err{background:rgba(239,68,68,.18)}
    .ok{background:rgba(34,197,94,.18)}
    .hint{color:#a7b0c0;font-size:13px;margin-top:8px;line-height:1.35}

    /* --- FIX tailles inputs/select/textarea --- */
    input, select, textarea{
      width:100%;
      box-sizing:border-box;
      border-radius:12px;
      border:1px solid rgba(255,255,255,.12);
      background:rgba(0,0,0,.2);
      color:#e5e7eb;

      height:44px;
      padding:10px 12px;
      line-height:22px;
      font-size:14px;
    }

    textarea{
      height:auto;
      min-height:44px;
      resize:vertical;
    }

    select{
      appearance:none;
      -webkit-appearance:none;
      -moz-appearance:none;

      padding-right:40px;

      background-image:
        linear-gradient(45deg, transparent 50%, rgba(229,231,235,.85) 50%),
        linear-gradient(135deg, rgba(229,231,235,.85) 50%, transparent 50%),
        linear-gradient(to right, transparent, transparent);
      background-position:
        calc(100% - 18px) 19px,
        calc(100% - 13px) 19px,
        0 0;
      background-size:5px 5px, 5px 5px, 100% 100%;
      background-repeat:no-repeat;
    }

    input[type="file"]{
      height:auto;
      padding:10px 12px;
    }

    input:focus, select:focus, textarea:focus{
      outline:none;
      border-color: rgba(255,255,255,.22);
      box-shadow: 0 0 0 4px rgba(99,102,241,.12);
    }
    /* --- Select Manga : style dark propre --- */
    select{
      background-color: rgba(15,23,42,.95);
      color: #e5e7eb;
    }

    /* options (fonctionne bien sur Firefox / partiellement Chrome) */
    select option{
      background-color: #0b0f18;
      color: #e5e7eb;
    }

    /* option sélectionnée */
    select option:checked{
      background-color: #6366f1;
      color: #ffffff;
    }

    /* hover (Firefox surtout) */
    select option:hover{
      background-color: #4f46e5;
      color: #ffffff;
    }
    select{
      background:
        linear-gradient(180deg, rgba(99,102,241,.12), rgba(0,0,0,0)),
        rgba(15,23,42,.95);
    }
    /* --- File input custom (PDF / Images) --- */
    .fileField{
      margin-top:10px;
      display:flex;
      align-items:center;
      gap:10px;
      border:1px solid rgba(255,255,255,.12);
      background: rgba(0,0,0,.20);
      border-radius:14px;
      padding:10px;
    }

    .fileInput{
      position:absolute;
      left:-9999px; /* cache l'input natif */
    }

    .fileBtn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:10px 12px;
      border-radius:12px;
      border:1px solid rgba(255,255,255,.12);
      background: rgba(255,255,255,.08);
      color:#e5e7eb;
      font-weight:900;
      cursor:pointer;
      transition: transform .12s ease, background .12s ease, border-color .12s ease;
      white-space:nowrap;
    }

    .fileBtn:hover{
      transform: translateY(-1px);
      border-color: rgba(255,255,255,.18);
      background: rgba(255,255,255,.11);
    }

    .fileName{
      flex:1;
      min-width:0;
      color:#a7b0c0;
      font-size:13px;
      padding:10px 10px;
      border-radius:12px;
      border:1px dashed rgba(255,255,255,.10);
      background: rgba(255,255,255,.03);
      overflow:hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .fileBtn{
      box-shadow: 0 0 0 0 rgba(99,102,241,.0);
    }
    .fileBtn:hover{
      box-shadow: 0 0 0 4px rgba(99,102,241,.12);
    }
  </style>
</head>
<script>
  const pdf = document.getElementById('pdfFile');
  const pdfName = document.getElementById('pdfName');
  if (pdf && pdfName) {
    pdf.addEventListener('change', () => {
      pdfName.textContent = pdf.files && pdf.files.length ? pdf.files[0].name : 'Aucun fichier choisi';
    });
  }

  const imgs = document.getElementById('imgFiles');
  const imgName = document.getElementById('imgName');
  if (imgs && imgName) {
    imgs.addEventListener('change', () => {
      if (!imgs.files || !imgs.files.length) {
        imgName.textContent = 'Aucun fichier choisi';
        return;
      }
      imgName.textContent = imgs.files.length === 1
        ? imgs.files[0].name
        : imgs.files.length + ' fichiers sélectionnés';
    });
  }
</script>
<body>
<div class="wrap">
  <div class="nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="catalog.php">Catalogue</a>
  </div>

  <div class="card">
    <h2 style="margin:0 0 8px;">Upload chapitre</h2>
    <div class="hint">Ajoute <b>un PDF</b> OU <b>des images</b> (pas les deux). Le système détecte automatiquement.</div>

    <?php if ($successMsg): ?>
      <div class="msg ok"><?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>
    <?php foreach ($errors as $e): ?>
      <div class="msg err"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data">
      <label>Manga</label>
      <select name="manga_id" required>
        <option value="">— Choisir —</option>
        <?php foreach ($mangas as $m): ?>
          <option value="<?= (int)$m['id'] ?>"><?= (int)$m['id'] ?> — <?= htmlspecialchars($m['title']) ?></option>
        <?php endforeach; ?>
      </select>

      <div class="row">
        <div>
          <label>Numéro du chapitre</label>
          <input type="number" name="chapter_number" min="1" required>
        </div>
        <div>
          <label>Date (optionnel)</label>
          <input type="date" name="published_at">
        </div>
      </div>

      <label>Titre du chapitre (optionnel)</label>
      <textarea name="chapter_title" placeholder="ex: Le début"></textarea>

      <div class="card" style="margin-top:12px">
        <b>Mode PDF</b>
        <div class="fileField">
          <input id="pdfFile" class="fileInput" type="file" name="pdf" accept="application/pdf">
          <label class="fileBtn" for="pdfFile">Choisir un PDF</label>
          <div class="fileName" id="pdfName">Aucun fichier choisi</div>
        </div>
        <div class="hint">Upload un seul PDF. Il remplacera les pages images du chapitre.</div>
      </div>


      <div class="card" style="margin-top:12px">
        <b>Mode Images</b>

        <div class="fileField">
          <input id="imgFiles" class="fileInput" type="file" name="pages[]" accept="image/jpeg,image/png,image/webp" multiple>
          <label class="fileBtn" for="imgFiles">Choisir des images</label>
          <div class="fileName" id="imgName">Aucun fichier choisi</div>
        </div>

        <div class="hint">Upload plusieurs images. Elles seront renommées automatiquement en 001.jpg, 002.jpg…</div>
      </div>


      <button type="submit">Uploader</button>
    </form>
  </div>
</div>
</body>
</html>
