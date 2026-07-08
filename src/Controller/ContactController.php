<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request): Response
    {
        $sent = false;

        if ($request->isMethod('POST')) {
            // Dans un vrai projet : envoi email avec Symfony Mailer
            $sent = true;
        }

        return $this->render('contact/index.html.twig', [
            'sent' => $sent,
        ]);
    }
}
