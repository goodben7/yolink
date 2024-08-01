<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Exception\OrderException;
use App\Form\NewOrderType;
use Psr\Log\LoggerInterface;
use App\Manager\OrderManager;
use App\Model\NewOrderCommand;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\InsufficientEntityPermissionException;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class OrderCrudController extends AbstractCrudController
{
    const ACTION_INIT = 'init';
    const ACTION_VALIDATE = 'validate';
    const ACTION_PROCESS = 'process';

    public function __construct(
        private OrderManager $manager,
        private LoggerInterface $logger,
    )
    {
        
    }
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular("Commande")
            ->setEntityLabelInPlural("Commandes")
            ->overrideTemplate('crud/detail', 'admin/order/detail.html.twig')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield DateTimeField::new('date', 'Date')->hideOnForm();
        yield AssociationField::new('team', 'Equipe')
                ->setRequired(true)
                ->setPermission('ROLE_ADMIN')
                ;
        yield IntegerField::new('Volume')->hideOnForm();
        yield ChoiceField::new('status')
            ->setChoices(Order::getAvailableStatus())
            ->setCustomOption(ChoiceField::OPTION_RENDER_AS_BADGES, true)
            ->renderAsBadges([
                Order::STATUS_ACCEPTED => 'success',
                Order::STATUS_PENDING => 'info',
                Order::STATUS_REFUSED => 'danger',
                Order::STATUS_WAITING => 'warning',
            ])
            ;
        yield MoneyField::new('cost','Coût')
            ->setCurrencyPropertyPath('currency')
            ->setNumDecimals(2)
            ->setStoredAsCents(false)
        ;
        yield ChoiceField::new('method','Méthode')
            ->setChoices(Order::getAvailableMethods())
        ;
        
        yield TextField::new('txReference','Référence')->onlyOnDetail();
        yield TextField::new('issuer','Emetteur')->onlyOnDetail();
        
        
        yield DateTimeField::new('closedAt', 'Clôturé le')->onlyOnDetail();
        yield TextareaField::new('note')->onlyOnDetail();
        yield BooleanField::new('validated','Validé ?')
            ->renderAsSwitch(false)
            ->hideOnForm()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $init = Action::new(self::ACTION_INIT, 'Initier une nouvelle commande', 'fa fa-file')
            ->displayAsLink()
            ->setCssClass("btn btn-primary")
            ->linkToCrudAction('init')
            ->createAsGlobalAction()
        ;

        $validate = Action::new(self::ACTION_VALIDATE, 'Valider', 'fa fa-check-circle')
            ->displayAsLink()
            ->setCssClass("btn btn-secondary text-success")
            ->linkToUrl('#')
            ->setHtmlAttributes([
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#modal-validate',
            ])
            ->displayIf(fn (Order $entity) => $entity->canBeValidated())
        ;

        $process = Action::new(self::ACTION_PROCESS, 'Payer', 'fa fa-money')
            ->displayAsLink()
            ->setCssClass("btn btn-secondary text-success")
            ->linkToUrl('#')
            ->setHtmlAttributes([
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#modal-process',
            ])
            ->displayIf(fn (Order $entity) => $entity->isPending())
        ;

        return $actions
            ->disable(Action::EDIT, Action::DELETE, Action::NEW)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $init)
            ->add(Crud::PAGE_DETAIL, $validate)
            ->add(Crud::PAGE_DETAIL, $process)
            ->setPermission(Action::DETAIL, 'ROLE_ORDER_VIEW')
            ->setPermission(self::ACTION_INIT, 'ROLE_ORDER_CREATE')
            ->setPermission(self::ACTION_VALIDATE, 'ROLE_ORDER_VALIDATE')
            ->setPermission(self::ACTION_PROCESS, 'ROLE_ORDER_PROCESS')
            ->setPermission(Action::EDIT, 'ROLE_ORDER_EDIT')
        ;
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

    public function init(AdminContext $context) {
        $event = new BeforeCrudActionEvent($context);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => self::ACTION_INIT, 'entity' => null])) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        /** @var \App\Entity\User */
        $user = $this->getUser();

        $model = new NewOrderCommand();
        $model->team = $user->getTeam();

        $form = $this
                    ->createForm(NewOrderType::class, $model)
                    ;
        
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entity = $this->manager->create($model);

                $this->addFlash('success', "la commande a été enregstré avec succès !");
                $url = $this->container->get(AdminUrlGenerator::class)
                    ->setAction(Action::DETAIL)
                    ->setEntityId($entity->getId())
                    ->generateUrl();

                return $this->redirect($url);
            }
            catch (\Exception $e) {
                $this->logger->critical($e->getMessage(), ['exception' => $e]);
                $this->addFlash('danger', "une erreur s'est produite lors de l'exécution de votre requête.");
            }

            $url = $this->container->get(AdminUrlGenerator::class)
                ->setAction(Action::INDEX)
                ->generateUrl();

            return $this->redirect($url);
        }

        $responseParameters = $this->configureResponseParameters(KeyValueStore::new([
            'templatePath' => 'admin/order/init.html.twig',
            'new_form' => $form,
        ]));

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }

    public function validate(AdminContext $context) {
        $event = new BeforeCrudActionEvent($context);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => self::ACTION_VALIDATE, 'entity' => $context->getEntity()])) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        $csrfToken = $context->getRequest()->request->get('token');
        if ($this->container->has('security.csrf.token_manager') && !$this->isCsrfTokenValid('ea-validate', $csrfToken)) {
            return $this->redirectToRoute($context->getDashboardRouteName());
        }

        $entityInstance = $context->getEntity()->getInstance();

        try {
            $entityInstance = $this->manager->validate($entityInstance);
            $this->addFlash('success', 'Commande validée !');
        }
        catch(OrderException $ex) {
            $this->addFlash('danger', $ex->getMessage());
        }

        $responseParameters = $this->configureResponseParameters(KeyValueStore::new([
            'entity' => $context->getEntity(),
        ]));

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        $url = $this->container->get(AdminUrlGenerator::class)
                    ->setAction(Action::DETAIL)
                    ->setEntityId($entityInstance->getId())
                    ->generateUrl();

        return $this->redirect($url);
    
    }

    public function process(AdminContext $context) {
        $event = new BeforeCrudActionEvent($context);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => self::ACTION_PROCESS, 'entity' => $context->getEntity()])) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        $csrfToken = $context->getRequest()->request->get('token');
        if ($this->container->has('security.csrf.token_manager') && !$this->isCsrfTokenValid('ea-process', $csrfToken)) {
            return $this->redirectToRoute($context->getDashboardRouteName());
        }

        $entityInstance = $context->getEntity()->getInstance();

        try {
            $entityInstance = $this->manager->process($entityInstance);
            if ($entityInstance->getStatus() === Order::STATUS_ACCEPTED) {
                $this->addFlash('success', 'Commande acceptée. En attente de validation.');
            }
            elseif ($entityInstance->getStatus() === Order::STATUS_WAITING) {
                $this->addFlash('warning', 'Commande en cours...');
            }
            else {
                $this->addFlash('danger', 'Commande en refusé.');
            }

        }
        catch(OrderException $ex) {
            $this->addFlash('danger', $ex->getMessage());
        }

        $responseParameters = $this->configureResponseParameters(KeyValueStore::new([
            'entity' => $context->getEntity(),
        ]));

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        $url = $this->container->get(AdminUrlGenerator::class)
                    ->setAction(Action::DETAIL)
                    ->setEntityId($entityInstance->getId())
                    ->generateUrl();

        return $this->redirect($url);
    
    }
}
