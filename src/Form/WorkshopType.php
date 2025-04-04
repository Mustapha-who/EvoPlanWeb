<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Workshop;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkshopType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('date', null, [
                'widget' => 'single_text',
            ])
            ->add('enddate', null, [
                'widget' => 'single_text',
            ])
            ->add('instructor')
            ->add('capacity')
            ->add('location')
            ->add('description')
            ->add('id_event', EntityType::class, [
                'class' => Event::class,
                'choice_label' => 'nom', 
                'placeholder' => 'Select an event',
                'required' => true,
                'label' => 'Event'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Workshop::class,
        ]);
    }
}
