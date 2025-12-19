<?php

namespace App\Controller;

use App\Repository\GameRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShopController extends AbstractController
{
    #[Route('/shop', name: 'app_shop')]
    public function index(Request $request, GameRepository $gameRepository, PaginatorInterface $paginator): Response
    {
        $platform = $request->query->get('platform');
        $genre = $request->query->get('genre');
        $search = $request->query->get('search');

        $queryBuilder = $gameRepository->createQueryBuilder('g');

        if ($platform) {
            $queryBuilder->andWhere('g.platform = :platform')
                ->setParameter('platform', $platform);
        }

        if ($genre) {
            $queryBuilder->andWhere('g.genre = :genre')
                ->setParameter('genre', $genre);
        }

        if ($search) {
            $queryBuilder->andWhere('g.title LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $queryBuilder->orderBy('g.title', 'ASC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            12
        );

        // Get all platforms and genres for filters
        $platforms = $gameRepository->createQueryBuilder('g')
            ->select('DISTINCT g.platform')
            ->where('g.platform IS NOT NULL')
            ->orderBy('g.platform', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        $genres = $gameRepository->createQueryBuilder('g')
            ->select('DISTINCT g.genre')
            ->where('g.genre IS NOT NULL')
            ->orderBy('g.genre', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return $this->render('shop/index.html.twig', [
            'pagination' => $pagination,
            'platforms' => $platforms,
            'genres' => $genres,
            'current_platform' => $platform,
            'current_genre' => $genre,
            'current_search' => $search,
        ]);
    }

    #[Route('/shop/{id}', name: 'app_shop_show', requirements: ['id' => '\d+'])]
    public function show(int $id, GameRepository $gameRepository): Response
    {
        $game = $gameRepository->find($id);

        if (!$game) {
            throw $this->createNotFoundException('Ce jeu n\'existe pas');
        }

        // Get similar games
        $similarGames = $gameRepository->findBy(
            ['platform' => $game->getPlatform()],
            ['createdAt' => 'DESC'],
            5
        );

        return $this->render('shop/show.html.twig', [
            'game' => $game,
            'similar_games' => $similarGames,
        ]);
    }
}