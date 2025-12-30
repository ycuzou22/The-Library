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

            // Vérifier si username existe déjà
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
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Library — Inscription</title>
  <style>
    body{font-family:Arial;background:#f4f6f8;height:100vh;margin:0;display:flex;align-items:center;justify-content:center}
    .container{background:#fff;padding:40px;border-radius:10px;width:380px;box-shadow:0 10px 25px rgba(0,0,0,.1)}
    h1{text-align:center;margin-bottom:20px}
    label{display:block;margin-top:15px;font-weight:bold}
    input{width:100%;padding:10px;margin-top:5px;border-radius:5px;border:1px solid #ccc}
    button{width:100%;margin-top:25px;padding:12px;border:0;border-radius:6px;background:#28a745;color:#fff;font-weight:bold;cursor:pointer}
    button:hover{opacity:.9}
    .error{background:#ffe0e0;color:#a10000;padding:10px;border-radius:5px;margin-bottom:10px}
    .success{background:#e0ffe7;color:#1b7f3b;padding:10px;border-radius:5px;margin-bottom:15px;text-align:center}
    .back{margin-top:20px;text-align:center}
    .back a{text-decoration:none;color:#555;font-size:.9em}
  </style>
</head>
<body>
<div class="container">
  <h1>📝 Inscription</h1>

  <?php foreach ($errors as $error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endforeach; ?>

  <?php if ($success): ?>
    <div class="success">
      Inscription réussie 🎉<br>
      <a href="login.php">Se connecter</a>
    </div>
  <?php else: ?>
    <form method="post">
      <label for="username">Nom d’utilisateur</label>
      <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>

      <label for="password">Mot de passe</label>
      <input type="password" id="password" name="password" required>

      <label for="confirm_password">Confirmer le mot de passe</label>
      <input type="password" id="confirm_password" name="confirm_password" required>

      <button type="submit">S’inscrire</button>
    </form>
  <?php endif; ?>

  <div class="back">
    <a href="index.php">← Retour à l’accueil</a>
  </div>
</div>
</body>
</html>
