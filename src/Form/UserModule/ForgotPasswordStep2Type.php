<?php

namespace App\Form\UserModule;

use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ForgotPasswordStep2Type extends AbstractType
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('code', TextType::class, [
            'label' => 'Enter the verification code:',
            'required' => true,
            'constraints' => [
                new Assert\NotBlank(['message' => 'Verification code is required.']),
                new Assert\Callback(function ($code, $context) use ($options) {
                    $email = $options['email'] ?? null; // Ensure email is passed to the options
                    if (!$email) {
                        $this->logger->error('Email is missing for code validation.');
                        $context->buildViolation('Email is required for validation.')
                            ->addViolation();
                        return;
                    }

                    $cache = new FilesystemAdapter();
                    $sanitizedEmail = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $email);
                    $cacheKey = "forgot_password_$sanitizedEmail";

                    // Retrieve the cached code
                    $cachedCode = $cache->getItem($cacheKey)->get();

                    // Log for debugging
                    $this->logger->info("Submitted Code: $code");
                    $this->logger->info("Cached Code: " . ($cachedCode ?? 'null'));
                    $this->logger->info("Cache Key: $cacheKey");

                    // Validate the code
                    if (!$cachedCode || (string)$cachedCode !== (string)$code) {
                        $this->logger->error('Invalid verification code.');
                        $context->buildViolation('Invalid verification code.')
                            ->addViolation();
                    } else {
                        $this->logger->info('Verification code is valid.');
                    }
                }),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {

        $resolver->setDefaults([
            'code_is_valid_callback' => null,
            'email' => null, // Define the email option
        ]);
    }
}