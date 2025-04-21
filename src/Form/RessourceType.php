<?php

// src/Form/RessourceType.php

namespace App\Form;

use App\Entity\Ressource;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class RessourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la ressource',
                'attr' => [
                    'placeholder' => 'Entrez le nom de la ressource',
                    'class' => 'form-control'
                ],
                'required' => true
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de ressource',
                'choices' => [
                    'Équipement' => 'equipement',
                    'Venue' => 'venue'
                ],
                'placeholder' => 'Sélectionnez un type',
                'attr' => ['class' => 'form-select'],
                'required' => true
            ])
            ->add('availability', CheckboxType::class, [
                'label' => 'Disponible',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ressource::class,
        ]);
    }
}