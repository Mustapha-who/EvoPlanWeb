<?php

namespace App\Form;

use App\Entity\Ressource;
use App\Entity\Venue;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VenueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'attr' => ['id' => 'venue_address'],
            ])
            ->add('capacity', IntegerType::class, [
                'label' => 'Capacité',
            ])
            ->add('ressource', EntityType::class, [
                'class' => Ressource::class,
                'choice_label' => 'name',
                'label' => 'Ressource associée',
                'placeholder' => 'Sélectionnez une ressource',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Venue::class,
        ]);
    }
}