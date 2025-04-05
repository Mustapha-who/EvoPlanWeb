<?php

namespace App\Form;  // ← MUST include namespace

use App\Entity\Event;
use App\Entity\Workshop;
use App\Entity\UserModule\Instructor;
use App\Entity\UserModule\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManagerInterface;

class WorkshopType extends AbstractType
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Start output buffering as safety measure
        ob_start();
        try {
            $builder
                ->add('title')
                ->add('date', null, ['widget' => 'single_text'])
                ->add('enddate', null, ['widget' => 'single_text'])
                ->add('instructor', EntityType::class, [
                    'class' => Instructor::class,
                    'choices' => $this->getInstructors(),
                    'choice_label' => fn(Instructor $i) => (string)$i->getId(),
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
            ob_end_clean(); // Discard any accidental output
        }
    }

    private function getInstructors(): array
{
    $qb = $this->em->getRepository(User::class)
        ->createQueryBuilder('u')
        ->where('u INSTANCE OF App\Entity\UserModule\Instructor');
    
    // Join with instructor table to ensure existence
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