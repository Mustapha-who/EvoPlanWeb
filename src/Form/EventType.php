<?php

namespace App\Form;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Enum\Lieu;
use App\Enum\StatutEvent;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', null, [
                'required' => true,
                'attr' => ['placeholder' => 'Enter event name']
            ])
            ->add('description', null, [
                'required' => true,
                'attr' => ['rows' => 3, 'placeholder' => 'Describe the event']
            ])
            ->add('dateDebut', DateTimeType::class, [
                'required' => true,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => ['class' => 'form-control datetimepicker']
            ])
            ->add('dateFin', DateTimeType::class, [
                'required' => true,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => ['class' => 'form-control datetimepicker']
            ])
            ->add('lieu', ChoiceType::class, [
                'choices'  => Lieu::cases(),
                'choice_label' => fn ($choice) => $choice->value,
            ])
            ->add('statut', ChoiceType::class, [
                'choices'  => StatutEvent::cases(),
                'choice_label' => fn ($statut) => $statut->value,
            ])
            ->add('capacite', null, [
                'required' => true,
                'attr' => ['placeholder' => 'Enter max capacity']
            ])
            ->add('prix', null, [
                'required' => true,
                'attr' => ['placeholder' => 'Enter price']
            ])
            ->add('imageEvent', FileType::class, [
                'label' => 'Event Image (JPEG/PNG file)',
                'mapped' => false, // Ne pas lier directement à l'entité
                'required' => false, // Changé de true à false
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG/PNG)',
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
