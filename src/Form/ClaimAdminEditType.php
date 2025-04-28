<?php

namespace App\Form;

use App\Entity\Claim;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotBlank;

class ClaimAdminEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('claimStatus', ChoiceType::class, [
                'choices' => [
                    'Nouveau' => 'new',
                    'En cours' => 'in_progress',
                    'Résolu' => 'resolved',
                ],
                'label' => 'Statut',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez sélectionner un statut']),
                ],
                'attr' => ['class' => 'form-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Claim::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'claim_admin_item',
        ]);
    }
}