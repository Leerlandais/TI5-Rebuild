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
    private function getAuthors(EntityManagerInterface $em)
    {
        return $em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.roles NOT LIKE :role')
            ->setParameter('role', '%ROLE_USER%')
            ->getQuery()
            ->getResult();
    }

    #[Route('/', name: 'public_home')]
    public function index(Request $request, PaginatorInterface $paginator, EntityManagerInterface $em): Response
    {
        $queryBuilder = $em->getRepository(Article::class)->createQueryBuilder('a')
            ->where('a.published = 1')
            ->orderBy('a.id', 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            5
        );
        $authors = $em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.roles NOT LIKE :role')
            ->setParameter('role', '%ROLE_USER%')
            ->getQuery()
            ->getResult();

        $sections = $em->getRepository(Section::class)->findAll();
        return $this->render('main/public.main.html.twig', [
            'pagination' => $pagination,
            'sections' => $sections,
            'authors' => $this->getAuthors($em),
        ]);
    }

    #[Route('/section/{slug}', name: 'public_section')]
    public function section(EntityManagerInterface $em, $slug): Response
    {
        $sections = $em->getRepository(Section::class)->findBy(['section_slug' => $slug]);

        return $this->render('main/public.section.html.twig', [
            'sections' => $sections,
            'authors' => $this->getAuthors($em),
        ]);
    }
}
