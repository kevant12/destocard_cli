<?php

namespace App\Entity;

use App\Repository\AddressesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AddressesRepository::class)]
class Addresses
{
    public const TYPE_HOME = 'home';
    public const TYPE_BILLING = 'billing';
    public const TYPE_SHIPPING = 'shipping';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: "Le numéro de rue ne peut pas être vide.")]
    #[Assert\Length(max: 10, maxMessage: "Le numéro de rue ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $number = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La rue ne peut pas être vide.")]
    #[Assert\Length(min: 3, max: 255, minMessage: "La rue doit contenir au moins {{ limit }} caractères.", maxMessage: "La rue ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $street = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "La ville ne peut pas être vide.")]
    #[Assert\Length(min: 2, max: 100, minMessage: "La ville doit contenir au moins {{ limit }} caractères.", maxMessage: "La ville ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $city = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: "Le code postal ne peut pas être vide.")]
    #[Assert\Length(min: 5, max: 10, minMessage: "Le code postal doit contenir au moins {{ limit }} caractères.", maxMessage: "Le code postal ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $zipCode = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le pays ne peut pas être vide.")]
    #[Assert\Length(min: 2, max: 100, minMessage: "Le pays doit contenir au moins {{ limit }} caractères.", maxMessage: "Le pays ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $country = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Choice(choices: [self::TYPE_HOME, self::TYPE_BILLING, self::TYPE_SHIPPING], message: "Le type d'adresse n'est pas valide.")]
    private ?string $type = null;

    #[ORM\ManyToOne(inversedBy: 'addresses')]
    private ?Users $users = null;

    /**
     * @var Collection<int, Orders>
     */
    #[ORM\OneToMany(targetEntity: Orders::class, mappedBy: 'addresses')]
    private Collection $orders;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    // =========================================================================
    // SECTION: Getters et Setters
    // =========================================================================

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getUsers(): ?Users
    {
        return $this->users;
    }

    public function setUsers(?Users $users): static
    {
        $this->users = $users;

        return $this;
    }

    // =========================================================================
    // SECTION: Gestion des relations (Collections)
    // =========================================================================

    /**
     * @return Collection<int, Orders>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Orders $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setAddresses($this);
        }

        return $this;
    }

    public function removeOrder(Orders $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getAddresses() === $this) {
                $order->setAddresses(null);
            }
        }

        return $this;
    }
}
