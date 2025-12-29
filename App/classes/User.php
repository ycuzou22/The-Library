<?php
class User {
    public $name;
    public function __construct($name) {
        $this->name = $name;
    }
    public function getName() {
        return $this->name;
    }
    public function setName($name) {
        $this->name = $name;
    }
    public function __toString() {
        return "User: " . $this->name;
    }
    public function borrowBook($book) {
        // Logic to borrow a book
    }
    public function returnBook($book) {
        // Logic to return a book
    }
}
?>