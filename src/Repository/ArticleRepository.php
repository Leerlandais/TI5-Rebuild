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

    public function findAdjacentArticles(int $id): array
    {
        $currentArticle = $this->find($id);
        if (!$currentArticle) {
            return ['current' => null, 'previous' => null, 'next' => null];
        }

        $nextArticle = $this->createQueryBuilder('a')
            ->where('a.id > :id')
            ->setParameter('id', $id)
            ->andWhere('a.published = true')
            ->orderBy('a.article_date_posted', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if (!$nextArticle) {
            $nextArticle = $this->createQueryBuilder('a')
                ->where('a.published = true')
                ->orderBy('a.article_date_posted', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        $previousArticle = $this->createQueryBuilder('a')
            ->where('a.id < :id')
            ->setParameter('id', $id)
            ->andWhere('a.published = true')
            ->orderBy('a.article_date_posted', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if (!$previousArticle) {
            $previousArticle = $this->createQueryBuilder('a')
                ->where('a.published = true')
                ->orderBy('a.article_date_posted', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        return [
            'current' => $currentArticle,
            'previous' => $previousArticle,
            'next' => $nextArticle,
        ];
    }
}
