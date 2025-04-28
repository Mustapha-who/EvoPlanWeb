<?php

namespace App\Form\UserModule;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ForgotPasswordStep1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label' => 'Enter your email:',
            'required' => true,
            'constraints' => [
                new Assert\NotBlank(['message' => 'Email is required.']),
                new Assert\Email(['message' => 'Please enter a valid email address.']),
                new Assert\Callback(function ($email, $context) use ($options) {
                    if (!$options['email_exists_callback']($email)) {
                        $context->buildViolation('Email does not exist.')
                            ->addViolation();
                    }
                }),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'email_exists_callback' => null, // Pass a callable to check if the email exists
        ]);
    }
}