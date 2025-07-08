<?php

namespace App\Repository;

use App\Entity\Products;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Products>
 */
class ProductsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Products::class);
    }

    public function searchProductsQuery(?string $query, ?string $category = null, ?string $rarity = null, ?string $seller = null, ?string $extension = null, ?string $serie = null, ?string $sortBy = null, ?string $sortOrder = 'asc'): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('m')
            ->leftJoin('p.media', 'm')
            ->addSelect('u')
            ->leftJoin('p.users', 'u');

        if ($query) {
            // 🔍 RECHERCHE SIMPLE ET EFFICACE (comme Google !)
            // On cherche le mot dans le titre OU la description
            // C'est comme chercher dans un livre : on regarde partout !
            $qb->andWhere('p.title LIKE :query OR p.description LIKE :query OR p.category LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        if ($category) {
            $qb->andWhere('p.category = :category')
                ->setParameter('category', $category);
        }

        if ($rarity) {
            $qb->andWhere('p.rarity = :rarity')
                ->setParameter('rarity', $rarity);
        }

        if ($seller) {
            // 👤 FILTRE PAR VENDEUR - Recherche par ID du vendeur
            $qb->andWhere('p.users = :seller')
                ->setParameter('seller', $seller);
        }

        if ($extension) {
            // 📦 FILTRE PAR EXTENSION - Recherche par extension spécifique
            $qb->andWhere('p.extension = :extension')
                ->setParameter('extension', $extension);
        }

        if ($serie) {
            // 📚 FILTRE PAR SÉRIE - Recherche par série spécifique
            $qb->andWhere('p.serie = :serie')
                ->setParameter('serie', $serie);
        }

        // Tri
        if ($sortBy) {
            switch ($sortBy) {
                case 'price':
                    $qb->orderBy('p.price', $sortOrder);
                    break;
                case 'name':
                    $qb->orderBy('p.title', $sortOrder);
                    break;
                case 'date':
                default:
                    $qb->orderBy('p.createdAt', $sortOrder);
                    break;
            }
        } else {
            $qb->orderBy('p.createdAt', 'DESC');
        }

        return $qb;
    }

    public function findAllAvailableProductsQuery(): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->addSelect('m')
            ->leftJoin('p.media', 'm')
            ->andWhere('p.quantity > 0')
            ->orderBy('p.createdAt', 'DESC');
    }

    public function findUserProductsQuery(int $userId): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->addSelect('m')
            ->leftJoin('p.media', 'm')
            ->andWhere('p.users = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.createdAt', 'DESC');
    }

    /**
     * Récupère tous les vendeurs qui ont au moins un produit en vente
     * Utilisé pour peupler le filtre de recherche par vendeur
     * 
     * @return array Liste des vendeurs (Users) avec leurs informations
     */
    public function findAllSellers(): array
    {
        return $this->createQueryBuilder('p')
            ->select('DISTINCT u.id, u.firstname, u.lastname')
            ->join('p.users', 'u')
            ->where('p.quantity > 0') // Seulement les produits disponibles
            ->orderBy('u.firstname', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère toutes les extensions disponibles
     * Utilisé pour peupler le filtre de recherche par extension
     * 
     * @return array Liste des extensions uniques
     */
    public function findAllExtensions(): array
    {
        return $this->createQueryBuilder('p')
            ->select('DISTINCT p.extension')
            ->where('p.extension IS NOT NULL')
            ->andWhere('p.extension != \'\'')
            ->andWhere('p.quantity > 0') // Seulement les produits disponibles
            ->orderBy('p.extension', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère toutes les séries disponibles
     * Utilisé pour peupler le filtre de recherche par série
     * 
     * @return array Liste des séries uniques
     */
    public function findAllSeries(): array
    {
        return $this->createQueryBuilder('p')
            ->select('DISTINCT p.serie')
            ->where('p.serie IS NOT NULL')
            ->andWhere('p.serie != \'\'')
            ->andWhere('p.quantity > 0') // Seulement les produits disponibles
            ->orderBy('p.serie', 'ASC')
            ->getQuery()
            ->getResult();
    }
}