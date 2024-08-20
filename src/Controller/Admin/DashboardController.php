<?php

namespace App\Controller\Admin;

use App\Entity\Campaign;
use App\Entity\Contact;
use App\Entity\Order;
use App\Entity\Pricing;
use App\Entity\Team;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'dashboard')]
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        if ($this->isGranted('ROLE_CUSTOMER')) {
            return $this->redirect($adminUrlGenerator->setController(CampaignCrudController::class)->generateUrl());
        }

        return $this->redirect($adminUrlGenerator->setController(UserCrudController::class)->generateUrl());
        //return $this->render('admin/page/dashboard.html.twig', []);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('YoLink');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Administration')->setPermission('ROLE_ADMIN_MENU');
        yield MenuItem::linkToCrud('Equipes', 'fa-solid fa-people-group', Team::class)->setPermission('ROLE_TEAM_MENU');
        yield MenuItem::linkToCrud('Utilisateurs','fa-solid fa-user', User::class)->setPermission('ROLE_USER_MENU');
        yield MenuItem::linkToCrud('Tarifications','fa-solid fa-money-check-dollar', Pricing::class)->setPermission('ROLE_PRICING_MENU');

        yield MenuItem::section('Messagerie')->setPermission('ROLE_MSG_MENU');
        yield MenuItem::linkToCrud('Campagnes','fa-solid fa-envelopes-bulk',Campaign::class)->setPermission('ROLE_CAMPAIGN_MENU'); 
        yield MenuItem::linkToCrud('Contacts','fa-solid fa-address-book', Contact::class)->setPermission('ROLE_CONTACT_MENU');

        yield MenuItem::section('Finances & Compta.')->setPermission('ROLE_FINANCE_MENU');
        yield MenuItem::linkToCrud('Commandes','fa-solid fa-cart-shopping', Order::class)->setPermission('ROLE_ORDER_MENU');
    }
}
