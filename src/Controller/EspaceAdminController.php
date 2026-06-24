<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Role;
use App\Entity\Utilisateur;
use App\Form\EmployeFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class EspaceAdminController extends AbstractController
{
    #[Route('', name: 'app_espace_admin')]
    public function index(EntityManagerInterface $em): Response
    {
        $commandes = $em->getRepository(Commande::class)->findAll();

        $ca = 0;
        $parStatut = [];
        foreach ($commandes as $commande) {
            if ($commande->getStatut() !== 'annulée') {
                $ca += $commande->getPrixMenu() + $commande->getPrixLivraison();
            }
            $statut = $commande->getStatut();
            $parStatut[$statut] = ($parStatut[$statut] ?? 0) + 1;
        }

        $employes = $em->getRepository(Utilisateur::class)->createQueryBuilder('u')
            ->join('u.role', 'r')
            ->where('r.libelle = :role')
            ->setParameter('role', 'EMPLOYE')
            ->getQuery()
            ->getResult();

        return $this->render('espace_admin/index.html.twig', [
            'ca' => $ca,
            'total_commandes' => count($commandes),
            'par_statut' => $parStatut,
            'employes' => $employes,
        ]);
    }

    #[Route('/employe/nouveau', name: 'app_admin_employe_new')]
    public function nouveauEmploye(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $employe = new Utilisateur();
        $form = $this->createForm(EmployeFormType::class, $employe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $employe->setPassword($hasher->hashPassword($employe, $plainPassword));

            $roleEmploye = $em->getRepository(Role::class)->findOneBy(['libelle' => 'EMPLOYE']);
            $employe->setRole($roleEmploye);

            $em->persist($employe);
            $em->flush();

            $this->addFlash('success', 'Compte employé créé avec succès.');
            return $this->redirectToRoute('app_espace_admin');
        }

        return $this->render('espace_admin/employe_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/employe/{id}/supprimer', name: 'app_admin_employe_supprimer')]
    public function supprimerEmploye(int $id, EntityManagerInterface $em): Response
    {
        $employe = $em->getRepository(Utilisateur::class)->find($id);
        if (!$employe) {
            throw $this->createNotFoundException();
        }

        $em->remove($employe);
        $em->flush();

        $this->addFlash('success', 'Compte employé supprimé.');
        return $this->redirectToRoute('app_espace_admin');
    }
}
