<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/order')]
#[IsGranted('ROLE_USER')]
class OrderController extends AbstractController
{
    #[Route('/checkout', name: 'app_order_checkout')]
    public function checkout(CartRepository $cartRepository): Response
    {
        $user = $this->getUser();
        $cart = $cartRepository->findOneBy(['user' => $user]);

        if (!$cart || $cart->getCartItems()->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide');
            return $this->redirectToRoute('app_cart_index');
        }

        return $this->render('order/checkout.html.twig', [
            'cart_items' => $cart->getCartItems(),
            'total' => $cart->getTotal(),
        ]);
    }

    #[Route('/place', name: 'app_order_place', methods: ['POST'])]
    public function place(
        Request $request,
        CartRepository $cartRepository,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('place_order', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide');
            return $this->redirectToRoute('app_order_checkout');
        }

        $user = $this->getUser();
        $cart = $cartRepository->findOneBy(['user' => $user]);

        if (!$cart || $cart->getCartItems()->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide');
            return $this->redirectToRoute('app_cart_index');
        }

        // Create order
        $order = new Order();
        $order->setUser($user);
        $order->setTotalAmount((string)$cart->getTotal());
        $order->setStatus('paid');

        // Create order items from cart
        foreach ($cart->getCartItems() as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setOrderRef($order);
            $orderItem->setGame($cartItem->getGame());
            $orderItem->setQuantity($cartItem->getQuantity());
            $orderItem->setUnitPrice($cartItem->getGame()->getPrice());
            
            $order->addOrderItem($orderItem);
            $em->persist($orderItem);
        }

        $em->persist($order);

        // Clear cart
        foreach ($cart->getCartItems() as $cartItem) {
            $em->remove($cartItem);
        }

        $em->flush();

        $this->addFlash('success', 'Votre commande a été passée avec succès !');
        return $this->redirectToRoute('app_order_detail', ['id' => $order->getId()]);
    }

    #[Route('/history', name: 'app_order_history')]
    public function history(OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();
        $orders = $orderRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('order/history.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/{id}', name: 'app_order_detail', requirements: ['id' => '\d+'])]
    public function detail(int $id, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->find($id);

        if (!$order || $order->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Commande non trouvée');
        }

        return $this->render('order/detail.html.twig', [
            'order' => $order,
        ]);
    }
}