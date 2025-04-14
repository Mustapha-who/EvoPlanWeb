<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Workshop;
use App\Entity\UserModule\Instructor;
use App\Entity\UserModule\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class WorkshopType extends AbstractType
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        ob_start();
        try {
            $builder
                ->add('title')
                ->add('date', null, [
                    'widget' => 'single_text'
                ])
                ->add('enddate', null, [
                    'widget' => 'single_text',
                    'constraints' => [new Callback([$this, 'validateDates'])],
                    'error_bubbling' => false
                ])
                ->add('instructor', EntityType::class, [
                    'class' => Instructor::class,
                    'choices' => $this->getInstructors(),
                    'choice_label' => function(Instructor $instructor) {
                        return $instructor->getName(); // Display instructor name
                    },
                    'placeholder' => 'Select an instructor',
                    'required' => true
                ])
                ->add('capacity')
                ->add('location')
                ->add('description')
                ->add('id_event', EntityType::class, [
                    'class' => Event::class,
                    'choice_label' => 'nom',
                    'placeholder' => 'Select an event',
                    'required' => true,
                    'label' => 'Event'
                ]);
        } finally {
            ob_end_clean();
        }
    }

    public function validateDates($value, ExecutionContextInterface $context): void
    {
        $form = $context->getRoot();
        $data = $form->getData();
        
        if ($data->getIdEvent() && $data->getDate() && $data->getEnddate()) {
            $eventStart = $data->getIdEvent()->getDateDebut()->format('m/d/Y');
            $eventEnd = $data->getIdEvent()->getDateFin()->format('m/d/Y');
            $workshopStart = $data->getDate()->format('m/d/Y');
            $workshopEnd = $data->getEnddate()->format('m/d/Y');

            if ($workshopStart < $eventStart || $workshopEnd > $eventEnd || $workshopEnd < $workshopStart) {
                $context->buildViolation("Workshop dates must be between {$eventStart} and {$eventEnd}")
                    ->atPath('enddate')
                    ->addViolation();
            }
        }
    }

    private function getInstructors(): array
    {
        $qb = $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u INSTANCE OF App\Entity\UserModule\Instructor');
        
        $qb->leftJoin('App\Entity\UserModule\Instructor', 'i', 'WITH', 'i.id = u.id')
           ->andWhere('i.id IS NOT NULL'); // Only users with instructor records
        
        return $qb->orderBy('u.name', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Workshop::class,
        ]);
    }
}