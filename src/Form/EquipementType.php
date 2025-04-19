<?php

namespace App\Form;

use App\Entity\Equipement;
use App\Entity\Ressource;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class EquipementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('equipementType', TextType::class, [
                'label' => "Type d'équipement",
                'attr' => [
                    'placeholder' => "Entrez le type d'équipement",
                    'class' => 'form-control'
                ],
                'required' => true
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantité',
                'attr' => [
                    'placeholder' => 'Entrez la quantité',
                    'class' => 'form-control',
                    'min' => 0
                ],
                'html5' => true,
                'required' => true
            ])
            ->add('ressource', EntityType::class, [
                'class' => Ressource::class,
                'choice_label' => 'name', // Afficher le nom plutôt que l'ID
                'label' => 'Ressource associée',
                'placeholder' => 'Sélectionnez une ressource',
                'attr' => ['class' => 'form-select'],
                'required' => true
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Equipement::class,
        ]);
    }
}