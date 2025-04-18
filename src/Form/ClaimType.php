<?php
namespace App\Form;

use App\Entity\Claim;
use App\Entity\Client;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class ClaimType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $claimTypeChoices = [
            'Annulation de l\'événement' => 'EVENT_CANCELLATION',
            'Conflit de planning / emploi du temps' => 'SCHEDULE_CONFLICT',
            'Problème lié au lieu de l\'événement' => 'VENUE_ISSUE',
            'Problème avec l\'intervenant' => 'INSTRUCTOR_ISSUE',
            'Problème de paiement' => 'PAYMENT_PROBLEM',
            'Problème technique' => 'TECHNICAL_PROBLEM',
            'Qualité du service' => 'SERVICE_QUALITY',
            'Autre' => 'OTHER',
        ];

        $builder
            ->add('description', TextareaType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'La description ne peut pas être vide',
                    ]),
                    new Length([
                        'min' => 20,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères',
                        'max' => 1000,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Décrivez votre réclamation en détail...',
                    'class' => 'form-control',
                ],
                'label' => 'Description',
                'help' => '20 caractères minimum',
            ])
            ->add('claim_type', ChoiceType::class, [
                'choices' => $claimTypeChoices,
                'label' => 'Type de réclamation',
                'placeholder' => 'Choisissez un type',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner un type de réclamation',
                    ]),
                ],
                'empty_data' => '',
                'attr' => ['class' => 'form-select'],
                'label_attr' => ['class' => 'form-label'],
            ])
            /*->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => function(Client $client) {
                    return $client->getNomComplet(); // Supposons que vous avez une méthode getNomComplet()
                },
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner un client',
                    ]),
                ],
                'attr' => ['class' => 'form-select'],
                'label' => 'Client',
                'placeholder' => 'Sélectionnez un client',
            ])*/;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Claim::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'claim_item',
            'validation_groups' => ['Default'],
        ]);
    }
}