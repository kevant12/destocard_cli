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

    public function searchProductsQuery(?string $query, ?string $category = null, ?string $rarity = null, ?string $sortBy = null, ?string $sortOrder = 'asc'): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('pc')
            ->leftJoin('p.pokemonCard', 'pc')
            ->addSelect('m')
            ->leftJoin('p.media', 'm');

        if ($query) {
            // Utilisation de MATCH AGAINST pour la recherche Full-Text
            // Les colonnes 'p.title', 'p.description', 'p.category', 'p.extension', 'p.rarity', 'p.type'
            // et 'pc.name', 'pc.number' doivent avoir un index FULLTEXT dans la base de données.
            $qb->andWhere('MATCH_AGAINST(p.title, p.description, p.category, p.extension, p.rarity, p.type, pc.name, pc.number) AGAINST (:query BOOLEAN)')
                ->setParameter('query', $query);
        }

        if ($category) {
            $qb->andWhere('p.category = :category')
                ->setParameter('category', $category);
        }

        if ($rarity) {
            $qb->andWhere('p.rarity = :rarity')
                ->setParameter('rarity', $rarity);
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
            ->addSelect('pc', 'm')
            ->leftJoin('p.pokemonCard', 'pc')
            ->leftJoin('p.media', 'm')
            ->andWhere('p.quantity > 0')
            ->orderBy('p.createdAt', 'DESC');
    }

    public function findUserProductsQuery(int $userId): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->addSelect('pc', 'm')
            ->leftJoin('p.pokemonCard', 'pc')
            ->leftJoin('p.media', 'm')
            ->andWhere('p.users = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.createdAt', 'DESC');
    }
}