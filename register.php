<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/config/db.php';

$errors = [];
$success = false;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['confirm_password'] ?? '');

    if ($username === '' || $password === '' || $confirm === '') {
        $errors[] = "Tous les champs sont obligatoires.";
    }
    if (strlen($username) < 3) {
        $errors[] = "Le nom d’utilisateur doit contenir au moins 3 caractères.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    }
    if ($password !== $confirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (!$errors) {
        try {
            $pdo = db();

            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = "Ce nom d’utilisateur est déjà utilisé.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
                $stmt->execute([$username, $hash]);
                $success = true;
            }
        } catch (Throwable $e) {
            $errors[] = "Erreur serveur : " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Library — Inscription</title>
  <style>
    :root{
      --bg0:#0b0f18;
      --bg1:#0f172a;
      --card: rgba(255,255,255,.06);
      --card2: rgba(255,255,255,.09);
      --text:#e5e7eb;
      --muted:#a7b0c0;
      --stroke: rgba(255,255,255,.10);
      --glow: rgba(99,102,241,.35);
      --glow2: rgba(236,72,153,.22);
      --danger: rgba(239,68,68,.18);
      --dangerStroke: rgba(239,68,68,.35);
      --success: rgba(34,197,94,.18);
      --successStroke: rgba(34,197,94,.35);
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      min-height:100vh;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, "Noto Sans", "Liberation Sans", sans-serif;
      background:
        radial-gradient(1000px 600px at 20% -10%, var(--glow), transparent 60%),
        radial-gradient(900px 600px at 90% 10%, var(--glow2), transparent 55%),
        linear-gradient(180deg, var(--bg0), var(--bg1));
      color:var(--text);
      display:grid;
      place-items:center;
      padding:22px 14px;
    }

    .auth{
      width:100%;
      max-width:440px;
      border:1px solid var(--stroke);
      background: linear-gradient(180deg, var(--card), rgba(255,255,255,.03));
      border-radius:18px;
      padding:18px;
      box-shadow: 0 20px 60px rgba(0,0,0,.35);
      backdrop-filter: blur(10px);
      position:relative;
      overflow:hidden;
    }
    .auth:before{
      content:"";
      position:absolute; inset:-2px;
      background:
        radial-gradient(500px 180px at 20% 0%, rgba(99,102,241,.22), transparent 60%),
        radial-gradient(500px 180px at 80% 0%, rgba(236,72,153,.16), transparent 60%);
      pointer-events:none;
    }

    .head{
      position:relative;
      display:flex;
      align-items:center;
      gap:12px;
      margin-bottom:12px;
    }
    .logo{
      width:42px;height:42px;border-radius:12px;
      background: linear-gradient(135deg, rgba(99,102,241,.9), rgba(236,72,153,.75));
      box-shadow: 0 10px 30px rgba(0,0,0,.35);
      flex:0 0 auto;
    }
    h1{
      margin:0;
      font-size:18px;
      letter-spacing:.2px;
    }
    .sub{
      margin:4px 0 0;
      color:var(--muted);
      font-size:12.5px;
      line-height:1.35;
    }

    form{position:relative;margin-top:12px}
    label{
      display:block;
      margin-top:12px;
      font-weight:800;
      font-size:12.5px;
      letter-spacing:.2px;
    }
    input{
      width:100%;
      margin-top:6px;
      padding:10px 12px;
      border-radius:12px;
      border:1px solid var(--stroke);
      background: rgba(0,0,0,.20);
      color:var(--text);
      outline:none;
    }
    input:focus{
      border-color: rgba(255,255,255,.18);
      box-shadow: 0 0 0 4px rgba(99,102,241,.12);
    }

    button{
      width:100%;
      margin-top:14px;
      padding:12px;
      border-radius:12px;
      border:1px solid rgba(255,255,255,.12);
      background: linear-gradient(135deg, rgba(99,102,241,.85), rgba(236,72,153,.75));
      color:var(--text);
      font-weight:900;
      cursor:pointer;
      transition: transform .12s ease, filter .12s ease;
    }
    button:hover{
      transform: translateY(-1px);
      filter: brightness(1.05);
    }

    .msg{
      position:relative;
      padding:10px 12px;
      border-radius:12px;
      margin-top:10px;
      border:1px solid var(--stroke);
      font-size:13px;
      line-height:1.35;
    }
    .msg.err{
      background: var(--danger);
      border-color: var(--dangerStroke);
      color: rgba(255,255,255,.92);
    }
    .msg.ok{
      background: var(--success);
      border-color: var(--successStroke);
      color: rgba(255,255,255,.92);
      text-align:center;
    }
    .msg.ok a{
      display:inline-block;
      margin-top:6px;
      color: rgba(255,255,255,.95);
      font-weight:900;
      text-decoration:none;
      border-bottom:1px solid rgba(255,255,255,.35);
    }

    .links{
      position:relative;
      margin-top:12px;
      display:flex;
      justify-content:space-between;
      gap:10px;
      flex-wrap:wrap;
    }
    .links a{
      text-decoration:none;
      color:var(--muted);
      font-size:12.5px;
      border:1px solid var(--stroke);
      background: rgba(255,255,255,.04);
      padding:6px 10px;
      border-radius:999px;
      transition: border-color .12s ease, background .12s ease, transform .12s ease;
    }
    .links a:hover{
      transform: translateY(-1px);
      border-color: rgba(255,255,255,.18);
      background: rgba(255,255,255,.06);
      color: rgba(229,231,235,.92);
    }
  </style>
</head>
<body>

  <div class="auth">
    <div class="head">
      <div class="logo" aria-hidden="true"></div>
      <div>
        <h1>Inscription</h1>
        <p class="sub">Crée ton compte Library</p>
      </div>
    </div>

    <?php foreach ($errors as $error): ?>
      <div class="msg err"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>

    <?php if ($success): ?>
      <div class="msg ok">
        Inscription réussie 🎉<br>
        <a href="login.php">Se connecter</a>
      </div>
    <?php else: ?>
      <form method="post" autocomplete="on">
        <label for="username">Nom d’utilisateur</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>

        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" required>

        <label for="confirm_password">Confirmer le mot de passe</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <button type="submit">Créer le compte</button>
      </form>
    <?php endif; ?>

    <div class="links">
      <a href="index.php">← Retour à l’accueil</a>
    </div>
  </div>

</body>
</html>
