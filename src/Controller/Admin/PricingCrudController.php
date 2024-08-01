<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Entity\Pricing;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CurrencyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;

class PricingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Pricing::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular("Tarification")
            ->setEntityLabelInPlural("Tarifications")
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('Volume')->setRequired(true);
        yield IdField::new('id', 'ID')->onlyOnDetail();
        yield NumberField::new('cost','Coût')->onlyOnForms();
        yield CurrencyField::new('currency','Devise')->onlyOnForms();
        yield MoneyField::new('cost', 'Coût')
            ->setCurrencyPropertyPath('currency')
            ->setNumDecimals(2)
            ->setStoredAsCents(false)
            ->hideOnForm()
        ;
        yield ChoiceField::new('method','Méthode')
                ->setChoices(Order::getAvailableMethods())
                ;
        yield BooleanField::new('active');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::DETAIL, 'ROLE_PRICING_VIEW')
            ->setPermission(Action::NEW, 'ROLE_PRICING_CREATE')
            ->setPermission(Action::EDIT, 'ROLE_PRICING_EDIT')
            ->setPermission(Action::DELETE, 'ROLE_PRICING_DELETE')
        ;
    }

}
