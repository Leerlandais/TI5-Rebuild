<?php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

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
            ->orderBy('a.article_date_posted', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if (!$nextArticle) {
            $nextArticle = $this->createQueryBuilder('a')
                ->where('a.user = :author')
                ->setParameter('author', $author)
                ->andWhere('a.published = true')
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
                ->andWhere('a.published = true')
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

    public function getAuthors(EntityManagerInterface $em): array
    {
        return $em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.roles NOT LIKE :role')
            ->setParameter('role', '%ROLE_USER%')
            ->getQuery()
            ->getResult();
    }

    public function getPagination(EntityManagerInterface $em, PaginatorInterface $paginator, Request $request): \Knp\Component\Pager\Pagination\PaginationInterface
    {
        $queryBuilder = $em->getRepository(Article::class)->createQueryBuilder('a')
            ->where('a.published = 1')
            ->orderBy('a.article_date_posted', 'DESC');

        return $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            5
        );
    }

    public function getPaginationBySection(EntityManagerInterface $em, PaginatorInterface $paginator, Request $request, string $section): \Knp\Component\Pager\Pagination\PaginationInterface
    {
        $queryBuilder = $em->getRepository(Article::class)->createQueryBuilder('a')
            ->where('a.published = 1')
            ->join('a.sections', 's')
            ->where('s.section_slug = :section')
            ->setParameter('section', $section)
            ->orderBy('a.id', 'DESC');

        return $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            5
        );
    }

    public function getPaginationByAuthor(EntityManagerInterface $em, PaginatorInterface $paginator, Request $request, int $id): \Knp\Component\Pager\Pagination\PaginationInterface
    {

        $queryBuilder = $em->getRepository(Article::class)->createQueryBuilder('a')
            ->where('a.published = 1')
            ->andWhere('a.user = :id')
            ->setParameter('id', $id)
            ->orderBy('a.article_date_posted', 'DESC');
        return $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            5
        );
    }
}
