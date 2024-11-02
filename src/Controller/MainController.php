<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Section;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    private function getAuthors(EntityManagerInterface $em) : array
    {
        return $em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.roles NOT LIKE :role')
            ->setParameter('role', '%ROLE_USER%')
            ->getQuery()
            ->getResult();
    }

    private function getPagination(EntityManagerInterface $em, PaginatorInterface $paginator, Request $request) : \Knp\Component\Pager\Pagination\PaginationInterface
    {
        $queryBuilder = $em->getRepository(Article::class)->createQueryBuilder('a')
            ->where('a.published = 1')
            ->orderBy('a.id', 'DESC');

        return $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            5
        );
    }

    private function getPaginationBySection(EntityManagerInterface $em, PaginatorInterface $paginator, Request $request, string $section) : \Knp\Component\Pager\Pagination\PaginationInterface
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

    private function getPaginationByAuthor(EntityManagerInterface $em, PaginatorInterface $paginator, Request $request, int $id) : \Knp\Component\Pager\Pagination\PaginationInterface
    {
        $queryBuilder = $em->getRepository(Article::class)->findBy(['user' => $id]);

        return $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            5
        );
    }



    #[Route('/', name: 'public_home')]
    public function index(EntityManagerInterface $em, PaginatorInterface $pagi, Request $request): Response
    {
        $sections = $em->getRepository(Section::class)->findAll();
        return $this->render('main/public.main.html.twig', [
            'pagination' => $this->getPagination($em, $pagi, $request),
            'sections' => $sections,
            'authors' => $this->getAuthors($em),
        ]);
    }

    #[Route('/section/{slug}', name: 'public_section')]
    public function section(EntityManagerInterface $em, string $slug, PaginatorInterface $pagi, Request $request): Response
    {
        $section = $em->getRepository(Section::class)->findOneBy(['section_slug' => $slug]);

        $sections = $em->getRepository(Section::class)->findAll();

        return $this->render('main/public.section.html.twig', [
            'section' => $section,
            'sections' => $sections,
            'authors' => $this->getAuthors($em),
            'pagination' => $this->getPaginationBySection($em, $pagi, $request, $slug),
        ]);
    }

    #[Route('/author/{id}', name: 'public_author')]
    public function author(EntityManagerInterface $em, int $id, PaginatorInterface $pagi, Request $request): Response
    {
        $author = $em->getRepository(User::class)->find($id);
        $sections = $em->getRepository(Section::class)->findAll();
        $articleCount = $em->getRepository(Article::class)
            ->count(['user' => $id]);
        return $this->render('main/public.author.html.twig', [
            'author' => $author,
            'authors' => $this->getAuthors($em),
            'pagination' => $this->getPaginationByAuthor($em, $pagi, $request, $id),
            'sections' => $sections,
            'articleCount' => $articleCount,
            ]);
    }
}
