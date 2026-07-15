<?php

namespace App\Controller;

use App\Entity\Avis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EntityManagerInterface $em): Response
    {
        $avis = $em->getRepository(Avis::class)->findBy(
            ['statut' => 'publié'],
            ['id' => 'DESC'],
            6
        );

        return $this->render('home/index.html.twig', [
            'avis' => $avis,
        ]);
    }
}
