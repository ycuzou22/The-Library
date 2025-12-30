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

$errors = [];
$success = null;

$title = trim((string)($_POST['title'] ?? ''));
$altTitle = trim((string)($_POST['alt_title'] ?? ''));
$status = trim((string)($_POST['status'] ?? 'En cours'));
$synopsis = trim((string)($_POST['synopsis'] ?? ''));
$coverUrl = trim((string)($_POST['cover_url'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($title === '') {
        $errors[] = "Le titre est obligatoire.";
    } elseif (mb_strlen($title) > 120) {
        $errors[] = "Titre trop long (max 120).";
    }

    if ($altTitle !== '' && mb_strlen($altTitle) > 120) {
        $errors[] = "Titre alternatif trop long (max 120).";
    }

    if (!in_array($status, ['En cours', 'Terminé'], true)) {
        $errors[] = "Statut invalide.";
    }

    if ($coverUrl !== '' && mb_strlen($coverUrl) > 255) {
        $errors[] = "Cover URL trop longue (max 255).";
    }

    if (!$errors) {
        // Anti-doublon simple
        $stmt = $pdo->prepare("SELECT id FROM mangas WHERE title = ? LIMIT 1");
        $stmt->execute([$title]);
        if ($stmt->fetch()) {
            $errors[] = "Ce manga existe déjà.";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO mangas (title, alt_title, status, synopsis, cover_url)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $title,
                $altTitle !== '' ? $altTitle : null,
                $status,
                $synopsis !== '' ? $synopsis : null,
                $coverUrl !== '' ? $coverUrl : null,
            ]);

            $newId = (int)$pdo->lastInsertId();
            $success = "Manga ajouté ✅ (id={$newId})";

            // reset form
            $title = $altTitle = $synopsis = $coverUrl = '';
            $status = 'En cours';
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Ajouter un manga</title>
  <style>
    :root{
      --bg0:#0b0f18; --bg1:#0f172a;
      --card: rgba(255,255,255,.06);
      --text:#e5e7eb; --muted:#a7b0c0; --stroke: rgba(255,255,255,.10);
      --glow: rgba(99,102,241,.35); --glow2: rgba(236,72,153,.22);
      --ok: rgba(34,197,94,.18); --bad: rgba(239,68,68,.18);
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
      max-width:1000px; margin:0 auto; padding:22px 16px 14px;
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
    .wrap{max-width:1000px;margin:0 auto;padding:8px 16px 34px}
    .card{
      border:1px solid var(--stroke);
      background: linear-gradient(180deg, var(--card), rgba(255,255,255,.03));
      border-radius:18px;
      padding:16px;
    }
    label{display:block;margin-top:12px;font-weight:800;font-size:13px}
    input, select, textarea{
      width:100%;
      margin-top:6px;
      background: rgba(0,0,0,.18);
      border:1px solid var(--stroke);
      color:var(--text);
      padding:10px 12px;
      border-radius:12px;
      outline:none;
    }
    textarea{min-height:120px;resize:vertical}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    @media(max-width:720px){.row{grid-template-columns:1fr}}
    button{
      margin-top:14px;
      width:100%;
      padding:12px 14px;
      border-radius:12px;
      border:1px solid var(--stroke);
      background: rgba(255,255,255,.08);
      color:var(--text);
      cursor:pointer;
      font-weight:900;
    }
    .msg{
      margin-top:10px;
      padding:10px 12px;
      border-radius:12px;
      border:1px solid var(--stroke);
      font-size:13px;
    }
    .ok{background:var(--ok)}
    .bad{background:var(--bad)}
    .hint{color:var(--muted);font-size:12.5px;margin-top:6px;line-height:1.35}
  </style>
</head>
<body>

<header class="topbar">
  <div>
    <div style="font-weight:900;font-size:18px;">Ajouter un manga</div>
    <div style="color:var(--muted);font-size:13px;">Connecté : <?= htmlspecialchars($username) ?></div>
  </div>
  <nav class="nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="catalog.php">Catalogue</a>
    <a href="upload_chapter.php">Upload chapitre</a>
    <a href="logout.php">Déconnexion</a>
  </nav>
</header>

<main class="wrap">
  <section class="card">
    <?php if ($success): ?>
      <div class="msg ok"><?= htmlspecialchars($success) ?> — <a href="catalog.php" style="color:inherit">Voir le catalogue</a></div>
    <?php endif; ?>

    <?php foreach ($errors as $e): ?>
      <div class="msg bad"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <form method="post">
      <label for="title">Titre *</label>
      <input id="title" name="title" value="<?= htmlspecialchars($title) ?>" required>

      <div class="row">
        <div>
          <label for="alt_title">Titre alternatif</label>
          <input id="alt_title" name="alt_title" value="<?= htmlspecialchars($altTitle) ?>">
        </div>
        <div>
          <label for="status">Statut</label>
          <select id="status" name="status">
            <option value="En cours" <?= $status === 'En cours' ? 'selected' : '' ?>>En cours</option>
            <option value="Terminé" <?= $status === 'Terminé' ? 'selected' : '' ?>>Terminé</option>
          </select>
        </div>
      </div>

      <label for="cover_url">Cover URL (optionnel)</label>
      <input id="cover_url" name="cover_url" value="<?= htmlspecialchars($coverUrl) ?>">
      <div class="hint">Optionnel. Tu peux laisser vide.</div>

      <label for="synopsis">Synopsis (optionnel)</label>
      <textarea id="synopsis" name="synopsis"><?= htmlspecialchars($synopsis) ?></textarea>

      <button type="submit">Ajouter</button>
    </form>
  </section>
</main>

</body>
</html>
