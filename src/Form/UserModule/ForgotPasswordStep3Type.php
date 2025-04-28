<?php

namespace App\Form\UserModule;

use App\Service\UserModule\ValidationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ForgotPasswordStep3Type extends AbstractType
{
    private ValidationService $validationService;

    public function __construct(ValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', PasswordType::class, [
                'label' => 'New Password:',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Password is required.']),
                    new Assert\Length([
                        'min' => 8,
                        'minMessage' => 'Password must be at least {{ limit }} characters long.',
                    ]),
                    new Callback([$this, 'validatePassword']),
                ],
            ])
            ->add('confirm_password', PasswordType::class, [
                'label' => 'Confirm Password:',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Please confirm your password.']),
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
                ->atPath('confirm_password')
                ->addViolation();
        }
    }
}