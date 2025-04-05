<?php

namespace App\Form;

use App\Entity\ReservationSession;
use App\Entity\Session;
use App\Entity\UserModule\Client;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationSessionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id_session', EntityType::class, [
                'class' => Session::class,
                'choice_label' => 'id',
            ])
            ->add('participant', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReservationSession::class,
        ]);
    }
}
