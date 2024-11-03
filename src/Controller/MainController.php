<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Section;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use App\Repository\SectionRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{

    private $articleRepository;
    private $commentRepository;
    private $sectionRepository;
    private $tagRepository;
    private $userRepository;

    public function __construct(ArticleRepository $articleRepository,
                                CommentRepository $commentRepository,
                                SectionRepository $sectionRepository,
                                TagRepository $tagRepository,
                                UserRepository $userRepository)
    {
        $this->articleRepository = $articleRepository;
        $this->commentRepository = $commentRepository;
        $this->sectionRepository = $sectionRepository;
        $this->tagRepository = $tagRepository;
        $this->userRepository = $userRepository;
    }


    #[Route('/', name: 'public_home')]
    public function index(EntityManagerInterface $em, PaginatorInterface $pagi, Request $request): Response
    {
        $sections = $this->sectionRepository->findAll();
        return $this->render('main/public.main.html.twig', [
            'pagination' => $this->articleRepository->getPagination($em, $pagi, $request),
            'sections' => $sections,
            'authors' => $this->articleRepository->getAuthors($em),
        ]);
    }

    #[Route('/section/{slug}', name: 'public_section')]
    public function section(EntityManagerInterface $em, string $slug, PaginatorInterface $pagi, Request $request): Response
    {
        $section = $this->sectionRepository->findOneBy(['section_slug' => $slug]);
        // better like this cos it returns all sections but the current one :)
        $sections = $this->sectionRepository->createQueryBuilder('s')
            ->where('s.section_slug != :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getResult();

        return $this->render('main/public.section.html.twig', [
            'section' => $section,
            'sections' => $sections,
            'authors' => $this->articleRepository->getAuthors($em),
            'pagination' => $this->articleRepository->getPaginationBySection($em, $pagi, $request, $slug),
        ]);
    }

    #[Route('/author/{id}', name: 'public_author')]
    public function author(EntityManagerInterface $em, int $id, PaginatorInterface $pagi, Request $request): Response
    {
        $author = $this->userRepository->find($id);
        $sections = $this->sectionRepository->findAll();
        $articleCount = $em->createQueryBuilder()
            ->select('count(a.id)')
            ->from(Article::class, 'a')
            ->where('a.user = :user')
            ->setParameter('user', $id)
            ->andWhere('a.published = true')
            ->getQuery()
            ->getSingleScalarResult();
        // did the same for authors as for sections
        $authors = $this->userRepository->getAllAuthors($id);
        return $this->render('main/public.author.html.twig', [
            'author' => $author,
            'authors' => $authors,
            'pagination' => $this->articleRepository->getPaginationByAuthor($em, $pagi, $request, $id),
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
        $artRepo = $em->getRepository(Article::class);
        $articles = $artRepo->findAdjacentArticles($artId, $author);
        $artComms = $artRepo->createQueryBuilder('a')
            ->leftJoin('a.comments', 'c')
            ->leftJoin('a.user', 'u')
            ->addSelect('c', 'u')
            ->where('a.title_slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();

        if ($artComms) {
            $comments = $artComms->getComments();

        } else {
            echo "Article not found.";
        }
        /*
         * ...once that's done, tidy up the repositories...
         * ...this means, get all the repositories via the construct and change all the routing functions so that they no longer get the repos!
         * Finish off the comment stuff and create a new tag for Git
         * Then make a new branch for the repo tidying
         * Once that's done, implement the admin side (update the Git tag - make sure there's a hard save before starting the admin side but make sure public end is 100% finished first!)
         */

        $sections = $em->getRepository(Section::class)->findAll();
        $artSections = $articles['main']->getSections();

        return $this->render('main/public.article.html.twig', [
            'article' => $articles['main'],
            'prev_art' => $articles['prev'],
            'next_art' => $articles['next'],
            'sections' => $sections,
            'artSections' => $artSections,
            'authors' => $authors,
            'comments' => $comments,

        ]);
    }
}
