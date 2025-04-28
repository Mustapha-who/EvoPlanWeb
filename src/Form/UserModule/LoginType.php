<?php

namespace App\Form\UserModule;

use App\Service\UserModule\ValidationService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class LoginType extends AbstractType
{
    private ValidationService $validationService;

    public function __construct(ValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('_username', TextType::class, [
                'label' => 'Email address',
                'attr' => [
                    'class' => 'form-control form-control-solid',
                    'placeholder' => '',
                    'aria-label' => 'Email Address',
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Email is required']),
                    new Callback([$this, 'validateEmail']),
                ],
            ])
            ->add('_password', PasswordType::class, [
                'label' => 'Password',
                'attr' => [
                    'class' => 'form-control form-control-solid',
                    'placeholder' => '',
                    'aria-label' => 'Password',
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Password is required']),
                ],
            ])
            ->add('_remember_me', CheckboxType::class, [
                'label' => 'Remember password',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ]);
    }

    public function validateEmail($email, ExecutionContextInterface $context): void
    {
        if (!$this->validationService->isValidEmail($email)) {
            $context->buildViolation('Invalid email address')
                ->atPath('_username')
                ->addViolation();
        }

        if (!$this->validationService->isEmailExists($email)) {
            $context->buildViolation('User does not exist')
                ->atPath('_username')
                ->addViolation();
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'authenticate',
        ]);
    }
}