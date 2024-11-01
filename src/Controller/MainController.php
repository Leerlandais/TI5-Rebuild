<?php

namespace App\Controller;

use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'public_home')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $allArticles = $entityManager->getRepository(Article::class)->findAll();
        $articles = [];
        foreach ($allArticles as $article) {
            if ($article->isPublished()) {
                $articles[] = $article;
            }
        }
        return $this->render('main/public.main.html.twig', [
            'articles' => $articles,
        ]);
    }
}
