<?php

namespace App\Form;

use App\Model\NewCampaignModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CampaignType extends AbstractType
{
    public function __construct(
    )
    {
        
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('message', TextareaType::class, [
                'label' => 'Contenu',
                'required' => true,
                'attr' => [
                    'maxlength' => 160,

                ]
            ])
            ->add('sendToAllReceipient', CheckboxType::class, [
                'label' => 'Envoyer à tout mes contacts',
                'required' => false,
                'attr' => [
                    'x-on:change' => '$store.campaign.toggle()',
                    'x-data' => '',
                    'x-bind:value' => '$store.campaign.toYAll'
                ]
            ])
            ->add('recipients', HiddenType::class, [
                'required' => true,
                'attr' => [
                    'x-bind:value' => '$store.campaign.targetedRecipients',
                    'x-data' => ''
                ]
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NewCampaignModel::class,
        ]);
    }
}
