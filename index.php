<?php
require_once 'App/classes/Book.php';
require_once 'App/classes/User.php';
require_once 'App/classes/Borrow.php';
require_once 'App/classes/Library.php';

$biblio = new Library();

/** -----------------------------
 *  1) Générer des utilisateurs
 *  ----------------------------*/
$users = [
    new User("Alice"),
    new User("Bob"),
    new User("Chloé"),
    new User("David"),
    new User("Emma"),
    new User("Farid"),
    new User("Inès"),
    new User("Julien"),
];

/** -----------------------------
 *  2) Générer des livres
 *  ----------------------------*/
$books = [
    new Book("1984", "George Orwell", 328),
    new Book("Le Petit Prince", "Antoine de Saint-Exupéry", 96),
    new Book("L'Étranger", "Albert Camus", 184),
    new Book("Harry Potter à l'école des sorciers", "J.K. Rowling", 320),
    new Book("Dune", "Frank Herbert", 688),
    new Book("Les Misérables", "Victor Hugo", 1463),
    new Book("Fondation", "Isaac Asimov", 296),
    new Book("Le Seigneur des Anneaux", "J.R.R. Tolkien", 1216),
    new Book("La Peste", "Albert Camus", 288),
    new Book("Germinal", "Émile Zola", 592),
];

/** -----------------------------
 *  3) Générer des emprunts
 *  ----------------------------*/
$nbBorrows = 20;

for ($i = 0; $i < $nbBorrows; $i++) {
    $user = $users[array_rand($users)];
    $book = $books[array_rand($books)];
    $borrowDate = new DateTimeImmutable('2024-01-01');
    $borrowDate = $borrowDate->modify('+' . random_int(0, 90) . ' days');
    $borrow = new Borrow($user, $book, $borrowDate);
    $biblio->addBorrow($borrow);
    if (random_int(1, 100) <= 60) {
        $returnDate = $borrowDate->modify('+' . random_int(1, 30) . ' days');
        $borrow->setReturnDate($returnDate);
    }
}

/** -----------------------------
 *  4) Afficher tous les emprunts
 *  ----------------------------*/
echo "<h2>Liste des emprunts</h2>";

foreach ($biblio->getBorrows() as $b) {
    echo $b . "\n";
}
$biblio->returnBook($book, new DateTimeImmutable("2024-01-10"));
?>