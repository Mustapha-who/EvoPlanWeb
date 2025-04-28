<?php

namespace App\Form\UserModule;

use App\Service\UserModule\ValidationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GoogleClientRegisterFormType extends AbstractType
{
    private ValidationService $validationService;

    public function __construct(ValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'First name is required']),
                ],
            ])
            ->add('lastName', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Last name is required']),
                ],
            ])
            ->add('email', EmailType::class, [
                'attr' => ['readonly' => true],
                'data' => $options['email'], // Pre-fill with Google email
            ])
            ->add('phoneNumber', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Phone number is required']),
                    new Regex([
                        'pattern' => '/^\d{8}$/',
                        'message' => 'Phone number must be exactly 8 digits',
                    ]),
                ],
            ])
            ->add('password', PasswordType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Password is required']),
                    new Callback([$this, 'validatePassword']),
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Password confirmation is required']),
                    new Callback([$this, 'validateConfirmPassword']),
                ],
            ]);
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'email' => null, // Pass the email as an option
        ]);
    }

    public function validatePassword($password, ExecutionContextInterface $context): void
    {
        if (!$this->validationService->isValidPassword($password)) {
            $context->buildViolation('Invalid password')
                ->atPath('password')
                ->addViolation();
        }
    }

    public function validateConfirmPassword($confirmPassword, ExecutionContextInterface $context): void
    {
        $form = $context->getRoot();
        $password = $form->get('password')->getData();

        if ($password !== $confirmPassword) {
            $context->buildViolation('Passwords do not match')
                ->atPath('confirmPassword')
                ->addViolation();
        }
    }



}