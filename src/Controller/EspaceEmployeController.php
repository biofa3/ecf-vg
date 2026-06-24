<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Menu;
use App\Entity\Avis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/employe')]
class EspaceEmployeController extends AbstractController
{
    #[Route('', name: 'app_espace_employe')]
    public function index(EntityManagerInterface $em): Response
    {
        $commandes = $em->getRepository(Commande::class)->findBy([], ['date_commande' => 'DESC']);

        return $this->render('espace_employe/index.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/commande/{id}/statut', name: 'app_employe_statut', methods: ['POST'])]
    public function changerStatut(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $commande = $em->getRepository(Commande::class)->find($id);
        if (!$commande) {
            throw $this->createNotFoundException();
        }

        $statut = $request->request->get('statut');
        $statutsValides = ['en attente', 'confirmée', 'en préparation', 'livrée', 'annulée'];
        if (in_array($statut, $statutsValides)) {
            $commande->setStatut($statut);
            $em->flush();
            $this->addFlash('success', 'Statut mis à jour.');
        }

        return $this->redirectToRoute('app_espace_employe');
    }

    #[Route('/menus', name: 'app_employe_menus')]
    public function menus(EntityManagerInterface $em): Response
    {
        $menus = $em->getRepository(Menu::class)->findAll();

        return $this->render('espace_employe/menus.html.twig', [
            'menus' => $menus,
        ]);
    }

    #[Route('/avis', name: 'app_employe_avis')]
    public function avis(EntityManagerInterface $em): Response
    {
        $avis = $em->getRepository(Avis::class)->findAll();

        return $this->render('espace_employe/avis.html.twig', [
            'avis' => $avis,
        ]);
    }

    #[Route('/avis/{id}/valider', name: 'app_employe_avis_valider')]
    public function validerAvis(int $id, EntityManagerInterface $em): Response
    {
        $avis = $em->getRepository(Avis::class)->find($id);
        if (!$avis) {
            throw $this->createNotFoundException();
        }

        $avis->setStatut('publié');
        $em->flush();
        $this->addFlash('success', 'Avis publié.');

        return $this->redirectToRoute('app_employe_avis');
    }

    #[Route('/avis/{id}/refuser', name: 'app_employe_avis_refuser')]
    public function refuserAvis(int $id, EntityManagerInterface $em): Response
    {
        $avis = $em->getRepository(Avis::class)->find($id);
        if (!$avis) {
            throw $this->createNotFoundException();
        }

        $avis->setStatut('refusé');
        $em->flush();
        $this->addFlash('success', 'Avis refusé.');

        return $this->redirectToRoute('app_employe_avis');
    }
}
