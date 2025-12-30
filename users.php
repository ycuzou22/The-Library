<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/db.php';
$pdo = db();

$viewerId = (int)$_SESSION['user_id'];
$profileId = (int)($_GET['id'] ?? $viewerId); // si pas d'id => mon profil
if ($profileId <= 0) $profileId = $viewerId;

$stmt = $pdo->prepare("SELECT id, username, email, phone, avatar_url, banner_url, bio FROM users WHERE id=?");
$stmt->execute([$profileId]);
$u = $stmt->fetch();
if (!$u) {
    http_response_code(404);
    exit("Utilisateur introuvable.");
}
// $hist = $pdo->prepare("
//   SELECT id, type, url, created_at
//   FROM user_media_history
//   WHERE user_id=?
//   ORDER BY created_at DESC
//   LIMIT 30
// ");
// $hist->execute([$viewerId]);
// $mediaHistory = $hist->fetchAll();

$isOwner = ($profileId === $viewerId);

$mediaHistory5 = [];
if ($isOwner) {
    $stmt = $pdo->prepare("
      SELECT id, type, url, created_at
      FROM user_media_history
      WHERE user_id=?
      ORDER BY created_at DESC
      LIMIT 5
    ");
    $stmt->execute([$viewerId]);
    $mediaHistory5 = $stmt->fetchAll();
}


$stmt = $pdo->prepare("
    SELECT id, content, image_url, video_url, audio_url, created_at
    FROM posts
    WHERE user_id=?
    ORDER BY created_at DESC
    LIMIT 50
");
$stmt->execute([$profileId]);
$posts = $stmt->fetchAll();

$username = (string)($_SESSION['username'] ?? 'Utilisateur');

$avatar = trim((string)($u['avatar_url'] ?? ''));
$banner = trim((string)($u['banner_url'] ?? ''));
$bio = (string)($u['bio'] ?? '');
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Profil — <?= htmlspecialchars((string)$u['username']) ?></title>
  <style>
    :root{
      --bg0:#0b0f18; --bg1:#0f172a;
      --card: rgba(255,255,255,.06); --card2: rgba(255,255,255,.09);
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

    .profile{
      border:1px solid var(--stroke);
      background: linear-gradient(180deg, var(--card), rgba(255,255,255,.03));
      border-radius:18px;
      overflow:hidden;
    }
    .banner{
      height:220px;
      position:relative;
      background:
        radial-gradient(700px 240px at 30% 10%, rgba(99,102,241,.25), transparent 60%),
        radial-gradient(700px 240px at 70% 10%, rgba(236,72,153,.18), transparent 60%),
        linear-gradient(135deg, rgba(255,255,255,.08), rgba(255,255,255,.02));
    }
    .banner img{
      position:absolute; inset:0; width:100%; height:100%;
      object-fit:cover;
      filter: saturate(1.05) contrast(1.02);
      transform: scale(1.02);
    }
    .banner:after{
      content:""; position:absolute; inset:0;
      background: linear-gradient(180deg, rgba(0,0,0,.10), rgba(0,0,0,.58));
    }

    .head{
      position:relative;
      padding:0 16px 16px;
    }
    .avatar{
      width:112px; height:112px; border-radius:22px;
      border:1px solid var(--stroke);
      background: rgba(255,255,255,.08);
      overflow:hidden;
      display:grid; place-items:center;
      margin-top:-56px;
      position:relative;
    }
    .avatar img{width:100%;height:100%;object-fit:cover}
    .initial{
      font-weight:900; font-size:42px; color:rgba(255,255,255,.9);
    }
    .nameRow{
      margin-top:12px;
      display:flex; align-items:flex-end; justify-content:space-between; gap:12px; flex-wrap:wrap;
    }
    .name h1{margin:0;font-size:22px}
    .name p{margin:5px 0 0;color:var(--muted);font-size:13px}
    .bio{
      margin-top:12px;
      color:var(--muted);
      font-size:13.5px;
      line-height:1.45;
      white-space:pre-wrap;
    }

    .grid{
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap:14px;
      margin-top:14px;
    }
    @media(max-width:900px){.grid{grid-template-columns:1fr}}

    .card{
      border:1px solid var(--stroke);
      background: rgba(255,255,255,.04);
      border-radius:18px;
      padding:14px;
    }
    .card h2{margin:0 0 10px;font-size:15px;color:var(--text)}
    label{display:block;margin-top:10px;font-weight:800;font-size:13px}
    input, textarea{
      width:100%;
      margin-top:6px;
      background: rgba(0,0,0,.18);
      border:1px solid var(--stroke);
      color:var(--text);
      padding:10px 12px;
      border-radius:12px;
      outline:none;
    }
    textarea{min-height:90px;resize:vertical}
    input[type="file"]{padding:10px}
    .hint{color:var(--muted);font-size:12.5px;line-height:1.35;margin-top:6px}
    button{
      margin-top:12px;
      width:100%;
      padding:12px 14px;
      border-radius:12px;
      border:1px solid var(--stroke);
      background: rgba(255,255,255,.08);
      color:var(--text);
      cursor:pointer;
      font-weight:900;
    }

    .post{
      border:1px solid var(--stroke);
      background: rgba(255,255,255,.04);
      border-radius:18px;
      overflow:hidden;
      margin-top:12px;
    }
    .postBody{padding:12px 14px}
    .postMeta{color:var(--muted);font-size:12px;margin-bottom:8px}
    .postText{white-space:pre-wrap;line-height:1.45}
    .media{margin-top:10px;display:flex;flex-direction:column;gap:10px}
    .media img, .media video{width:100%;border-radius:14px;border:1px solid var(--stroke);background:#111}
    audio{width:100%}
    .sectionTitle{margin:14px 0 6px;color:var(--muted);font-size:13px}
  </style>
</head>
<body>

<header class="topbar">
  <div class="nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="catalog.php">Catalogue</a>
    <a href="users.php">Mon profil</a>
  </div>
  <div class="nav">
    <a href="settings.php">Paramètres</a>
    <a href="logout.php">Déconnexion (<?= htmlspecialchars($username) ?>)</a>
  </div>
</header>

<main class="wrap">
  <section class="profile">
    <div class="banner" aria-hidden="true">
      <?php if ($banner !== ''): ?>
        <img src="<?= htmlspecialchars($banner) ?>" alt="">
      <?php endif; ?>
    </div>

    <div class="head">
      <div class="avatar" title="Photo de profil">
        <?php if ($avatar !== ''): ?>
          <img src="<?= htmlspecialchars($avatar) ?>" alt="">
        <?php else: ?>
          <div class="initial"><?= htmlspecialchars(mb_strtoupper(mb_substr((string)$u['username'], 0, 1))) ?></div>
        <?php endif; ?>
      </div>

      <div class="nameRow">
        <div class="name">
          <h1><?= htmlspecialchars((string)$u['username']) ?></h1>
          <p>ID <?= (int)$u['id'] ?><?= $isOwner ? " • C’est toi" : "" ?></p>
        </div>
        <?php if ($isOwner): ?>
          <div class="nav">
            <a href="#edit">Éditer</a>
            <a href="#post">Poster</a>
          </div>
        <?php endif; ?>
      </div>

      <?php if (trim($bio) !== ''): ?>
        <div class="bio"><?= htmlspecialchars($bio) ?></div>
      <?php else: ?>
        <div class="bio" style="opacity:.8">Aucune bio.</div>
      <?php endif; ?>
    </div>
  </section>

  <div class="grid">
    <?php if ($isOwner): ?>
      <section class="card" id="edit">
        <h2>Personnaliser le profil</h2>
        <div class="hint">Bannière + avatar + bio (fichiers : jpg/png/webp). Taille conseillée : banner 1600×400.</div>

        <form action="profile_update.php" method="post" enctype="multipart/form-data">
          <label>Bannière</label>
          <input type="file" name="banner" accept="image/jpeg,image/png,image/webp">

          <label>Photo de profil</label>
          <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp">

          <label>Bio</label>
          <textarea name="bio" placeholder="Ta description..."><?= htmlspecialchars($bio) ?></textarea>

          <button type="submit">Enregistrer</button>
        </form>
        <?php if ($isOwner): ?>
          <div style="margin-top:14px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
            <div style="color:var(--muted);font-size:13px;">Historique (5 derniers)</div>
            <a href="history.php" style="text-decoration:none;color:var(--text);border:1px solid var(--stroke);background:rgba(255,255,255,.04);padding:8px 12px;border-radius:999px;font-size:13px;">
              Voir tout →
            </a>
          </div>
          <?php if (!$mediaHistory5): ?>
            <div class="hint" style="margin-top:8px;">Aucun historique pour le moment.</div>
          <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-top:10px;">
              <?php foreach ($mediaHistory5 as $h): ?>
                <div style="border:1px solid var(--stroke);background:rgba(0,0,0,.15);border-radius:14px;overflow:hidden;">
                  <div style="padding:8px 10px;font-weight:800;font-size:12px;color:var(--muted);">
                    <?= htmlspecialchars((string)$h['type']) ?>
                  </div>
                  <div style="aspect-ratio:16/9;background:#111;">
                    <img src="<?= htmlspecialchars((string)$h['url']) ?>" alt=""
                        style="width:100%;height:100%;object-fit:cover;display:block;">
                  </div>
                  <form method="post" action="media_set.php" style="margin:0;padding:10px;">
                    <input type="hidden" name="hist_id" value="<?= (int)$h['id'] ?>">
                    <button type="submit" style="width:100%;padding:10px;border-radius:12px;border:1px solid var(--stroke);background:rgba(255,255,255,.08);color:var(--text);font-weight:900;cursor:pointer;">
                      Remettre
                    </button>
                  </form>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </section>

      <section class="card" id="post">
        <h2>Créer un post</h2>
        <div class="hint">Tu peux mettre du texte + 1 image + 1 vidéo + 1 musique (tout optionnel).</div>

        <form action="post_create.php" method="post" enctype="multipart/form-data">
          <label>Texte</label>
          <textarea name="content" placeholder="Écris quelque chose..."></textarea>

          <label>Image (optionnel)</label>
          <input type="file" name="image" accept="image/jpeg,image/png,image/webp">

          <label>Vidéo (optionnel)</label>
          <input type="file" name="video" accept="video/mp4,video/webm,video/ogg">

          <label>Musique (optionnel)</label>
          <input type="file" name="audio" accept="audio/mpeg,audio/mp3,audio/ogg,audio/wav">

          <button type="submit">Publier</button>
        </form>
      </section>
    <?php else: ?>
      <section class="card">
        <h2>Profil</h2>
        <div class="hint">Tu consultes le profil de <?= htmlspecialchars((string)$u['username']) ?>.</div>
      </section>
      <section class="card">
        <h2>Posts</h2>
        <div class="hint">Les posts apparaissent ci-dessous.</div>
      </section>
    <?php endif; ?>
  </div>

  <div class="sectionTitle">Posts</div>

  <?php if (!$posts): ?>
    <div class="card">Aucun post pour le moment.</div>
  <?php else: ?>
    <?php foreach ($posts as $p): ?>
      <article class="post">
        <div class="postBody">
          <div class="postMeta">Publié le <?= htmlspecialchars((string)$p['created_at']) ?></div>

          <?php if (trim((string)$p['content']) !== ''): ?>
            <div class="postText"><?= htmlspecialchars((string)$p['content']) ?></div>
          <?php endif; ?>

          <div class="media">
            <?php if (!empty($p['image_url'])): ?>
              <img src="<?= htmlspecialchars((string)$p['image_url']) ?>" alt="">
            <?php endif; ?>

            <?php if (!empty($p['video_url'])): ?>
              <video src="<?= htmlspecialchars((string)$p['video_url']) ?>" controls playsinline></video>
            <?php endif; ?>

            <?php if (!empty($p['audio_url'])): ?>
              <audio src="<?= htmlspecialchars((string)$p['audio_url']) ?>" controls></audio>
            <?php endif; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</main>

</body>
</html>
