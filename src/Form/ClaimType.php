<?php

namespace App\Form;

use App\Entity\Claim;
use App\Entity\UserModule\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ClaimType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Description de la réclamation',
                'required' => true,
                'attr' => ['rows' => 5, 'class' => 'form-control'],
            ])
            ->add('claimType', ChoiceType::class, [
                'label' => 'Type de réclamation',
                'choices' => [
                    'Technique' => 'technical',
                    'Service client' => 'customer_service',
                    'Livraison' => 'delivery',
                    'Facturation' => 'billing',
                    'Autre' => 'other',
                ],
                'required' => true,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'phoneNumber',
                'label' => 'Client',
                'required' => true,
                'placeholder' => 'Sélectionner un client',
                'attr' => ['class' => 'form-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Claim::class,
        ]);
    }
}