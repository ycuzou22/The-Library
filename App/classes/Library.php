<?php
require_once 'Book.php';
class Library {
    public array $lb = [];
    /** @var Borrow[] */
    private array $borrows = [];
    public function addBook(Book $book): void {
        $this->lb[] = $book;
    }
    public function getBooks(): array {
        return $this->lb;
    }
    public function countBooks(): int {
        return count($this->lb);
    }
    public function removeBook(Book $book): void {
        $index = array_search($book, $this->lb, true);
        if ($index !== false) {
            unset($this->lb[$index]);
            $this->lb = array_values($this->lb);
        }
    }
    public function findBookByTitle(string $title): ?Book {
        foreach ($this->lb as $book) {
            if ($book->title === $title) {
                return $book;
            }
        }
        return null;
    }
    public function listBookTitles(): array {
        $titles = [];
        foreach ($this->lb as $book) {
            $titles[] = $book->title;
        }
        return $titles;
    }
    public function clearLibrary(): void {
        $this->lb = [];
    }
    public function isEmpty(): bool {
        return empty($this->lb);
    }
    public function getTotalPages(): int {
        $total = 0;
        foreach ($this->lb as $book) {
            $total += $book->pages;
        }
        return $total;
    }
    public function getBooksByAuthor(string $author): array {
        $authorBooks = [];
        foreach ($this->lb as $book) {
            if ($book->author === $author) {
                $authorBooks[] = $book;
            }
        }
        return $authorBooks;
    }
    public function sortBooksByTitle(): void {
        usort($this->lb, function($a, $b) {
            return strcmp($a->title, $b->title);
        });
    }
    public function sortBooksByAuthor(): void {
        usort($this->lb, function($a, $b) {
            return strcmp($a->author, $b->author);
        });
    }
    public function sortBooksByPages(): void {
        usort($this->lb, function($a, $b) {
            return $a->pages <=> $b->pages;
        });
    }
    public function getAveragePages(): float {
        if ($this->isEmpty()) {
            return 0;
        }
        return $this->getTotalPages() / $this->countBooks();
    }
    public function getBookTitlesByAuthor(string $author): array {
        $titles = [];
        foreach ($this->lb as $book) {
            if ($book->author === $author) {
                $titles[] = $book->title;
            }
        }
        return $titles;
    }
    public function addBorrow(Borrow $borrow): void
    {
        $this->borrows[] = $borrow;
    }
    public function getBorrows(): array
    {
        return $this->borrows;
    }
    public function getBorrowsByUser(User $user): array
    {
        return array_values(array_filter(
            $this->borrows,
            fn (Borrow $b) => $b->getUser() === $user
        ));
    }
    public function getActiveBorrows(): array
    {
        return array_values(array_filter(
            $this->borrows,
            fn (Borrow $b) => !$b->isReturned()
        ));
    }
    public function returnBook(Book $book, DateTimeImmutable $returnDate): bool
    {
        foreach ($this->borrows as $borrow) {
            if ($borrow->getBook() === $book && !$borrow->isReturned()) {
                $borrow->setReturnDate($returnDate);
                return true;
            }
        }
        return false;
    }
}
?>