<?php

namespace App\Controller;
use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $email = (new Email())
                ->from($data['email'])
                ->to('contact@gamestore.fr')
                ->subject('Nouveau message de contact - Game Store')
                ->text(sprintf(
                    "Nom: %s\nEmail: %s\n\nMessage:\n%s",
                    $data['name'],
                    $data['email'],
                    $data['message']
                ));

            try {
                $mailer->send($email);
                $this->addFlash('success', 'Votre message a été envoyé avec succès !');
                return $this->redirectToRoute('app_contact');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi du message.');
            }
        }
        return $this->render('contact/index.html.twig', [
            'form' => $form,
        ]);
    }
}