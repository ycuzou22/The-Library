CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mangas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  alt_title VARCHAR(120) NULL,
  status ENUM('En cours','Terminé') NOT NULL DEFAULT 'En cours',
  synopsis TEXT NULL,
  cover_url VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chapters (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  manga_id INT UNSIGNED NOT NULL,
  number INT UNSIGNED NOT NULL,
  title VARCHAR(120) NULL,
  published_at DATE NULL,
  pdf_url VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_manga_chapter (manga_id, number),
  CONSTRAINT fk_chapters_manga
    FOREIGN KEY (manga_id) REFERENCES mangas(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  chapter_id INT UNSIGNED NOT NULL,
  page_number INT UNSIGNED NOT NULL,
  image_url VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_chapter_page (chapter_id, page_number),
  CONSTRAINT fk_pages_chapter
    FOREIGN KEY (chapter_id) REFERENCES chapters(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------
-- Données de démo
-- ---------
INSERT INTO mangas (title, alt_title, status, synopsis, cover_url)
VALUES
('Demo Manga', 'Démo', 'En cours', 'Manga de démonstration pour tester le reader.', NULL)
ON DUPLICATE KEY UPDATE title=title;

-- Crée le chapitre 1 si pas déjà présent
INSERT IGNORE INTO chapters (manga_id, number, title, published_at)
SELECT m.id, 1, 'Chapitre 1', '2024-01-01'
FROM mangas m
WHERE m.title = 'Demo Manga';

-- Ajoute 8 pages de démo (images placeholder)
INSERT IGNORE INTO pages (chapter_id, page_number, image_url)
SELECT c.id, p.n, CONCAT('https://placehold.co/900x1300?text=Demo+Manga+Ch1+Page+', p.n)
FROM chapters c
JOIN mangas m ON m.id = c.manga_id
JOIN (
  SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
  UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8
) p
WHERE m.title = 'Demo Manga' AND c.number = 1;


-- One Piece (exemple)
INSERT IGNORE INTO mangas (title, alt_title, status, synopsis)
VALUES ('One Piece','Monkey D. Luffy','En cours','Luffy prend la mer pour devenir le Roi des Pirates et trouver le trésor légendaire : le One Piece.');
