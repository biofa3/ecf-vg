<?php

namespace App\Controller;

use App\Document\CommandeStat;
use App\Entity\Avis;
use App\Entity\Commande;
use App\Entity\CommandeHistorique;
use App\Entity\Menu;
use App\Entity\Role;
use App\Entity\Utilisateur;
use App\Form\EmployeFormType;
use App\Form\MenuFormType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
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
    public function nouveauEmploye(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher, MailerInterface $mailer): Response
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

            $emailBody = $this->renderView('emails/compte_employe_cree.html.twig', ['employe' => $employe]);
            $email = (new Email())
                ->from('noreply@vite-gourmand.fr')
                ->to($employe->getEmail())
                ->subject('Votre compte Vite & Gourmand a été créé')
                ->html($emailBody);
            $mailer->send($email);

            $this->addFlash('success', 'Compte employé créé avec succès. Un email a été envoyé.');
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

    #[Route('/employe/{id}/toggle', name: 'app_admin_employe_toggle')]
    public function toggleEmploye(int $id, EntityManagerInterface $em): Response
    {
        $employe = $em->getRepository(Utilisateur::class)->find($id);
        if (!$employe) throw $this->createNotFoundException();

        $employe->setActif(!$employe->isActif());
        $em->flush();

        $statut = $employe->isActif() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Compte {$statut} avec succès.");
        return $this->redirectToRoute('app_espace_admin');
    }

    #[Route('/commandes', name: 'app_admin_commandes')]
    public function commandes(EntityManagerInterface $em): Response
    {
        $commandes = $em->getRepository(Commande::class)->findBy([], ['date_commande' => 'DESC']);

        return $this->render('espace_admin/commandes.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/commande/{id}/statut', name: 'app_admin_commande_statut', methods: ['POST'])]
    public function changerStatutCommande(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $commande = $em->getRepository(Commande::class)->find($id);
        if (!$commande) throw $this->createNotFoundException();

        $statut = $request->request->get('statut');
        $statutsValides = ['en attente', 'acceptée', 'en préparation', 'en cours de livraison', 'en attente du retour de matériel', 'terminée', 'annulée'];
        if (in_array($statut, $statutsValides)) {
            $commande->setStatut($statut);

            $historique = new CommandeHistorique();
            $historique->setCommande($commande);
            $historique->setStatut($statut);
            $historique->setDateChangement(new \DateTime());
            $em->persist($historique);

            $em->flush();
            $this->addFlash('success', 'Statut mis à jour.');
        }

        return $this->redirectToRoute('app_admin_commandes');
    }

    #[Route('/statistiques', name: 'app_admin_stats')]
    public function statistiques(EntityManagerInterface $em, DocumentManager $dm, Request $request): Response
    {
        // Synchroniser MySQL → MongoDB
        $commandes = $em->getRepository(Commande::class)->findAll();
        $menus = $em->getRepository(Menu::class)->findAll();

        // Vider la collection MongoDB puis recalculer
        $dm->getDocumentCollection(CommandeStat::class)->drop();
        $dm->clear();

        $statsMap = [];
        foreach ($menus as $menu) {
            $stat = new CommandeStat($menu->getId(), $menu->getTitre());
            $statsMap[$menu->getId()] = $stat;
            $dm->persist($stat);
        }

        foreach ($commandes as $commande) {
            if ($commande->getStatut() === 'annulée') continue;
            $menuId = $commande->getMenu()?->getId();
            if ($menuId && isset($statsMap[$menuId])) {
                $montant = ($commande->getPrixMenu() ?? 0) + ($commande->getPrixLivraison() ?? 0);
                $statsMap[$menuId]->incrementer($montant);
            }
        }
        $dm->flush();

        // Filtres pour le CA
        $menuFiltre = $request->query->get('menu');
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');

        // CA par menu avec filtres (depuis MySQL)
        $qb = $em->createQueryBuilder();
        $qb->select('m.id, m.titre, COUNT(c.id) as nb_commandes, SUM(c.prix_menu + c.prix_livraison) as ca')
            ->from(Commande::class, 'c')
            ->join('c.menu', 'm')
            ->where('c.statut != :annulee')
            ->setParameter('annulee', 'annulée')
            ->groupBy('m.id');

        if ($menuFiltre) {
            $qb->andWhere('m.id = :menuId')->setParameter('menuId', (int) $menuFiltre);
        }
        if ($dateDebut) {
            $qb->andWhere('c.date_prestation >= :debut')->setParameter('debut', new \DateTime($dateDebut));
        }
        if ($dateFin) {
            $qb->andWhere('c.date_prestation <= :fin')->setParameter('fin', new \DateTime($dateFin));
        }

        $caParMenu = $qb->getQuery()->getResult();

        // Stats depuis MongoDB pour le graphique
        $statsMongoRaw = $dm->getRepository(CommandeStat::class)->findAll();
        $statsMongo = array_map(fn($s) => [
            'titre' => $s->getMenuTitre(),
            'nb' => $s->getNombreCommandes(),
            'ca' => $s->getChiffreAffaires(),
        ], $statsMongoRaw);

        return $this->render('espace_admin/statistiques.html.twig', [
            'stats_mongo' => $statsMongo,
            'ca_par_menu' => $caParMenu,
            'menus' => $menus,
            'menu_filtre' => $menuFiltre,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
        ]);
    }

    #[Route('/menus', name: 'app_admin_menus')]
    public function menus(EntityManagerInterface $em): Response
    {
        $menus = $em->getRepository(Menu::class)->findAll();

        return $this->render('espace_admin/menus.html.twig', [
            'menus' => $menus,
        ]);
    }

    #[Route('/menu/{id}/modifier', name: 'app_admin_menu_edit')]
    public function editMenu(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $menu = $em->getRepository(Menu::class)->find($id);
        if (!$menu) throw $this->createNotFoundException();

        $form = $this->createForm(MenuFormType::class, $menu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Menu "' . $menu->getTitre() . '" mis à jour.');
            return $this->redirectToRoute('app_admin_menus');
        }

        return $this->render('espace_admin/menu_edit.html.twig', [
            'form' => $form->createView(),
            'menu' => $menu,
        ]);
    }

    #[Route('/avis', name: 'app_admin_avis')]
    public function avis(EntityManagerInterface $em): Response
    {
        $avis = $em->getRepository(Avis::class)->findAll();

        return $this->render('espace_admin/avis.html.twig', [
            'avis' => $avis,
        ]);
    }

    #[Route('/avis/{id}/valider', name: 'app_admin_avis_valider')]
    public function validerAvis(int $id, EntityManagerInterface $em): Response
    {
        $avis = $em->getRepository(Avis::class)->find($id);
        if (!$avis) throw $this->createNotFoundException();
        $avis->setStatut('publié');
        $em->flush();
        $this->addFlash('success', 'Avis publié.');
        return $this->redirectToRoute('app_admin_avis');
    }

    #[Route('/avis/{id}/refuser', name: 'app_admin_avis_refuser')]
    public function refuserAvis(int $id, EntityManagerInterface $em): Response
    {
        $avis = $em->getRepository(Avis::class)->find($id);
        if (!$avis) throw $this->createNotFoundException();
        $avis->setStatut('refusé');
        $em->flush();
        $this->addFlash('success', 'Avis refusé.');
        return $this->redirectToRoute('app_admin_avis');
    }
}
