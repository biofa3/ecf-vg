<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\CommandeHistorique;
use App\Entity\Menu;
use App\Entity\Avis;
use App\Form\MenuFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/employe')]
class EspaceEmployeController extends AbstractController
{
    private const STATUTS = [
        'en attente',
        'acceptée',
        'en préparation',
        'en cours de livraison',
        'en attente du retour de matériel',
        'terminée',
        'annulée',
    ];

    // Statuts qui nécessitent un contact préalable obligatoire avant modification
    private const STATUTS_CONTACT_REQUIS = ['annulée'];

    #[Route('', name: 'app_espace_employe')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $qb = $em->getRepository(Commande::class)->createQueryBuilder('c')
            ->leftJoin('c.utilisateur', 'u')
            ->leftJoin('c.menu', 'm')
            ->orderBy('c.date_commande', 'DESC');

        $filtreStatut = $request->query->get('statut', '');
        $filtreClient = $request->query->get('client', '');

        if ($filtreStatut) {
            $qb->andWhere('c.statut = :statut')->setParameter('statut', $filtreStatut);
        }
        if ($filtreClient) {
            $qb->andWhere('u.nom LIKE :client OR u.prenom LIKE :client OR u.email LIKE :client')
               ->setParameter('client', '%' . $filtreClient . '%');
        }

        $commandes = $qb->getQuery()->getResult();

        return $this->render('espace_employe/index.html.twig', [
            'commandes'     => $commandes,
            'statuts'       => self::STATUTS,
            'filtreStatut'  => $filtreStatut,
            'filtreClient'  => $filtreClient,
        ]);
    }

    #[Route('/commande/{id}/statut', name: 'app_employe_statut', methods: ['POST'])]
    public function changerStatut(int $id, Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $commande = $em->getRepository(Commande::class)->find($id);
        if (!$commande) {
            throw $this->createNotFoundException();
        }

        $nouveauStatut = $request->request->get('statut');
        $modeContact   = $request->request->get('mode_contact', '');
        $motif         = $request->request->get('motif', '');

        if (!in_array($nouveauStatut, self::STATUTS)) {
            $this->addFlash('danger', 'Statut invalide.');
            return $this->redirectToRoute('app_espace_employe');
        }

        // Contact obligatoire avant annulation
        if (in_array($nouveauStatut, self::STATUTS_CONTACT_REQUIS) && empty($motif)) {
            $this->addFlash('danger', 'Vous devez renseigner le mode de contact et le motif avant d\'annuler une commande.');
            return $this->redirectToRoute('app_espace_employe');
        }

        $ancienStatut = $commande->getStatut();
        $commande->setStatut($nouveauStatut);

        // Enregistrement dans l'historique
        $historique = new CommandeHistorique();
        $historique->setCommande($commande);
        $historique->setStatut($nouveauStatut);
        $historique->setDateChangement(new \DateTime());
        $commentaireHisto = '';
        if ($motif) {
            $commentaireHisto = 'Contact : ' . $modeContact . ' — Motif : ' . $motif;
        }
        $historique->setCommentaire($commentaireHisto ?: null);
        $em->persist($historique);

        $em->flush();

        // Emails spécifiques selon le nouveau statut
        $clientEmail = $commande->getUtilisateur()->getEmail();
        $clientPrenom = $commande->getUtilisateur()->getPrenom();

        if ($nouveauStatut === 'en attente du retour de matériel') {
            $html = $this->renderView('emails/retour_materiel.html.twig', ['commande' => $commande]);
            $mail = (new Email())
                ->from('noreply@vite-gourmand.fr')
                ->to($clientEmail)
                ->subject('Retour de matériel requis — ' . $commande->getNumeroCommande())
                ->html($html);
            $mailer->send($mail);
        }

        if ($nouveauStatut === 'terminée') {
            $html = $this->renderView('emails/commande_terminee.html.twig', ['commande' => $commande]);
            $mail = (new Email())
                ->from('noreply@vite-gourmand.fr')
                ->to($clientEmail)
                ->subject('Votre commande est terminée — Donnez votre avis !')
                ->html($html);
            $mailer->send($mail);
        }

        $this->addFlash('success', 'Statut mis à jour : "' . $nouveauStatut . '".');
        return $this->redirectToRoute('app_espace_employe');
    }

    #[Route('/menus', name: 'app_employe_menus')]
    public function menus(EntityManagerInterface $em): Response
    {
        $menus = $em->getRepository(Menu::class)->findAll();
        return $this->render('espace_employe/menus.html.twig', ['menus' => $menus]);
    }

    #[Route('/menu/{id}/modifier', name: 'app_employe_menu_edit')]
    public function editMenu(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $menu = $em->getRepository(Menu::class)->find($id);
        if (!$menu) throw $this->createNotFoundException();

        $form = $this->createForm(MenuFormType::class, $menu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Menu "' . $menu->getTitre() . '" mis à jour.');
            return $this->redirectToRoute('app_employe_menus');
        }

        return $this->render('espace_employe/menu_edit.html.twig', [
            'form' => $form->createView(),
            'menu' => $menu,
        ]);
    }

    #[Route('/menu/{id}/supprimer', name: 'app_employe_menu_delete', methods: ['POST'])]
    public function deleteMenu(int $id, EntityManagerInterface $em): Response
    {
        $menu = $em->getRepository(Menu::class)->find($id);
        if (!$menu) throw $this->createNotFoundException();

        $em->remove($menu);
        $em->flush();

        $this->addFlash('success', 'Menu supprimé.');
        return $this->redirectToRoute('app_employe_menus');
    }

    #[Route('/avis', name: 'app_employe_avis')]
    public function avis(EntityManagerInterface $em): Response
    {
        $avis = $em->getRepository(Avis::class)->findAll();
        return $this->render('espace_employe/avis.html.twig', ['avis' => $avis]);
    }

    #[Route('/avis/{id}/valider', name: 'app_employe_avis_valider', methods: ['POST'])]
    public function validerAvis(int $id, EntityManagerInterface $em): Response
    {
        $avis = $em->getRepository(Avis::class)->find($id);
        if (!$avis) throw $this->createNotFoundException();

        $avis->setStatut('publié');
        $em->flush();
        $this->addFlash('success', 'Avis publié.');
        return $this->redirectToRoute('app_employe_avis');
    }

    #[Route('/avis/{id}/refuser', name: 'app_employe_avis_refuser', methods: ['POST'])]
    public function refuserAvis(int $id, EntityManagerInterface $em): Response
    {
        $avis = $em->getRepository(Avis::class)->find($id);
        if (!$avis) throw $this->createNotFoundException();

        $avis->setStatut('refusé');
        $em->flush();
        $this->addFlash('success', 'Avis refusé.');
        return $this->redirectToRoute('app_employe_avis');
    }
}
