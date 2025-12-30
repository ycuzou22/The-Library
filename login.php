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
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Library — Connexion</title>
  <link rel="stylesheet" href="assets/app.css">
</head>
<body>

  <div class="auth">
    <div class="head">
      <div class="logo" aria-hidden="true"></div>
      <div>
        <h1>Connexion</h1>
        <p class="sub">Accède à ton dashboard Library</p>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="msg err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="on">
      <label for="username">Nom d’utilisateur</label>
      <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>

      <label for="password">Mot de passe</label>
      <input type="password" id="password" name="password" required>

      <button type="submit">Se connecter</button>
    </form>

    <div class="links">
      <a href="index.php">← Retour à l’accueil</a>
    </div>
  </div>

</body>
</html>
