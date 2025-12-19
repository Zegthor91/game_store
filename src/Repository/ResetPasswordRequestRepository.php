<?php

namespace App\Repository;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SymfonyCasts\Bundle\ResetPassword\Persistence\ResetPasswordRequestRepositoryInterface;

class ResetPasswordRequestRepository extends ServiceEntityRepository implements ResetPasswordRequestRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResetPasswordRequest::class);
    }

    public function createResetPasswordRequest(object $user, \DateTimeInterface $expiresAt, string $selector, string $hashedToken): ResetPasswordRequest
    {
        $resetPasswordRequest = new ResetPasswordRequest($user, $expiresAt, $selector, $hashedToken);

        $this->_em->persist($resetPasswordRequest);
        $this->_em->flush();

        return $resetPasswordRequest;
    }

    public function getUserIdentifier(object $user): string
    {
        return $user->getUserIdentifier();
    }

    public function removeResetPasswordRequest(ResetPasswordRequest $resetPasswordRequest): void
    {
        $this->_em->remove($resetPasswordRequest);
        $this->_em->flush();
    }

    public function removeExpiredResetPasswordRequests(): int
    {
        return $this->createQueryBuilder('r')
            ->delete()
            ->where('r.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}