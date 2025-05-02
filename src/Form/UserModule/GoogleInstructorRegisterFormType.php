<?php

namespace App\Form\UserModule;

use App\Service\UserModule\ValidationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class GoogleInstructorRegisterFormType extends AbstractType
{
    private ValidationService $validationService;

    public function __construct(ValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
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
            ->add('certificate', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Certificate is required']),
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
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'email' => null, // Pass the email as an option
        ]);
    }
}