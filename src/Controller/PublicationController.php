<?php

namespace App\Controller;

use App\Entity\Publication;
use App\Entity\Event;
use App\Form\PublicationType;
use App\Service\AIContentGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

#[Route('/publication')]
class PublicationController extends AbstractController
{
    #[Route('/', name: 'app_publication_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $publications = $em->getRepository(Publication::class)->findAll();

        return $this->render('publication/index.html.twig', [
            'publications' => $publications,
        ]);
    }

    #[Route('/new/{id_event?}', name: 'app_publication_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        AIContentGenerator $aiGenerator,
        ?int $id_event = null
    ): Response {
        $publication = new Publication();
        $publication->setContenu('');
        $publication->setDatePublication(new \DateTime());

        $event = null;

        if ($id_event !== null) {
            $event = $em->getRepository(Event::class)->find($id_event);
            if (!$event) {
                $this->addFlash('warning', 'Événement non trouvé');
                return $this->redirectToRoute('app_publication_new');
            }
            $publication->setEvent($event);
        }

        $form = $this->createForm(PublicationType::class, $publication);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imagePath')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                    $publication->setImagePath($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors du téléchargement de l\'image');
                }
            }
            $em->persist($publication);
            $em->flush();

            $this->addFlash('success', 'Publication enregistrée !');
            return $this->redirectToRoute('app_publication_index');
        }

        return $this->render('publication/new.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
            'publication' => $publication,
        ]);
    }

    #[Route('/generate-caption', name: 'app_generate_caption', methods: ['POST'])]
    public function generateCaption(
        Request $request,
        EntityManagerInterface $em,
        AIContentGenerator $aiGenerator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['event_id'])) {
            return new JsonResponse(['error' => 'Event ID is required'], 400);
        }

        $event = $em->getRepository(Event::class)->find($data['event_id']);

        if (!$event) {
            return new JsonResponse(['error' => 'Event not found'], 404);
        }

        try {
            $caption = $aiGenerator->generateCaption($event, $data['platform'] ?? 'facebook');
            return new JsonResponse(['caption' => $caption]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}