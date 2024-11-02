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





    #[Route('/', name: 'public_home')]
    public function index(EntityManagerInterface $em, PaginatorInterface $pagi, Request $request): Response
    {
        $artRepo = $em->getRepository(Article::class);
        $sections = $em->getRepository(Section::class)->findAll();
        return $this->render('main/public.main.html.twig', [
            'pagination' => $artRepo->getPagination($em, $pagi, $request),
            'sections' => $sections,
            'authors' => $artRepo->getAuthors($em),
        ]);
    }

    #[Route('/section/{slug}', name: 'public_section')]
    public function section(EntityManagerInterface $em, string $slug, PaginatorInterface $pagi, Request $request): Response
    {
        $section = $em->getRepository(Section::class)->findOneBy(['section_slug' => $slug]);
        // better like this cos it returns all sections but the current one :)
        $sections = $em->getRepository(Section::class)->createQueryBuilder('s')
            ->where('s.section_slug != :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getResult();

        $artRepo = $em->getRepository(Article::class);

        return $this->render('main/public.section.html.twig', [
            'section' => $section,
            'sections' => $sections,
            'authors' => $artRepo->getAuthors($em),
            'pagination' => $artRepo->getPaginationBySection($em, $pagi, $request, $slug),
        ]);
    }

    #[Route('/author/{id}', name: 'public_author')]
    public function author(EntityManagerInterface $em, int $id, PaginatorInterface $pagi, Request $request): Response
    {
        $author = $em->getRepository(User::class)->find($id);
        $sections = $em->getRepository(Section::class)->findAll();
        $articleCount = $em->createQueryBuilder()
            ->select('count(a.id)')
            ->from(Article::class, 'a')
            ->where('a.user = :user')
            ->setParameter('user', $id)
            ->andWhere('a.published = true')
            ->getQuery()
            ->getSingleScalarResult();
        // did the same for authors as for sections
        $userRepo = $em->getRepository(User::class);
        $authors = $userRepo->getAllAuthors($id);
        $artRepo = $em->getRepository(Article::class);
        return $this->render('main/public.author.html.twig', [
            'author' => $author,
            'authors' => $authors,
            'pagination' => $artRepo->getPaginationByAuthor($em, $pagi, $request, $id),
            'sections' => $sections,
            'articleCount' => $articleCount,
        ]);
    }

    #[Route('/article/{slug}', name: 'public_article')]
    public function article(EntityManagerInterface $em, string $slug, PaginatorInterface $pagi, Request $request): Response
    {
        $art = $em->getRepository(Article::class)->findOneBy(['title_slug' => $slug]);
        $artId = $art->getId();
        $author = $art->getUser()->getId();
        $userRepo = $em->getRepository(User::class);
        $authors = $userRepo->getAllAuthors($author);
        $articles = $em->getRepository(Article::class)->findAdjacentArticles($artId, $author);

        $sections = $articles['main']->getSections();

        return $this->render('main/public.article.html.twig', [
            'article' => $articles['main'],
            'prev_art' => $articles['prev'],
            'next_art' => $articles['next'],
            'sections' => $sections,
            'authors' => $authors,
        ]);
    }
}
