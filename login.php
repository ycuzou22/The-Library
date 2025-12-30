<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/config/db.php';

$error = null;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = "Veuillez remplir tous les champs.";
    } else {
        try {
            $pdo = db();
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $error = "Identifiants incorrects.";
            } else {
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = (string)$user['username'];
                header('Location: dashboard.php');
                exit;
            }
        } catch (Throwable $e) {
            $error = "Erreur serveur : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Library — Connexion</title>
  <style>
    body{font-family:Arial;background:#f4f6f8;height:100vh;margin:0;display:flex;align-items:center;justify-content:center}
    .container{background:#fff;padding:40px;border-radius:10px;width:360px;box-shadow:0 10px 25px rgba(0,0,0,.1)}
    h1{text-align:center;margin-bottom:20px}
    label{display:block;margin-top:15px;font-weight:bold}
    input{width:100%;padding:10px;margin-top:5px;border-radius:5px;border:1px solid #ccc}
    button{width:100%;margin-top:25px;padding:12px;border:0;border-radius:6px;background:#007bff;color:#fff;font-weight:bold;cursor:pointer}
    button:hover{opacity:.9}
    .error{background:#ffe0e0;color:#a10000;padding:10px;border-radius:5px;margin-bottom:15px;text-align:center}
    .back{margin-top:20px;text-align:center}
    .back a{text-decoration:none;color:#555;font-size:.9em}
  </style>
</head>
<body>
<div class="container">
  <h1>🔐 Connexion</h1>

  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post">
    <label for="username">Nom d’utilisateur</label>
    <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>

    <label for="password">Mot de passe</label>
    <input type="password" id="password" name="password" required>

    <button type="submit">Se connecter</button>
  </form>

  <div class="back">
    <a href="index.php">← Retour à l’accueil</a>
  </div>
</div>
</body>
</html>
