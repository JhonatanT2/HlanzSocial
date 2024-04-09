<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\AST\Join;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @extends ServiceEntityRepository<Post>
 *
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

//    /**
//     * @return Post[] Returns an array of Post objects
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

//    public function findOneBySomeField($value): ?Post
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
        public function findAllOrderedByDate(): array
        {
            $queryBuilder = $this->createQueryBuilder('p')
                ->orderBy('p.date', 'DESC'); // Ordena por la propiedad 'date' de forma descendente

            return $queryBuilder->getQuery()->getResult();
        }

    public function findAllFavoritosByUser($userId): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->join('p.likes', 'l')
            ->join('l.user', 'u')
            ->andWhere('u.id = :userId')
            ->setParameter('userId', $userId);

        return $queryBuilder->getQuery()->getResult();
    }

    public function findAllByUser($userId): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->andWhere('p.iduser = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.date', 'DESC');
        
            return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Busca posts por un término de búsqueda.
     *
     * @param string $query
     * @return Post[]
     */
    public function findBySearchQuery(string $query): array
    {
        // Realiza una consulta personalizada para buscar posts por un término de búsqueda
        return $this->createQueryBuilder('p')
            
            ->andWhere('p.text LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }

    public function findAllPostsOrderedByLikes(): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p')
            ->addSelect('COUNT(l.id) as likeCount')
            ->leftJoin('p.likes', 'l', 'WITH', 'l.post = p')
            ->groupBy('p.id')
            ->orderBy('likeCount', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }

    public function findAllPostsOrderedByComments(): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p', 'COUNT(c.id) as commentCount')
            ->leftJoin('p.comments', 'c')
            ->groupBy('p.id')
            ->orderBy('commentCount', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }

}
