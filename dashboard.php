<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$username = (string)($_SESSION['username'] ?? 'Utilisateur');

// Menu
$items = [
    [
        'title' => 'Catalogue',
        'desc'  => 'Tous les livres / mangas',
        'href'  => 'catalog.php',
        'icon'  => 'grid',
    ],
    [
        'title' => 'Upload chapitre',
        'desc'  => 'Ajouter des pages (scan)',
        'href'  => 'upload_chapter.php',
        'icon'  => 'upload',
    ],
    [
        'title' => 'Mes emprunts',
        'desc'  => 'En cours & historique',
        'href'  => 'my_borrows.php',
        'icon'  => 'bookmark',
    ],
    [
        'title' => 'Emprunter',
        'desc'  => 'Nouvel emprunt',
        'href'  => 'borrow.php',
        'icon'  => 'arrow',
    ],
    [
        'title' => 'Retours',
        'desc'  => 'Rendre un livre',
        'href'  => 'returns.php',
        'icon'  => 'refresh',
    ],
    [
        'title' => 'Ajouter un livre',
        'desc'  => 'Admin / gestion',
        'href'  => 'add_book.php',
        'icon'  => 'plus',
    ],
    [
        'title' => 'Utilisateurs',
        'desc'  => 'Liste & gestion',
        'href'  => 'users.php',
        'icon'  => 'users',
    ],
    [
        'title' => 'Paramètres',
        'desc'  => 'Compte & sécurité',
        'href'  => 'settings.php',
        'icon'  => 'gear',
    ],
    [
        'title' => 'Déconnexion',
        'desc'  => 'Quitter la session',
        'href'  => 'logout.php',
        'icon'  => 'logout',
    ],
];

