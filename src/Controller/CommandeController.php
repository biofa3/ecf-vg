<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Form\CommandeFormType;
use App\Repository\MenuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class CommandeController extends AbstractController
{
    #[Route('/commander/{menuId}', name: 'app_commande')]
    public function new(
        int $menuId,
        Request $request,
        MenuRepository $menuRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $menu = $menuRepository->find($menuId);
        if (!$menu) {
            throw $this->createNotFoundException('Menu non trouvé');
        }

        $commande = new Commande();
        $commande->setUtilisateur($this->getUser());
        $commande->setMenu($menu);
        $commande->setPrixMenu($menu->getPrixParPersonne());
        $commande->setStatut('en attente');
        $commande->setDateCommande(new \DateTime());
        $commande->setPretMateriel(false);
        $commande->setRestitutionMateriel(false);

        $form = $this->createForm(CommandeFormType::class, $commande, [
            'menu' => $menu,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nombrePersonnes = $form->get('nombre_personne')->getData();
            $commande->setNombrePersonne($nombrePersonnes);
            $prixBase = $menu->getPrixParPersonne() * $nombrePersonnes;

            $reduction = 0;
            if ($nombrePersonnes >= $menu->getNombrePersonneMinimum() + 5) {
                $reduction = $prixBase * 0.10;
            }

            $prixLivraison = 0;
            $ville = $form->get('ville')->getData();
            if (strtolower($ville) !== 'bordeaux') {
                $prixLivraison = 5.00;
            }

            $commande->setPrixMenu($prixBase - $reduction);
            $commande->setPrixLivraison($prixLivraison);
            $commande->setNumeroCommande(uniqid('CMD-'));

            $entityManager->persist($commande);
            $entityManager->flush();

            $emailBody = $this->renderView('emails/confirmation_commande.html.twig', [
                'commande' => $commande,
            ]);

            $email = (new Email())
                ->from('noreply@vite-gourmand.fr')
                ->to($commande->getUtilisateur()->getEmail())
                ->subject('Confirmation de votre commande n° ' . $commande->getNumeroCommande())
                ->html($emailBody);

            $mailer->send($email);

            return $this->redirectToRoute('app_commande_confirmation', [
                'id' => $commande->getId()
            ]);
        }

        return $this->render('commande/new.html.twig', [
            'form' => $form->createView(),
            'menu' => $menu,
        ]);
    }

    #[Route('/commander/confirmation/{id}', name: 'app_commande_confirmation')]
    public function confirmation(int $id, \App\Repository\CommandeRepository $commandeRepository): Response
    {
        $commande = $commandeRepository->find($id);

        if (!$commande || $commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createNotFoundException('Commande non trouvée');
        }

        return $this->render('commande/confirmation.html.twig', [
            'commande' => $commande,
        ]);
    }
}