<?php

class Borrow
{
    private User $user;
    private Book $book;
    private DateTimeImmutable $borrowDate;
    private ?DateTimeImmutable $returnDate;

    public function __construct(
        User $user,
        Book $book,
        DateTimeImmutable $borrowDate,
        ?DateTimeImmutable $returnDate = null
    ) {
        $this->user = $user;
        $this->book = $book;
        $this->borrowDate = $borrowDate;
        $this->returnDate = $returnDate;
    }

    public function getUser(): User { return $this->user; }
    public function getBook(): Book { return $this->book; }
    public function getBorrowDate(): DateTimeImmutable { return $this->borrowDate; }
    public function getReturnDate(): ?DateTimeImmutable { return $this->returnDate; }

    public function setReturnDate(DateTimeImmutable $returnDate): void
    {
        $this->returnDate = $returnDate;
    }

    public function isReturned(): bool
    {
        return $this->returnDate !== null;
    }

    public function __toString(): string
    {
        return "User: {$this->user->getName()}, Book: {$this->book->getTitle()}, Borrow Date: {$this->borrowDate->format('Y-m-d')}, Return Date: " .
            ($this->returnDate ? $this->returnDate->format('Y-m-d') : 'Not returned yet');
    }
}
