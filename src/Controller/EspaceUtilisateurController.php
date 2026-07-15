<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Commande;
use App\Form\AvisFormType;
use App\Form\InfosPersonnellesFormType;
use App\Form\ModifierCommandeFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/utilisateur')]
class EspaceUtilisateurController extends AbstractController
{
    #[Route('', name: 'app_espace_utilisateur')]
    public function index(EntityManagerInterface $em): Response
    {
        $commandes = $em->getRepository(Commande::class)->findBy(
            ['utilisateur' => $this->getUser()],
            ['date_commande' => 'DESC']
        );

        return $this->render('espace_utilisateur/index.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/commande/{id}', name: 'app_utilisateur_commande_detail')]
    public function detail(int $id, EntityManagerInterface $em): Response
    {
        $commande = $em->getRepository(Commande::class)->find($id);

        if (!$commande || $commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createNotFoundException();
        }

        return $this->render('espace_utilisateur/commande_detail.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/commande/{id}/modifier', name: 'app_utilisateur_commande_modifier')]
    public function modifier(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $commande = $em->getRepository(Commande::class)->find($id);

        if (!$commande || $commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createNotFoundException();
        }

        if ($commande->getStatut() !== 'en attente') {
            $this->addFlash('warning', 'Cette commande ne peut plus être modifiée (statut : ' . $commande->getStatut() . ').');
            return $this->redirectToRoute('app_utilisateur_commande_detail', ['id' => $id]);
        }

        $form = $this->createForm(ModifierCommandeFormType::class, $commande, [
            'minimum' => $commande->getMenu()->getNombrePersonneMinimum(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nb       = $commande->getNombrePersonne();
            $prixBrut = $commande->getMenu()->getPrixParPersonne() * $nb;
            $reduction = ($nb >= $commande->getMenu()->getNombrePersonneMinimum() + 5) ? $prixBrut * 0.10 : 0;
            $livraison = (strtolower(trim($commande->getVille())) === 'bordeaux') ? 0.00 : 5.00;

            $commande->setPrixMenu($prixBrut - $reduction);
            $commande->setPrixLivraison($livraison);

            $em->flush();
            $this->addFlash('success', 'Votre commande a bien été modifiée.');
            return $this->redirectToRoute('app_utilisateur_commande_detail', ['id' => $id]);
        }

        return $this->render('espace_utilisateur/commande_modifier.html.twig', [
            'form'     => $form->createView(),
            'commande' => $commande,
        ]);
    }

    #[Route('/commande/{id}/annuler', name: 'app_utilisateur_commande_annuler', methods: ['POST'])]
    public function annuler(int $id, EntityManagerInterface $em): Response
    {
        $commande = $em->getRepository(Commande::class)->find($id);

        if (!$commande || $commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createNotFoundException();
        }

        if ($commande->getStatut() !== 'en attente') {
            $this->addFlash('warning', 'Cette commande ne peut plus être annulée.');
            return $this->redirectToRoute('app_utilisateur_commande_detail', ['id' => $id]);
        }

        $commande->setStatut('annulée');
        $em->flush();

        $this->addFlash('success', 'Votre commande a été annulée.');
        return $this->redirectToRoute('app_espace_utilisateur');
    }

    #[Route('/profil', name: 'app_utilisateur_profil')]
    public function profil(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(InfosPersonnellesFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Vos informations ont été mises à jour.');
            return $this->redirectToRoute('app_utilisateur_profil');
        }

        return $this->render('espace_utilisateur/profil.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/avis/{id}', name: 'app_utilisateur_avis')]
    public function laisserAvis(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $commande = $em->getRepository(Commande::class)->find($id);

        if (!$commande || $commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createNotFoundException();
        }

        if ($commande->getStatut() !== 'terminée') {
            $this->addFlash('warning', 'Vous ne pouvez laisser un avis que sur une commande terminée.');
            return $this->redirectToRoute('app_espace_utilisateur');
        }

        if ($commande->getAvis() !== null) {
            $this->addFlash('warning', 'Vous avez déjà laissé un avis pour cette commande.');
            return $this->redirectToRoute('app_espace_utilisateur');
        }

        $avis = new Avis();
        $form = $this->createForm(AvisFormType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $avis->setUtilisateur($this->getUser());
            $avis->setCommande($commande);
            $avis->setStatut('en attente');

            $em->persist($avis);
            $em->flush();

            $this->addFlash('success', 'Votre avis a été soumis et sera publié après validation.');
            return $this->redirectToRoute('app_espace_utilisateur');
        }

        return $this->render('espace_utilisateur/avis.html.twig', [
            'form'     => $form->createView(),
            'commande' => $commande,
        ]);
    }
}
