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

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
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
    #[ORM\OneToMany(targetEntity: Media::class, mappedBy: 'products')]
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
    #[ORM\OneToMany(targetEntity: OrdersProducts::class, mappedBy: 'products', cascade: ['persist', 'remove'])]
    private Collection $ordersProducts;

    #[ORM\ManyToOne(targetEntity: PokemonCard::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?PokemonCard $pokemonCard = null;

    public function __construct()
    {
        $this->media = new ArrayCollection();
        $this->likes = new ArrayCollection();
    }

    public function getPokemonCard(): ?PokemonCard
    {
        return $this->pokemonCard;
    }

    public function setPokemonCard(?PokemonCard $pokemonCard): static
    {
        $this->pokemonCard = $pokemonCard;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

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

    public function getUsers(): ?Users
    {
        return $this->users;
    }

    public function setUsers(?Users $users): static
    {
        $this->users = $users;

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
            $like->addLike($this);
        }

        return $this;
    }

    public function removeLike(Users $like): static
    {
        if ($this->likes->removeElement($like)) {
            $like->removeLike($this);
        }

        return $this;
    }

    public function getOrdersProducts(): ?OrdersProducts
    {
        return $this->ordersProducts;
    }

    public function setOrdersProducts(?OrdersProducts $ordersProducts): static
    {
        $this->ordersProducts = $ordersProducts;

        return $this;
    }
}