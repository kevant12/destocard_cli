<?php

namespace App\Entity;

use App\Repository\ProductsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductsRepository::class)]
class Products
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $category = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column(options: ["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 100)]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $price = null;

    /**
     * @var Collection<int, Media>
     */
    #[ORM\OneToMany(targetEntity: Media::class, mappedBy: 'products', cascade: ['persist', 'remove'])]
    private Collection $media;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Users $users = null;

    /**
     * @var Collection<int, Users>
     */
    #[ORM\ManyToMany(targetEntity: Users::class, mappedBy: 'likes')]
    private Collection $likes;

    /**
     * @var Collection<int, OrdersProducts>
     */
    #[ORM\OneToMany(targetEntity: OrdersProducts::class, mappedBy: 'products')]
    private Collection $ordersProducts;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $number = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $extension = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $serie = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $rarity = null;

    public function __construct()
    {
        $this->media = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->ordersProducts = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    // =========================================================================
    // SECTION: Getters et Setters
    // =========================================================================

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

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

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): static
    {
        $this->number = $number;
        return $this;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension): static
    {
        $this->extension = $extension;
        return $this;
    }

    public function getSerie(): ?string
    {
        return $this->serie;
    }

    public function setSerie(?string $serie): static
    {
        $this->serie = $serie;
        return $this;
    }

    public function getRarity(): ?string
    {
        return $this->rarity;
    }

    public function setRarity(?string $rarity): static
    {
        $this->rarity = $rarity;
        return $this;
    }

    // =========================================================================
    // SECTION: Gestion des relations (Collections)
    // =========================================================================

    /**
     * @return Collection<int, Media>
     */
    public function getMedia(): Collection
    {
        return $this->media;
    }

    public function addMedium(Media $medium): static
    {
        if (!$this->media->contains($medium)) {
            $this->media->add($medium);
            $medium->setProducts($this);
        }

        return $this;
    }

    public function removeMedium(Media $medium): static
    {
        if ($this->media->removeElement($medium)) {
            // set the owning side to null (unless already changed)
            if ($medium->getProducts() === $this) {
                $medium->setProducts(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Users>
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(Users $like): static
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            // 🔄 SYNCHRONISATION BIDIRECTIONNELLE (sans récursion)
            // On ajoute seulement du côté "owning" de la relation
            if (!$like->getLikes()->contains($this)) {
                $like->getLikes()->add($this);
            }
        }

        return $this;
    }

    public function removeLike(Users $like): static
    {
        if ($this->likes->removeElement($like)) {
            // 🔄 SYNCHRONISATION BIDIRECTIONNELLE (sans récursion)
            // On retire seulement du côté "owning" de la relation
            $like->getLikes()->removeElement($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, OrdersProducts>
     */
    public function getOrdersProducts(): Collection
    {
        return $this->ordersProducts;
    }

    public function addOrdersProduct(OrdersProducts $ordersProduct): static
    {
        if (!$this->ordersProducts->contains($ordersProduct)) {
            $this->ordersProducts->add($ordersProduct);
            $ordersProduct->setProducts($this);
        }

        return $this;
    }

    public function removeOrdersProduct(OrdersProducts $ordersProduct): static
    {
        if ($this->ordersProducts->removeElement($ordersProduct)) {
            // set the owning side to null (unless already changed)
            if ($ordersProduct->getProducts() === $this) {
                $ordersProduct->setProducts(null);
            }
        }

        return $this;
    }
}