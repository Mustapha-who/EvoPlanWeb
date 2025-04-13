<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Enum\StatutReservation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EnumType; // Ajoutez c
class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Aucun champ nécessaire car tout est géré automatiquement
            // Vous pouvez ajouter d'autres champs si nécessaire
            ->add('statut', EnumType::class, [
                'class' => StatutReservation::class,
                'data' => StatutReservation::CONFIRMEE,
                'attr' => ['style' => 'display:none'],
                'label' => false
            ]);// Si vous voulez garder ce champ éditable

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}