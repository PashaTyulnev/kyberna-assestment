<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ein Buch im Bestand der Bibliothek.
 */
#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ApiResource(operations: [
    new GetCollection(
        parameters: [
            'author' => new QueryParameter(filter: new PartialSearchFilter()),
        ],
    ),
])]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $author = null;

    #[ORM\Column(length: 255)]
    private ?string $isbn = null;

    #[ORM\Column]
    private ?int $availableCopies = null;

    /**
     * @var Collection<int, Loan>
     */
    #[ORM\OneToMany(targetEntity: Loan::class, mappedBy: 'book')]
    private Collection $loans;

    /**
     * Initialisiert die Collection der Ausleihen.
     */
    public function __construct()
    {
        $this->loans = new ArrayCollection();
    }

    /**
     * Gibt die ID zurück.
     *
     * @return ?int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gibt den Titel zurück.
     *
     * @return ?string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Setzt den Titel.
     *
     * @param string $title
     *
     * @return static
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gibt den Autor zurück.
     *
     * @return ?string
     */
    public function getAuthor(): ?string
    {
        return $this->author;
    }

    /**
     * Setzt den Autor.
     *
     * @param string $author
     *
     * @return static
     */
    public function setAuthor(string $author): static
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Gibt die ISBN zurück.
     *
     * @return ?string
     */
    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    /**
     * Setzt die ISBN.
     *
     * @param string $isbn
     *
     * @return static
     */
    public function setIsbn(string $isbn): static
    {
        $this->isbn = $isbn;

        return $this;
    }

    /**
     * Gibt die Anzahl verfügbarer Kopien zurück.
     *
     * @return ?int
     */
    public function getAvailableCopies(): ?int
    {
        return $this->availableCopies;
    }

    /**
     * Setzt die Anzahl verfügbarer Kopien.
     *
     * @param int $availableCopies
     *
     * @return static
     */
    public function setAvailableCopies(int $availableCopies): static
    {
        $this->availableCopies = $availableCopies;

        return $this;
    }

    /**
     * Gibt die Ausleihen zurück.
     *
     * @return Collection<int, Loan>
     */
    public function getLoans(): Collection
    {
        return $this->loans;
    }

    /**
     * Fügt eine Ausleihe hinzu und setzt die Gegenseite der Beziehung.
     *
     * @param Loan $loan
     *
     * @return static
     */
    public function addLoan(Loan $loan): static
    {
        if (!$this->loans->contains($loan)) {
            $this->loans->add($loan);
            $loan->setBook($this);
        }

        return $this;
    }

    /**
     * Entfernt eine Ausleihe und löst die Gegenseite der Beziehung.
     *
     * @param Loan $loan
     *
     * @return static
     */
    public function removeLoan(Loan $loan): static
    {
        if ($this->loans->removeElement($loan)) {
            // set the owning side to null (unless already changed)
            if ($loan->getBook() === $this) {
                $loan->setBook(null);
            }
        }

        return $this;
    }
}
