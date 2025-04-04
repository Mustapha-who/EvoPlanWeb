<?php

namespace App\Form;

use App\Entity\Session;
use App\Entity\Workshop;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SessionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', null, [
                'widget' => 'single_text',
            ])
            ->add('dateheuredeb', null, [
                'widget' => 'single_text',
            ])
            ->add('dateheurefin', null, [
                'widget' => 'single_text',
            ])
            ->add('participant_count')
            ->add('capacity')
            ->add('id_workshop', EntityType::class, [
                'class' => Workshop::class,
                'choice_label' => 'title',
                'disabled' => $options['data']->getIdWorkshop() !== null,
                'required' => true,
                'label' => 'Workshop'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Session::class,
        ]);
    }
}
