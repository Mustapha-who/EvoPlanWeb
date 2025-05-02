<?php

namespace App\Form\UserModule;

use App\Service\UserModule\UserService;
use App\Service\UserModule\ValidationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UpdatePasswordFormType extends AbstractType
{
    private ValidationService $validationService;
    private UserService $userService;
    private LoggerInterface $logger;

    public function __construct(ValidationService $validationService, UserService $userService, LoggerInterface $logger)
    {
        $this->validationService = $validationService;
        $this->userService = $userService;
        $this->logger = $logger;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $userId = $options['user_id'];
        $this->logger->info('Building form with user_id: ' . $userId);

        $builder
            ->add('current_password', PasswordType::class, [
                'label' => 'Current Password',
                'attr' => [
                    'placeholder' => 'Enter current password',
                    'class' => 'form-control',
                    'required' => true,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Current password is required']),
                    new Callback([
                        'callback' => function ($value, ExecutionContextInterface $context) use ($userId) {
                            if (!$this->userService->isCurrentPassword($userId, $value)) {
                                $context->buildViolation('The current password is incorrect.')
                                    ->addViolation();
                            }
                        }
                    ]),
                ],
            ])
            ->add('new_password', PasswordType::class, [
                'label' => 'New Password',
                'attr' => [
                    'placeholder' => 'Enter new password',
                    'class' => 'form-control',
                    'required' => true,
                    'minlength' => 6,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'New password is required']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'New password must be at least {{ limit }} characters long',
                    ]),
                    new Callback([$this, 'validatePassword']),
                ],
            ])
            ->add('confirm_password', PasswordType::class, [
                'label' => 'Confirm Password',
                'attr' => [
                    'placeholder' => 'Confirm new password',
                    'class' => 'form-control',
                    'required' => true,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please confirm your new password']),
                    new Callback([$this, 'validateConfirmPassword']),
                ],
            ])
            ->add('_token', HiddenType::class, [
                'mapped' => false,
            ]);
    }
    public function validatePassword($password, ExecutionContextInterface $context): void
    {
        if (!$this->validationService->isValidPassword($password)) {
            $context->buildViolation('Invalid password')
                ->atPath('new_password')
                ->addViolation();
        }
    }

    public function validateConfirmPassword($confirmPassword, ExecutionContextInterface $context): void
    {
        $form = $context->getRoot();
        $password = $form->get('new_password')->getData();

        if ($password !== $confirmPassword) {
            $context->buildViolation('Passwords do not match')
                ->atPath('confirm_password')
                ->addViolation();
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'user_id' => null,
        ]);
        $resolver->setRequired('user_id');
    }
}