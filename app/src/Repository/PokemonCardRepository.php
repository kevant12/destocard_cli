<?php

namespace App\Repository;

use App\Entity\PokemonCard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PokemonCard>
 */
class PokemonCardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PokemonCard::class);
    }

    /**
     * @return string[] Returns an array of unique extension names
     */
    public function findUniqueExtensions(): array
    {
        return $this->createQueryBuilder('pc')
            ->select('DISTINCT pc.extension')
            ->where('pc.extension IS NOT NULL')
            ->orderBy('pc.extension', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function findOneByNumber(string $number): ?PokemonCard
    {
        return $this->createQueryBuilder('pc')
            ->andWhere('pc.number = :number')
            ->setParameter('number', $number)
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return PokemonCard[] Returns an array of PokemonCard objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?PokemonCard
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
