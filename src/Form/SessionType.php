<?php

namespace App\Form;

use App\Entity\Session;
use App\Entity\Workshop;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints\Callback;

class SessionType extends AbstractType
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', null, [
                'widget' => 'single_text',
                'constraints' => [new Callback([$this, 'validateDates'])],
                'error_bubbling' => false
            ])
            ->add('dateheuredeb', null, [
                'widget' => 'single_text',
                'constraints' => [new Callback([$this, 'validateTimeSlot'])],
                'error_bubbling' => false
            ])
            ->add('dateheurefin', null, [
                'widget' => 'single_text',
                'error_bubbling' => false
            ])
            ->add('capacity', null, [
                'constraints' => [
                    new Callback([$this, 'validateCapacity'])
                ]
            ])
            ->add('id_workshop', EntityType::class, [
                'class' => Workshop::class,
                'choice_label' => 'title',
                'disabled' => $options['data']->getIdWorkshop() !== null,
                'required' => true,
                'label' => 'Workshop'
            ])
        ;

        // Add event listener to set participant_count to 0
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $session = $event->getData();
            if ($session instanceof Session) {
                $session->setParticipantCount(0);
            }
        });
    }

    public function validateDates($value, ExecutionContextInterface $context): void
    {
        $session = $context->getRoot()->getData();
        $workshop = $session->getIdWorkshop();
        
        if (!$workshop || !$value) {
            return;
        }

        $workshopStart = $workshop->getDate();
        $workshopEnd = $workshop->getEnddate();
        $sessionDate = $value;

        if ($sessionDate < $workshopStart || $sessionDate > $workshopEnd) {
            $context->buildViolation('Session date must be between {{ start }} and {{ end }}')
                ->setParameters([
                    '{{ start }}' => $workshopStart->format('m/d/Y'),
                    '{{ end }}' => $workshopEnd->format('m/d/Y')
                ])
                ->addViolation();
        }
    }

    public function validateTimeSlot($value, ExecutionContextInterface $context): void
    {
        $form = $context->getRoot();
        $session = $form->getData();
        
        if (!$session->getDate() || !$session->getDateheuredeb() || !$session->getDateheurefin()) {
            return;
        }

        // Check if end time is after start time
        if ($session->getDateheurefin() <= $session->getDateheuredeb()) {
            $context->buildViolation('End time must be after start time')
                ->atPath('dateheurefin')
                ->addViolation();
            return;
        }

        // Check for overlapping sessions using injected EntityManager
        $existingSessions = $this->em->getRepository(Session::class)->findBy([
            'date' => $session->getDate(),
            'id_workshop' => $session->getIdWorkshop()
        ]);

        foreach ($existingSessions as $existingSession) {
            // Skip current session when editing
            if ($session->getId_session() === $existingSession->getId_session()) {
                continue;
            }

            if ($this->timeSlotsOverlap(
                $session->getDateheuredeb(),
                $session->getDateheurefin(),
                $existingSession->getDateheuredeb(),
                $existingSession->getDateheurefin()
            )) {
                $context->buildViolation('This time slot overlaps with an existing session')
                    ->atPath('dateheuredeb')
                    ->addViolation();
                return;
            }
        }
    }

    private function timeSlotsOverlap(\DateTime $start1, \DateTime $end1, \DateTime $start2, \DateTime $end2): bool
    {
        return $start1 < $end2 && $start2 < $end1;
    }

    public function validateCapacity($value, ExecutionContextInterface $context): void
    {
        $session = $context->getRoot()->getData();
        $workshop = $session->getIdWorkshop();
        
        if (!$workshop || !$value) {
            return;
        }

        $remainingCapacity = $workshop->getRemainingCapacity();
        if ($session->getId_session()) {
            $remainingCapacity += $session->getCapacity(); // Add back current session's capacity for edits
        }

        if ($value > $remainingCapacity) {
            $context->buildViolation('Maximum available capacity is {{ remaining }}. Workshop total capacity is {{ total }}.')
                ->setParameters([
                    '{{ remaining }}' => $remainingCapacity,
                    '{{ total }}' => $workshop->getCapacity()
                ])
                ->addViolation();
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Session::class,
        ]);
    }
}
