<?php

namespace App\Controller\Admin;

use App\Entity\Campaign;
use App\Exception\CampaignConfigurationException;
use App\Exception\CampaignException;
use App\Form\CampaignType;
use App\Model\NewCampaignModel;
use App\Manager\CampaignManager;
use App\Repository\ContactRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\InsufficientEntityPermissionException;
use Psr\Log\LoggerInterface;

class CampaignCrudController extends AbstractCrudController
{
    const ACTION_SEND = 'send';

    public function __construct(
        private ContactRepository $contactRepository,
        private CampaignManager $manager,
        private LoggerInterface $logger,
    )
    {
        
    }

    public static function getEntityFqcn(): string
    {
        return Campaign::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular("campagne")
            ->setEntityLabelInPlural("campagnes")
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield DateTimeField::new('createdAt', 'Date')->hideOnForm();
        yield TextareaField::new('message', 'Message');
        yield AssociationField::new('team', 'Equipe')
            ->setPermission('ROLE_ADMIN');
        yield ChoiceField::new('status')
                ->setChoices(Campaign::getAvailablesStatus())
                ->setCustomOption(ChoiceField::OPTION_RENDER_AS_BADGES, true)
                ->renderAsBadges([
                    Campaign::STATUS_SENT => 'success',
                    Campaign::STATUS_PENDING => 'warning',
                    Campaign::STATUS_DRAFT => 'info',
                    Campaign::STATUS_FAILED => 'danger',
                ])
        ;
        yield DateTimeField::new('sentAt', 'Délivrée le')->onlyOnDetail();
        yield TextField::new('trackingId', 'Tracking ID')->onlyOnDetail();
        yield TextareaField::new('notes', 'Notes du fournissuer')->onlyOnDetail();
    }

    public function configureActions(Actions $actions): Actions
    {
        $send = Action::new(self::ACTION_SEND, 'Nouvelle campagne', 'fa fa-send')
            ->displayAsLink()
            ->setCssClass("btn btn-primary")
            ->linkToCrudAction('send')
            ->createAsGlobalAction()
        ;

        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $send)
            ->setPermission(Action::DETAIL, 'ROLE_CAMPAIGN_VIEW')
            ->setPermission(self::ACTION_SEND, 'ROLE_CAMPAIGN_SEND')
        ;
    }

    public function send(AdminContext $context) {
        $event = new BeforeCrudActionEvent($context);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => self::ACTION_SEND, 'entity' => null])) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        /** @var \App\Entity\User */
        $user = $this->getUser();
        $contacts = [];
        
        /** @var \App\Entity\Contact $contact */
        foreach ($this->contactRepository->getTeamContact($user->getTeam()) as $contact) {
            $contacts[$contact->getPhone()] = $contact;
        };
        /** @var \App\Entity\Contact $i */
        $contacts = array_map(fn ($i) => [ 'phone' => $i->getPhone(), 'label' => $i->getLabeledValue()] , array_values($contacts));

        $model = new NewCampaignModel();
        $form = $this->createForm(CampaignType::class, $model);
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $campaign = Campaign::fromModel($model);
                $campaign->setTeam($user->getTeam());
                $campaign = $this->manager->process($campaign);

                $this->addFlash('success', "Votre campagne a été lancée avec succès !");
                $url = $this->container->get(AdminUrlGenerator::class)
                    ->setAction(Action::DETAIL)
                    ->setEntityId($campaign->getId())
                    ->generateUrl();

                return $this->redirect($url);
            }
            catch (CampaignException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
            catch (CampaignConfigurationException $e) {
                $this->addFlash('danger', $e->getMessage());
                $this->logger->error($e->getMessage(), ['exception' => $e]);
            }
            catch (\Exception $e) {
                $this->addFlash('danger', "Une erreur s'est produite lors de l'exécution de votre requête.");
                $this->logger->critical($e->getMessage(), ['exception' => $e]);
            }
        }

        $responseParameters = $this->configureResponseParameters(KeyValueStore::new([
            'templatePath' => 'admin/campaign/send.html.twig',
            'new_form' => $form,
            'contacts' => json_encode($contacts),
        ]));

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->container->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }
}
