<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Manager\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class UserCrudController extends AbstractCrudController
{
    public function __construct(private UserManager $manager)
    {
        
    }
    
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular("Utilisateur")
            ->setEntityLabelInPlural("Utilisateurs")
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')->hideOnForm(),
            TextField::new('email','Adresse mail'),
            TextField::new('displayName', 'Nom')->setRequired(false),
            TextField::new('plainPassword', 'Mot de passe')->onlyOnForms(), 
            AssociationField::new('team', 'Equipe'),
            ChoiceField::new('roles', 'Roles')->allowMultipleChoices(true)->setChoices(User::getAvailablesRoles()),
            BooleanField::new('isActivated', 'Est activé'),
            DateTimeField::new('createdAt', 'Date de création')->onlyOnDetail(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::DETAIL, 'ROLE_USER_VIEW')
            ->setPermission(Action::NEW, 'ROLE_USER_CREATE')
            ->setPermission(Action::EDIT, 'ROLE_USER_EDIT')
        ;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->manager->create($entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->manager->update($entityInstance);
    }
}
