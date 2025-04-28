<?php
// src/Form/EventPlannerType.php

namespace App\Form\UserModule;

use App\Entity\UserModule\EventPlanner;
use App\Entity\UserModule\EventPlannerModule;
use App\Entity\UserModule\UserDTO;
use App\Service\UserModule\ValidationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EventPlannerUpdateFormType extends AbstractType
{
    private ValidationService $validationService;

    public function __construct(ValidationService $validationService)
    {
        $this->validationService = $validationService;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'attr' => ['data-verify-target' => 'email'],
                'required' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Email is required']),
                    new Callback([$this, 'validateEmail']),
                ],
            ])
            ->add('specialization', TextType::class, [
                'required' => false,
            ])
            ->add('assignedModule', TextType::class, [
                'required' => false,
                'disabled' => true, // Make the field non-editable
                'data' => EventPlannerModule::getValues($options['data']->getAssignedModule()) // Set the value as a string
            ])
            ->add('originalEmail', HiddenType::class, [
                'mapped' => false,
                'data' => $options['data']->getEmail(),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserDTO::class,
        ]);
    }

    public function validateEmail($email, ExecutionContextInterface $context): void
    {
        $form = $context->getRoot();
        $originalEmail = $form->get('originalEmail')->getData();

        if ($email !== $originalEmail) {
            if (!$this->validationService->isValidEmail($email)) {
                $context->buildViolation('Invalid email address')
                    ->atPath('email')
                    ->addViolation();
            }

            if ($this->validationService->isEmailExists($email)) {
                $context->buildViolation('Email is already in use')
                    ->atPath('email')
                    ->addViolation();
            }
        }
    }
}