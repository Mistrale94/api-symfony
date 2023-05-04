<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 *
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function save(Article $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Article $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.id, p.title')
            ->leftJoin('p.category', 'c') // Permet de faire la jointure
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOneById($value)
    {
        $results = $this->createQueryBuilder('p')
            ->select('p.id, p.title, p.content, p.active_date, c.title AS category_title, a.email, co.comment')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.author', 'a')
            ->leftJoin('p.comments', 'co')
            ->andWhere('p.id = :val')
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
                    'content' => $item['content'],
                    'active_date' => $item['active_date'],
                    'category_title' => $item['category_title'],
                    'email' => $item['email'],
                    'comments' => []
                ];
            }
            if ($item['comment'] !== null) {
                $carry['comments'][] = $item['comment'];
            }
            return $carry;
        });

        return $post;
    }

//    /**
//     * @return Article[] Returns an array of Article objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Article
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
