<?php

namespace App\Repository;

use App\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Property>
 *
 * @method Property|null find($id, $lockMode = null, $lockVersion = null)
 * @method Property|null findOneBy(array $criteria, array $orderBy = null)
 * @method Property[]    findAll()
 * @method Property[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PropertyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Property::class);
    }

    public function searchByCriteria(array $criteria): array
    {
        $qb = $this->createQueryBuilder('p');

        if (isset($criteria['type'])) {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $criteria['type']);
        }

        if (isset($criteria['location'])) {
            $qb->andWhere('p.location = :location')
               ->setParameter('location', $criteria['location']);
        }

        if (isset($criteria['max_price'])) {
            $qb->andWhere('p.price <= :max_price')
               ->setParameter('max_price', $criteria['max_price']);
        }

        return $qb->getQuery()->getResult();
    }

    public function findBySemanticSearch(array $embedding, int $limit = 5): array
    {
        $em = $this->getEntityManager();
        $rsm = new \Doctrine\ORM\Query\ResultSetMappingBuilder($em);
        $rsm->addRootEntityFromClassMetadata(Property::class, 'p');

        $sql = 'SELECT p.* FROM property p ORDER BY p.embeddings <=> ? LIMIT ?';
        $query = $em->createNativeQuery($sql, $rsm);
        $embeddingString = '[' . implode(',', $embedding) . ']';
        
        $query->setParameter(1, $embeddingString);
        $query->setParameter(2, $limit);

        return $query->getResult();
    }
}
