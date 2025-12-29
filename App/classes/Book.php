<?php
class Book {
    public $title;
    public $author;
    public $pages;  
    public function __construct($title, $author, $pages) {
        $this->title = $title;
        $this->author = $author;
        $this->pages = $pages;
    }
    public function getTitle() {
        return $this->title;
    }
    public function getAuthor() {
        return $this->author;
    }
    public function getPages() {
        return $this->pages;    
    }
    public function __toString() {
        return "Title: " . $this->title . ", Author: " . $this->author . ", Pages: " . $this->pages;
    }
    public function isLongBook(): bool {
        return $this->pages > 300;
    }
    public function isWrittenBy(string $author): bool {
        return $this->author === $author;
    }
    public function hasMorePagesThan(Book $otherBook): bool {
        return $this->pages > $otherBook->pages;
    }
    public function getBookInfo(): string {
        return $this->__toString();
    }
    public function equals(Book $otherBook): bool {
        return $this->title === $otherBook->title &&
               $this->author === $otherBook->author &&
               $this->pages === $otherBook->pages;
    }
}

?>