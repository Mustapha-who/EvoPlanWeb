<?php

namespace App\Form;

use App\Entity\Publication;
use App\Entity\Event;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PublicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu',
                'required' => true,
            ])
            ->add('imagePath', FileType::class, [
                'label' => 'Image',
                'required' => false,
                'mapped' => false,
            ])
            ->add('datePublication', DateTimeType::class, [
                'label' => 'Date de publication',
                'widget' => 'single_text',
            ])
            ->add('plateforme', ChoiceType::class, [
                'label' => 'Plateforme',
                'choices' => [
                    'Facebook' => 'facebook',
                    'Instagram' => 'instagram',
                    'Twitter' => 'twitter',
                ],
            ])
            ->add('event', EntityType::class, [
                'class' => Event::class,
                'label' => 'Événement',
                'choice_label' => 'nom',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Publication::class,
        ]);
    }
}