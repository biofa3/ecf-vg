<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        $sent = false;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('contact', $request->request->get('_csrf_token'))) {
                throw $this->createAccessDeniedException('Token CSRF invalide.');
            }

            $nom = $request->request->get('nom');
            $sujet = $request->request->get('sujet');
            $message = $request->request->get('message');
            $emailExpediteur = $request->request->get('email');

            $email = (new Email())
                ->from('noreply@vite-gourmand.fr')
                ->to('jose@vite-gourmand.fr')
                ->replyTo($emailExpediteur)
                ->subject('[Contact] ' . $sujet)
                ->html('
                    <h3>' . htmlspecialchars($sujet) . '</h3>
                    <p>' . nl2br(htmlspecialchars($message)) . '</p>
                    <hr>
                    <p><strong>De :</strong> ' . htmlspecialchars($nom) . ' &lt;' . htmlspecialchars($emailExpediteur) . '&gt;</p>
                ');

            $mailer->send($email);
            $sent = true;
        }

        return $this->render('contact/index.html.twig', [
            'sent' => $sent,
        ]);
    }
}
