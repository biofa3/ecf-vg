<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    /**
     * Retourne les commandes en attente de retour matériel depuis plus de $jours jours.
     *
     * @return Commande[]
     */
    public function findRetourMaterielEnRetard(int $jours = 10): array
    {
        $limite = new \DateTime("-{$jours} days");

        return $this->createQueryBuilder('c')
            ->join('c.historiques', 'h')
            ->where('c.statut = :statut')
            ->andWhere('c.restitution_materiel = false')
            ->andWhere('h.statut = :statut')
            ->andWhere('h.date_changement <= :limite')
            ->setParameter('statut', 'en attente du retour de matériel')
            ->setParameter('limite', $limite)
            ->orderBy('h.date_changement', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Commande[] Returns an array of Commande objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Commande
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
