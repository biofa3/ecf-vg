<?php

namespace App\Controller;

use App\Repository\MenuRepository;
use App\Repository\ThemeRepository;
use App\Repository\RegimeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MenuController extends AbstractController
{
    #[Route('/menus', name: 'app_menus')]
    public function index(
        MenuRepository $menuRepository,
        ThemeRepository $themeRepository,
        RegimeRepository $regimeRepository,
        Request $request
    ): Response {
        $themes = $themeRepository->findAll();
        $regimes = $regimeRepository->findAll();
        $menus = $menuRepository->findAll();

        return $this->render('menu/index.html.twig', [
            'menus' => $menus,
            'themes' => $themes,
            'regimes' => $regimes,
        ]);
    }

    #[Route('/menus/{id}', name: 'app_menu_detail')]
    public function detail(int $id, MenuRepository $menuRepository): Response
    {
        $menu = $menuRepository->find($id);

        if (!$menu) {
            throw $this->createNotFoundException('Menu non trouvé');
        }

        return $this->render('menu/detail.html.twig', [
            'menu' => $menu,
        ]);
    }
}