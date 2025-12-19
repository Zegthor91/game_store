<?php

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 *
 * @method Game|null find($id, $lockMode = null, $lockVersion = null)
 * @method Game|null findOneBy(array $criteria, array $orderBy = null)
 * @method Game[]    findAll()
 * @method Game[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function save(Game $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Game $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve les jeux mis en avant
     */
    public function findFeaturedGames(int $limit = 6): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.featured = :featured')
            ->setParameter('featured', true)
            ->orderBy('g.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les derniers jeux sortis
     */
    public function findLatestReleases(int $limit = 8): array
    {
        return $this->createQueryBuilder('g')
            ->orderBy('g.releaseDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de jeux par titre
     */
    public function searchByTitle(string $query): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.title LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('g.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les jeux par plateforme
     */
    public function findByPlatform(string $platform): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.platform = :platform')
            ->setParameter('platform', $platform)
            ->orderBy('g.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les jeux par genre
     */
    public function findByGenre(string $genre): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.genre = :genre')
            ->setParameter('genre', $genre)
            ->orderBy('g.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}