<?php

namespace App\Form;

use App\Entity\Contract;
use App\Entity\Partner;
use App\Entity\Partnership;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Expression;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;

class ContractType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id_partnership', EntityType::class, [
                'class' => Partnership::class,
                'choice_label' => function (Partnership $partnership) {
                    $event = $partnership->getIdEvent();
                    $partner = $partnership->getIdPartner();
                    return sprintf('%s - %s', 
                        $event ? $event->getNom() : 'Unknown Event',
                        $partner ? $partner->getEmail() : 'Unknown Partner'
                    );
                },
                'label' => 'Partnership',
                'placeholder' => 'Select a partnership...',
                'attr' => [
                    'class' => 'form-select',
                    'data-error-required' => 'Partnership is required'
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Please select a partnership.'])
                ]
            ])
            ->add('id_partner', EntityType::class, [
                'class' => Partner::class,
                'choice_label' => function (Partner $partner) {
                    return sprintf('%s - %s', $partner->getTypePartner(), $partner->getEmail());
                },
                'label' => 'Partner',
                'placeholder' => 'Select a partner...',
                'attr' => [
                    'class' => 'form-select',
                    'data-error-required' => 'Partner is required'
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Please select a partner.'])
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
                    new NotBlank(['message' => 'Start date is required.']),
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
                'label' => 'End Date',
                'attr' => [
                    'class' => 'form-control',
                    'data-error-required' => 'End date is required',
                    'data-error-format' => 'Please enter a valid date',
                    'data-error-after' => 'End date must be after start date'
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'End date is required.']),
                    new Type([
                        'type' => '\DateTimeInterface',
                        'message' => 'Please enter a valid date.'
                    ]),
                    new Expression([
                        'expression' => 'value > this.getParent().get("date_debut").getData()',
                        'message' => 'End date must be after start date.'
                    ])
                ]
            ])
            ->add('terms', TextareaType::class, [
                'label' => 'Contract Terms',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'data-error-required' => 'Contract terms are required'
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Contract terms are required.'])
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Active' => 'active',
                    'Suspended' => 'suspended',
                    'Expired' => 'expired'
                ],
                'attr' => [
                    'class' => 'form-select',
                    'data-error-required' => 'Status is required'
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Status is required.'])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contract::class,
        ]);
    }
}
