<?php

namespace App\Controller\Admin;

use App\Entity\Contact;
use App\Exception\ContactImportationException;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\QueryBuilder;
use App\Manager\ContactManager;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Symfony\Component\Validator\Constraints\File;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\InsufficientEntityPermissionException;

class ContactCrudController extends AbstractCrudController
{

    const ACTION_IMPORT = 'import';

    public function __construct(
        private ContactManager $cm,
        private LoggerInterface $logger,
    )
    {
        
    }

    public static function getEntityFqcn(): string
    {
        return Contact::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular("Contact")
            ->setEntityLabelInPlural("Contacts")
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')->hideOnForm(),
            TelephoneField::new('phone', 'Numéro de Téléphone'),
            TextField::new('fullname', 'Nom du contact'),
            AssociationField::new('team', 'Equipe')
                ->setRequired(true)
                ->setPermission('ROLE_ADMIN'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $import = Action::new(self::ACTION_IMPORT, 'Importer des contacts', 'fa fa-download')
            ->displayAsLink()
            ->setCssClass("btn btn-default")
            ->linkToCrudAction('import')
            ->createAsGlobalAction()
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $import)
            ->setPermission(Action::DETAIL, 'ROLE_CONTACT_VIEW')
            ->setPermission(Action::NEW, 'ROLE_CONTACT_CREATE')
            ->setPermission(Action::EDIT, 'ROLE_CONTACT_EDIT')
            ->setPermission(Action::DELETE, 'ROLE_CONTACT_DELETE')
            ->setPermission(self::ACTION_IMPORT, 'ROLE_CONTACT_IMPORT')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('fullname', 'Nom du contact'))
            ->add(TextFilter::new('phone', 'Numéro de Téléphone'))
        ;
    }

    /**
     * @param Contact $contact
     */
    public function persistEntity(EntityManagerInterface $em, $contact): void
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_CUSTOMER')) {
            $contact->setTeam($user->getTeam());
            $em->persist($contact);
            $em->flush();
        }
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        /** @var \App\Entity\User */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_CUSTOMER')) {

            $qb
                ->andWhere("entity.team = :team")
                ->setParameter('team', $user->getTeam())
            ;
        }

        return $qb;
    }

    public function import(AdminContext $context) {
        $event = new BeforeCrudActionEvent($context);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => self::ACTION_IMPORT, 'entity' => null])) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        $form = $this
                    ->createFormBuilder()
                    ->add('file', FileType::class,[
                        'label' => 'Fichier',
                        'required' => true,
                    ])
                    ->getForm()
                    ;
        
        $form->handleRequest($context->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile */
            $file = $form->get('file')->getData();
            if ($file) {
                /** @var \App\Entity\User */
                $user = $this->getUser();

                $path = uniqid().'.'.$file->guessExtension();

                try {
                    $file = $file->move(sys_get_temp_dir(), $path);

                    $this->cm->import($user->getTeam(), $file->getRealPath(), $file->getMimeType());
                    $this->addFlash('success', "Vos contacts ont été importés avec succès !");
                }
                catch (ContactImportationException $e) {
                    $this->logger->error($e->getMessage(), ['exception' => $e]);
                    $this->addFlash('danger', $e->getMessage());
                }
                catch (\Exception $e) {
                    $this->logger->critical($e->getMessage(), ['exception' => $e]);
                    $this->addFlash('danger', "une erreur s'est produite lors du traitement de votre requête");
                }

                $url = $this->container->get(AdminUrlGenerator::class)
                    ->setAction(Action::INDEX)
                    ->generateUrl();

                return $this->redirect($url);
            }
        }

        $responseParameters = $this->configureResponseParameters(KeyValueStore::new([
            'templatePath' => 'admin/contact/import.html.twig',
            'new_form' => $form,
        ]));

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }
}
