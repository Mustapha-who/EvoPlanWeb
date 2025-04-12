<?php

namespace App\Form;

use App\Entity\Partnership;
use App\Entity\Partner;
use App\Entity\Event;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Expression;

class PartnershipType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id_partner', EntityType::class, [
                'class' => Partner::class,
                'choice_label' => function (Partner $partner) {
                    return sprintf('%s - %s', $partner->getTypePartner(), $partner->getEmail());
                },
                'label' => 'Partner',
                'placeholder' => 'Select a partner...',
                'attr' => [
                    'class' => 'form-select',
                    'data-error-required' => 'Please select a partner'
                ],
                'required' => true,
                'constraints' => [
                    new NotNull(['message' => 'Please select a partner.'])
                ]
            ])
            ->add('id_event', EntityType::class, [
                'class' => Event::class,
                'choice_label' => 'nom',  // This will display the event name
                'label' => 'Event',
                'placeholder' => 'Select an event...',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('e')
                        ->orderBy('e.nom', 'ASC');
                },
                'attr' => [
                    'class' => 'form-select',
                    'data-error-required' => 'Please select an event'
                ],
                'required' => true,
                'constraints' => [
                    new NotNull(['message' => 'Please select an event.'])
                ]
            ])
            ->add('date_debut', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Start Date',
                'attr' => [
                    'class' => 'form-control',
                    'data-error-required' => 'Start date is required',
                    'data-error-format' => 'Please enter a valid date',
                    'data-error-future' => 'Start date must be today or in the future'
                ],
                'required' => true,
                'constraints' => [
                    new NotNull(['message' => 'Start date is required.']),
                    new Type([
                        'type' => '\DateTimeInterface',
                        'message' => 'Please enter a valid date.'
                    ]),
                    new GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'Start date must be today or in the future.'
                    ])
                ]
            ])
            ->add('date_fin', DateType::class, [
                'widget' => 'single_text',
                'label' => 'End Date (Optional)',
                'attr' => [
                    'class' => 'form-control',
                    'data-error-format' => 'Please enter a valid date',
                    'data-error-after' => 'End date must be after start date'
                ],
                'required' => false,
                'help' => 'Leave empty for indefinite partnerships',
                'constraints' => [
                    new Type([
                        'type' => '\DateTimeInterface',
                        'message' => 'Please enter a valid date.'
                    ]),
                    new Expression([
                        'expression' => 'value === null or (value > this.getParent().get("date_debut").getData())',
                        'message' => 'End date must be after start date.'
                    ])
                ]
            ])
            ->add('terms', TextareaType::class, [
                'label' => 'Terms and Conditions',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Enter partnership terms and conditions...',
                    'data-error-required' => 'Terms and conditions are required'
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Terms and conditions are required.'])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Partnership::class,
        ]);
    }
}
