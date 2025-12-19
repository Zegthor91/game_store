<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Repository\CartItemRepository;
use App\Repository\CartRepository;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cart')]
#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    #[Route('/', name: 'app_cart_index')]
    public function index(CartRepository $cartRepository): Response
    {
        $user = $this->getUser();
        $cart = $cartRepository->findOneBy(['user' => $user]);

        if (!$cart) {
            return $this->render('cart/index.html.twig', [
                'cart_items' => [],
                'total' => 0,
            ]);
        }

        return $this->render('cart/index.html.twig', [
            'cart_items' => $cart->getCartItems(),
            'total' => $cart->getTotal(),
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(
        int $id,
        GameRepository $gameRepository,
        CartRepository $cartRepository,
        EntityManagerInterface $em
    ): Response {
        $game = $gameRepository->find($id);

        if (!$game) {
            throw $this->createNotFoundException('Jeu non trouvé');
        }

        if ($game->getStock() <= 0) {
            $this->addFlash('error', 'Ce jeu n\'est plus en stock');
            return $this->redirectToRoute('app_shop_show', ['id' => $id]);
        }

        $user = $this->getUser();
        $cart = $cartRepository->findOneBy(['user' => $user]);

        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $em->persist($cart);
        }

        // Check if game already in cart
        $existingItem = null;
        foreach ($cart->getCartItems() as $item) {
            if ($item->getGame()->getId() === $game->getId()) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem) {
            $existingItem->setQuantity($existingItem->getQuantity() + 1);
        } else {
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setGame($game);
            $cartItem->setQuantity(1);
            $em->persist($cartItem);
        }

        $cart->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', 'Jeu ajouté au panier');
        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/update/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(
        int $id,
        Request $request,
        CartItemRepository $cartItemRepository,
        EntityManagerInterface $em
    ): Response {
        $cartItem = $cartItemRepository->find($id);

        if (!$cartItem || $cartItem->getCart()->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Article non trouvé');
        }

        $quantity = $request->request->getInt('quantity', 1);
        $quantity = max(1, min(10, $quantity));

        $cartItem->setQuantity($quantity);
        $cartItem->getCart()->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', 'Quantité mise à jour');
        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(
        int $id,
        CartItemRepository $cartItemRepository,
        EntityManagerInterface $em
    ): Response {
        $cartItem = $cartItemRepository->find($id);

        if (!$cartItem || $cartItem->getCart()->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Article non trouvé');
        }

        $em->remove($cartItem);
        $em->flush();

        $this->addFlash('success', 'Article retiré du panier');
        return $this->redirectToRoute('app_cart_index');
    }
}