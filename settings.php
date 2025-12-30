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
$usernameSession = (string)($_SESSION['username'] ?? 'Utilisateur');

function cleanPhone(string $p): string {
    // garde chiffres + + (format simple)
    $p = trim($p);
    $p = preg_replace('/[^\d+]/', '', $p) ?? '';
    return $p;
}
function isValidEmail(string $e): bool {
    return (bool)filter_var($e, FILTER_VALIDATE_EMAIL);
}

$errors = [];
$success = [];

// Récup user actuel
$stmt = $pdo->prepare("SELECT id, username, email, phone, password_hash FROM users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$currentUsername = (string)$user['username'];
$currentEmail = (string)($user['email'] ?? '');
$currentPhone = (string)($user['phone'] ?? '');

// --- UPDATE PROFIL (username/email/phone) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'profile') {
    $newUsername = trim((string)($_POST['username'] ?? ''));
    $newEmail    = trim((string)($_POST['email'] ?? ''));
    $newPhone    = cleanPhone((string)($_POST['phone'] ?? ''));

    if ($newUsername === '') {
        $errors[] = "Le pseudo est obligatoire.";
    } elseif (mb_strlen($newUsername) < 3 || mb_strlen($newUsername) > 30) {
        $errors[] = "Le pseudo doit faire entre 3 et 30 caractères.";
    }

    if ($newEmail !== '' && !isValidEmail($newEmail)) {
        $errors[] = "Email invalide.";
    }

    if ($newPhone !== '' && (mb_strlen($newPhone) < 6 || mb_strlen($newPhone) > 30)) {
        $errors[] = "Numéro de téléphone invalide.";
    }

    if (!$errors) {
        // vérif pseudo unique (si tu veux)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username=? AND id<>? LIMIT 1");
        $stmt->execute([$newUsername, $userId]);
        if ($stmt->fetch()) {
            $errors[] = "Ce pseudo est déjà utilisé.";
        }

        // vérif email unique si unique index (sinon optionnel)
        if (!$errors && $newEmail !== '') {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email=? AND id<>? LIMIT 1");
            $stmt->execute([$newEmail, $userId]);
            if ($stmt->fetch()) {
                $errors[] = "Cet email est déjà utilisé.";
            }
        }

        if (!$errors) {
            $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, phone=? WHERE id=?");
            $stmt->execute([
                $newUsername,
                $newEmail !== '' ? $newEmail : null,
                $newPhone !== '' ? $newPhone : null,
                $userId
            ]);

            // Sync session username
            $_SESSION['username'] = $newUsername;

            $success[] = "Profil mis à jour ✅";
            $currentUsername = $newUsername;
            $currentEmail = $newEmail;
            $currentPhone = $newPhone;
        }
    }
}

// --- UPDATE PASSWORD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'password') {
    $old = (string)($_POST['old_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $new2 = (string)($_POST['new_password_confirm'] ?? '');

    if ($old === '' || $new === '' || $new2 === '') {
        $errors[] = "Tous les champs mot de passe sont obligatoires.";
    } elseif (!password_verify($old, (string)$user['password_hash'])) {
        $errors[] = "Ancien mot de passe incorrect.";
    } elseif ($new !== $new2) {
        $errors[] = "Les nouveaux mots de passe ne correspondent pas.";
    } elseif (mb_strlen($new) < 8) {
        $errors[] = "Le nouveau mot de passe doit faire au moins 8 caractères.";
    }

    if (!$errors) {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?");
        $stmt->execute([$hash, $userId]);
        $success[] = "Mot de passe modifié ✅";
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Paramètres</title>
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
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    @media(max-width:820px){.grid{grid-template-columns:1fr}}
    .card{
      border:1px solid var(--stroke);
      background: linear-gradient(180deg, var(--card), rgba(255,255,255,.03));
      border-radius:18px;
      padding:16px;
    }
    h2{margin:0 0 10px;font-size:16px}
    label{display:block;margin-top:12px;font-weight:800;font-size:13px}
    input{
      width:100%;
      margin-top:6px;
      background: rgba(0,0,0,.18);
      border:1px solid var(--stroke);
      color:var(--text);
      padding:10px 12px;
      border-radius:12px;
      outline:none;
    }
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
    .hint{color:var(--muted);font-size:12.5px;line-height:1.35}
  </style>
</head>
<body>

<header class="topbar">
  <div>
    <div style="font-weight:900;font-size:18px;">Paramètres</div>
    <div style="color:var(--muted);font-size:13px;">Connecté : <?= htmlspecialchars((string)($_SESSION['username'] ?? $usernameSession)) ?></div>
  </div>
  <nav class="nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="catalog.php">Catalogue</a>
    <a href="logout.php">Déconnexion</a>
  </nav>
</header>

<main class="wrap">
  <?php foreach ($success as $s): ?>
    <div class="msg ok"><?= htmlspecialchars($s) ?></div>
  <?php endforeach; ?>
  <?php foreach ($errors as $e): ?>
    <div class="msg bad"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <div class="grid">
    <section class="card">
      <h2>Profil</h2>
      <div class="hint">Modifie ton pseudo, ajoute un email et un téléphone.</div>

      <form method="post">
        <input type="hidden" name="action" value="profile">

        <label>Pseudo</label>
        <input name="username" value="<?= htmlspecialchars($currentUsername) ?>" required>

        <label>Email (optionnel)</label>
        <input name="email" type="email" value="<?= htmlspecialchars($currentEmail) ?>" placeholder="ex: moi@mail.com">

        <label>Téléphone (optionnel)</label>
        <input name="phone" value="<?= htmlspecialchars($currentPhone) ?>" placeholder="+33612345678">

        <button type="submit">Enregistrer</button>
      </form>
    </section>

    <section class="card">
      <h2>Sécurité</h2>
      <div class="hint">Changer ton mot de passe (vérification de l’ancien).</div>

      <form method="post">
        <input type="hidden" name="action" value="password">

        <label>Ancien mot de passe</label>
        <input type="password" name="old_password" required>

        <label>Nouveau mot de passe</label>
        <input type="password" name="new_password" required>

        <label>Confirmer nouveau mot de passe</label>
        <input type="password" name="new_password_confirm" required>

        <button type="submit">Mettre à jour le mot de passe</button>
      </form>
    </section>
  </div>
</main>

</body>
</html>
