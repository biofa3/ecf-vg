<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Role;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegisterController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $utilisateur = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $utilisateur->setPassword(
                $passwordHasher->hashPassword(
                    $utilisateur,
                    $form->get('plainPassword')->getData()
                )
            );

            $role = $entityManager->getRepository(Role::class)->findOneBy(['libelle' => 'USER']);
            $utilisateur->setRole($role);

            $entityManager->persist($utilisateur);
            $entityManager->flush();

            // Mail de bienvenue à l'utilisateur
            $emailBienvenue = (new Email())
                ->from('noreply@vite-gourmand.fr')
                ->to($utilisateur->getEmail())
                ->cc('jose@vite-gourmand.fr')
                ->subject('Bienvenue chez Vite & Gourmand !')
                ->html('
                    <h2>Bienvenue ' . $utilisateur->getPrenom() . ' !</h2>
                    <p>Votre compte a bien été créé sur <strong>Vite & Gourmand</strong>.</p>
                    <p>Vous pouvez dès maintenant consulter nos menus et passer commande.</p>
                    <p>À bientôt,<br>L\'équipe Vite & Gourmand</p>
                ');
            $mailer->send($emailBienvenue);

            return $this->redirectToRoute('app_login');
        }

        return $this->render('register/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}