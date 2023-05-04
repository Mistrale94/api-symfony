<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function save(Category $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Category $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAll(): array
    {
       return $this->createQueryBuilder('c')
           ->select('c.id, c.title') // Choix des colonnes à récupérer
           ->setMaxResults(3)
           ->orderBy('c.id', 'DESC')
           ->getQuery()
           ->getResult()
       ;
    }

//    /**
//     * @return Category[] Returns an array of Category objects
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

   public function findOneById($value)
   {
    $results = $this->createQueryBuilder('c')
        ->select('c.id, c.title, a.title as article')
        ->andWhere('c.id = :val')
        ->leftJoin('c.articles', 'a')
        ->setParameter('val', $value)
        ->getQuery()
        ->getResult()
    ;
       
       if (empty($results)) {
            return null;
        }

        $post = array_reduce($results, function ($carry, $item) {
            if ($carry === null) {
                $carry = [
                    'id' => $item['id'],
                    'title' => $item['title'],
                    'articles' => []
                ];
            }
            if ($item['article'] !== null) {
                $carry['articles'][] = $item['article'];
            }
            return $carry;
        });

        return $post;
   }
}
