<?php

namespace App\Controller;

use App\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(GameRepository $gameRepository): Response
    {
        $featuredGames = $gameRepository->findBy(['featured' => true], ['createdAt' => 'DESC'], 6);
        $latestGames = $gameRepository->findBy([], ['releaseDate' => 'DESC'], 8);
        
        return $this->render('home/index.html.twig', [
            'featured_games' => $featuredGames,
            'latest_games' => $latestGames,
        ]);
    }
}