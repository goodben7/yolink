<?php

namespace App\Form;

use App\Entity\Pricing;
use App\Model\NewOrderCommand;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pricing', EntityType::class, [
                'class' => Pricing::class,
                'label' => 'Tarifiction',
                'required' => true,
                'expanded' => true,
            ])
            ->add('qty', IntegerType::class, [
                'label' => 'Quantité',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NewOrderCommand::class,
        ]);
    }
}
