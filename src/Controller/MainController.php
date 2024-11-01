<?php

namespace App\Controller;

use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
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

        return $this->render('main/public.main.html.twig', [
            'pagination' => $pagination,
        ]);
    }
}
