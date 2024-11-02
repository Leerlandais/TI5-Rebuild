<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function findAdjacentArticles(int $id, int $author): array
    {
        $mainArticle = $this->find($id);
        if (!$mainArticle) {
            return ['main' => null, 'previous' => null, 'next' => null];
        }

        $nextArticle = $this->createQueryBuilder('a')
            ->where('a.id > :id')
            ->setParameter('id', $id)
            ->andWhere('a.published = true')
            ->andWhere('a.user = :author')
            ->setParameter('author', $author)
            ->orderBy('a.article_date_posted', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if (!$nextArticle) {
            $nextArticle = $this->createQueryBuilder('a')
                ->where('a.user = :author')
                ->setParameter('author', $author)
                ->where('a.published = true')
                ->orderBy('a.article_date_posted', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        $previousArticle = $this->createQueryBuilder('a')
            ->where('a.id < :id')
            ->setParameter('id', $id)
            ->andWhere('a.user = :author')
            ->setParameter('author', $author)
            ->andWhere('a.published = true')
            ->orderBy('a.article_date_posted', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if (!$previousArticle) {
            $previousArticle = $this->createQueryBuilder('a')
                ->where('a.user = :author')
                ->setParameter('author', $author)
                ->where('a.published = true')
                ->orderBy('a.article_date_posted', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        return [
            'main' => $mainArticle,
            'prev' => $previousArticle,
            'next' => $nextArticle,
        ];
    }
}
