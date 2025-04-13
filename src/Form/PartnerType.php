<?php

namespace App\Form;

use App\Entity\Partner;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class PartnerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type_partner', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Speaker' => 'speaker',
                    'Sponsor' => 'sponsor'
                ],
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'onchange' => 'validateType(this)',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Type is required']),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'oninput' => 'validateEmail(this)',
                    'data-error-required' => 'Email is required',
                    'data-error-format' => 'Please enter a valid email address'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Email is required']),
                    new Email(['message' => 'The email {{ value }} is not a valid email.']),
                ],
            ])
            ->add('phone_Number', TelType::class, [
                'label' => 'Phone Number',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => '8',
                    'oninput' => 'validatePhone(this)',
                    'data-error-required' => 'Phone number is required',
                    'data-error-length' => 'Phone number must be exactly 8 digits',
                    'data-error-format' => 'Phone number must contain only digits'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Phone number is required']),
                    new Length(['max' => 8, 'maxMessage' => 'Phone number cannot be longer than {{ limit }} digits']),
                    new Regex(['pattern' => '/^\d+$/', 'message' => 'Phone number must contain only digits']),
                ],
            ])
            ->add('logoFile', FileType::class, [
                'label' => 'Logo',
                'mapped' => false,
                'required' => $options['is_edit'] ? false : true,
                'constraints' => array_filter([
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG or PNG)',
                    ]),
                    $options['is_edit'] ? null : new NotBlank(['message' => 'Logo is required']),
                ]),
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*',
                    'onchange' => 'validateLogo(this)',
                    'data-error-type' => 'Please upload a valid image (JPEG or PNG)',
                    'data-error-size' => 'File size must not exceed 1MB'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Partner::class,
            'is_edit' => false,
        ]);
    }
}
