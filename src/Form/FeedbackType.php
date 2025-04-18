<?php

namespace App\Form;

use App\Entity\UserModule\Client;
use App\Entity\Feedback;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;

class FeedbackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('comments', TextareaType::class, [
                'label' => 'Vos commentaires *',
                'label_attr' => ['class' => 'form-label'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir votre commentaire',
                    ]),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'Votre commentaire doit contenir au moins {{ limit }} caractères',
                        'max' => 1000,
                        'maxMessage' => 'Votre commentaire ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-control',
                    'placeholder' => 'Partagez votre expérience avec nous...',
                    'data-min-length' => 10,
                    'data-max-length' => 1000,
                ],
                'row_attr' => ['class' => 'mb-4'],
            ])
            ->add('rating', IntegerType::class, [
                'label' => 'Note (1-5) *',
                'label_attr' => ['class' => 'form-label'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez attribuer une note',
                    ]),
                    new Range([
                        'min' => 1,
                        'max' => 5,
                        'notInRangeMessage' => 'La note doit être comprise entre {{ min }} et {{ max }}',
                    ]),
                ],
                'attr' => [
                    'min' => 1,
                    'max' => 5,
                    'class' => 'form-control rating-input',
                    'placeholder' => '1-5',
                ],
                'row_attr' => ['class' => 'mb-4'],
            ])
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'id',
                'required' => false,
                'label' => 'Client associé',
                'attr' => ['class' => 'form-select'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Feedback::class,
            'attr' => ['class' => 'feedback-form'],
        ]);
    }
}