function iconSvg(string $name): string {
    return match ($name) {
        'grid' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>',
        'bookmark' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12a1 1 0 0 1 1 1v18l-7-4-7 4V4a1 1 0 0 1 1-1z"/></svg>',
        'arrow' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l4 4-7 7H2v-2h6.17l5.42-5.41L12 4.83 9.41 7.41 8 6l4-4zm10 9v11a2 2 0 0 1-2 2H5v-2h15V11h2z"/></svg>',
        'refresh' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.65 6.35A7.95 7.95 0 0 0 12 4V1L7 6l5 5V7a6 6 0 1 1-6 6H4a8 8 0 1 0 13.65-6.65z"/></svg>',
        'plus' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 4h2v7h7v2h-7v7h-2v-7H4v-2h7V4z"/></svg>',
        'users' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11c1.66 0 3-1.34 3-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 3-1.34 3-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V20h14v-3.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V20h7v-3.5c0-2.33-4.67-3.5-7-3.5z"/></svg>',
        'gear' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19.14 12.94c.04-.31.06-.63.06-.94s-.02-.63-.06-.94l2.03-1.58a.5.5 0 0 0 .12-.64l-1.92-3.32a.5.5 0 0 0-.6-.22l-2.39.96a7.4 7.4 0 0 0-1.63-.94l-.36-2.54A.5.5 0 0 0 13.9 1h-3.8a.5.5 0 0 0-.49.42l-.36 2.54c-.58.23-1.12.54-1.63.94l-2.39-.96a.5.5 0 0 0-.6.22L2.71 7.48a.5.5 0 0 0 .12.64l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94L2.83 14.52a.5.5 0 0 0-.12.64l1.92 3.32c.13.22.39.3.6.22l2.39-.96c.51.4 1.05.71 1.63.94l.36 2.54c.04.24.25.42.49.42h3.8c.24 0 .45-.18.49-.42l.36-2.54c.58-.23 1.12-.54 1.63-.94l2.39.96c.22.09.47 0 .6-.22l1.92-3.32a.5.5 0 0 0-.12-.64l-2.03-1.58zM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5z"/></svg>',
        'upload' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 20h14v-2H5v2zM12 2l5 5h-3v6h-4V7H7l5-5z"/></svg>',
        'logout' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17v-2h4v-6h-4V7l-5 5 5 5zm9-14H12v2h7v14h-7v2h7a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"/></svg>',
        default => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z"/></svg>',
    };
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Library — Dashboard</title>
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
        }
        *{box-sizing:border-box}
        body{
            margin:0;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, "Noto Sans", "Liberation Sans", sans-serif;
            background:
                radial-gradient(1000px 600px at 20% -10%, var(--glow), transparent 60%),
                radial-gradient(900px 600px at 90% 10%, var(--glow2), transparent 55%),
                linear-gradient(180deg, var(--bg0), var(--bg1));
            color:var(--text);
            min-height:100vh;
        }
        .topbar{
            max-width:1100px;
            margin:0 auto;
            padding:28px 18px 14px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:14px;
        }
        .brand{display:flex;align-items:center;gap:12px;}
        .logo{
            width:42px;height:42px;border-radius:12px;
            background: linear-gradient(135deg, rgba(99,102,241,.9), rgba(236,72,153,.75));
            box-shadow: 0 10px 30px rgba(0,0,0,.35);
        }
        .title h1{font-size:18px;margin:0}
        .title p{margin:3px 0 0;color:var(--muted);font-size:13px}
        .userchip{
            display:flex; align-items:center; gap:10px;
            padding:10px 12px;
            border:1px solid var(--stroke);
            background: rgba(255,255,255,.04);
            border-radius:14px;
            backdrop-filter: blur(10px);
        }
        .avatar{
            width:34px;height:34px;border-radius:12px;
            background: rgba(255,255,255,.08);
            border:1px solid var(--stroke);
            display:grid;place-items:center;
            font-weight:700;
        }
        .userchip .name{font-weight:600}
        .userchip .hint{color:var(--muted);font-size:12px;margin-top:2px}
        .wrap{max-width:1100px;margin:0 auto;padding:10px 18px 36px;}
        .grid{
            display:grid;
            grid-template-columns: repeat(4, minmax(0,1fr));
            gap:14px;
            margin-top:14px;
        }
        @media (max-width: 980px){ .grid{ grid-template-columns: repeat(3, 1fr);} }
        @media (max-width: 720px){ .grid{ grid-template-columns: repeat(2, 1fr);} }
        @media (max-width: 420px){ .grid{ grid-template-columns: 1fr;} }

        a.card{
            text-decoration:none;
            color:inherit;
            border:1px solid var(--stroke);
            background: linear-gradient(180deg, var(--card), rgba(255,255,255,.03));
            border-radius:18px;
            padding:16px;
            position:relative;
            overflow:hidden;
            transition: transform .12s ease, border-color .12s ease, background .12s ease;
            min-height:120px;
        }
        a.card:before{
            content:"";
            position:absolute; inset:-2px;
            background: radial-gradient(500px 180px at 20% 0%, rgba(99,102,241,.25), transparent 60%),
                        radial-gradient(500px 180px at 80% 0%, rgba(236,72,153,.18), transparent 60%);
            opacity:.0;
            transition: opacity .12s ease;
            pointer-events:none;
        }
        a.card:hover{
            transform: translateY(-2px);
            border-color: rgba(255,255,255,.18);
            background: linear-gradient(180deg, var(--card2), rgba(255,255,255,.04));
        }
        a.card:hover:before{ opacity:1; }
        .ic{
            width:44px;height:44px;
            border-radius:14px;
            border:1px solid var(--stroke);
            background: rgba(255,255,255,.05);
            display:grid;place-items:center;
            margin-bottom:12px;
        }
        .ic svg{ width:22px;height:22px; fill: rgba(255,255,255,.92); }
        .card h3{ margin:0; font-size:15px; letter-spacing:.2px; }
        .card p{ margin:6px 0 0; color:var(--muted); font-size:12.5px; line-height:1.35; }
        .footer{
            margin-top:18px;
            color:var(--muted);
            font-size:12px;
            display:flex;
            justify-content:space-between;
            gap:12px;
            flex-wrap:wrap;
        }
        .pill{
            border:1px solid var(--stroke);
            background: rgba(255,255,255,.04);
            padding:6px 10px;
            border-radius:999px;
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="brand">
        <div class="logo" aria-hidden="true"></div>
        <div class="title">
            <h1>Library</h1>
            <p>Menu rapide — style “scan”</p>
        </div>
    </div>

    <div class="userchip">
        <div class="avatar" title="Profil">
            <?= htmlspecialchars(mb_strtoupper(mb_substr($username, 0, 1))) ?>
        </div>
        <div>
            <div class="name"><?= htmlspecialchars($username) ?></div>
            <div class="hint">Connecté</div>
        </div>
    </div>
</header>

<main class="wrap">
    <div class="grid">
        <?php foreach ($items as $it): ?>
            <a class="card" href="<?= htmlspecialchars($it['href']) ?>">
                <div class="ic"><?= iconSvg($it['icon']) ?></div>
                <h3><?= htmlspecialchars($it['title']) ?></h3>
                <p><?= htmlspecialchars($it['desc']) ?></p>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="footer">
        <div class="pill">⬆️ Astuce : utilise “Upload chapitre” pour ajouter tes pages et lire dans reader.php</div>
        <div class="pill">🖼️ Les images seront servies depuis /uploads/…</div>
    </div>
</main>

</body>
</html>
