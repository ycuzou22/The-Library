<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Library — Accueil</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            width: 360px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        h1 {
            margin-bottom: 10px;
        }

        p {
            color: #555;
            margin-bottom: 30px;
        }

        a.button {
            display: block;
            text-decoration: none;
            padding: 12px;
            margin: 10px 0;
            border-radius: 6px;
            font-weight: bold;
            color: white;
            background: #007bff;
        }

        a.button.register {
            background: #28a745;
        }

        a.button:hover {
            opacity: 0.9;
        }

        footer {
            margin-top: 20px;
            font-size: 0.85em;
            color: #888;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>📚 Library</h1>
    <p>Bienvenue sur votre bibliothèque en ligne</p>

    <a href="login.php" class="button">Se connecter</a>
    <a href="register.php" class="button register">S’inscrire</a>

    <footer>
        Projet PHP POO — Localhost
    </footer>
</div>

</body>
</html>
