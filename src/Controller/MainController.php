<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Section;
use App\Entity\User;
use App\Form\UserType;
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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{

    private $articleRepository;
    //  private $commentRepository;
    private $sectionRepository;
    //  private $tagRepository;
    private $userRepository;

    private $passwordHasher;

    public function __construct(ArticleRepository           $articleRepository,
        //   CommentRepository $commentRepository,
                                SectionRepository           $sectionRepository,
        //   TagRepository $tagRepository,
                                UserRepository              $userRepository,
                                UserPasswordHasherInterface $passwordHasher)
    {
        $this->articleRepository = $articleRepository;
        //    $this->commentRepository = $commentRepository;
        $this->sectionRepository = $sectionRepository;
        //    $this->tagRepository = $tagRepository;
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
    }

    private function getUserFullname()
    {
        $user = $this->getUser();
        return $user?->getFullname();
    }
    #[Route('/', name: 'public_home')]
    public function index(EntityManagerInterface $em, PaginatorInterface $pagi, Request $request): Response
    {

        $userName = $this->getUserFullname();
        $sections = $this->sectionRepository->findAll();
        return $this->render('main/public.main.html.twig', [
            'pagination' => $this->articleRepository->getPagination($em, $pagi, $request),
            'sections' => $sections,
            'authors' => $this->articleRepository->getAuthors($em),
            'userName' => $userName,
        ]);
    }

    #[Route('/section/{slug}', name: 'public_section')]
    public function section(EntityManagerInterface $em, string $slug, PaginatorInterface $pagi, Request $request): Response
    {
        $userName = $this->getUserFullname();
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
            'userName' => $userName,
        ]);
    }

    #[Route('/author/{id}', name: 'public_author')]
    public function author(EntityManagerInterface $em, int $id, PaginatorInterface $pagi, Request $request): Response
    {

        $userName = $this->getUserFullname();
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
            'userName' => $userName,
        ]);
    }

    #[Route('/article/{slug}/{id}', name: 'public_article')]
    public function article(EntityManagerInterface $em, string $slug, int $id, PaginatorInterface $pagi, Request $request): Response
    {
        $userName = $this->getUserFullname();

        $art = $this->articleRepository->findOneBy(['title_slug' => $slug]);
        if($art->getId() !== $id){
            $art = $this->articleRepository->findOneBy(['id' => $id]);
        }
        $author = $art->getUser()->getId();
        $authors = $this->userRepository->getAllAuthors($author);
        $articles = $this->articleRepository->findAdjacentArticles($id, $author);
        $artComms = $this->articleRepository->createQueryBuilder('a')
            ->leftJoin('a.comments', 'c')
            ->leftJoin('a.user', 'u')
            ->addSelect('c', 'u')
            ->where('a.title_slug = :slug')
            ->setParameter('slug', $slug)
            ->andWhere('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if ($artComms) {
            $comments = $artComms->getComments();
        } else {
            $comments = null;
        }

        $sections = $this->sectionRepository->findAll();
        $artSections = $articles['main']->getSections();

        return $this->render('main/public.article.html.twig', [
            'article' => $articles['main'],
            'prev_art' => $articles['prev'],
            'next_art' => $articles['next'],
            'sections' => $sections,
            'artSections' => $artSections,
            'authors' => $authors,
            'comments' => $comments,
            'userName' => $userName,

        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route('/new', name: 'public_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $textPass = $form->get('password')->getData();

            $hashPass = $this->passwordHasher->hashPassword($user, $textPass);
            $user->setPassword($hashPass);

            $user->setRoles(["ROLE_USER"]);
            $user->setUniqid(uniqid('user_', true));
            $user->setActivate(false);
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('public_home', [], Response::HTTP_SEE_OTHER);
        }
        $sections = $entityManager->getRepository(Section::class)->findAll();
        $authors = $entityManager->getRepository(Article::class)->getAuthors($entityManager);
        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
            'sections' => $sections,
            'authors' => $authors,
        ]);
    }
}
