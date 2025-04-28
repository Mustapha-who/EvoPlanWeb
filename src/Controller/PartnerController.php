<?php

namespace App\Controller;

use App\Entity\Partner;
use App\Form\PartnerType;
use App\Repository\PartnerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\FormError;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/partner')]
final class PartnerController extends AbstractController
{
    #[Route('/', name: 'app_partner_index', methods: ['GET'])]
    public function index(PartnerRepository $partnerRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $query = $partnerRepository->createQueryBuilder('p')
            ->orderBy('p.id_partner', 'DESC')
            ->getQuery();

        $partners = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5 // Items per page
        );

        return $this->render('partner/index.html.twig', [
            'partners' => $partners,
        ]);
    }

    #[Route('/new', name: 'app_partner_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $partner = new Partner();
        $form = $this->createForm(PartnerType::class, $partner);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check for duplicate email
            $existingPartnerWithEmail = $entityManager->getRepository(Partner::class)
                ->findOneBy(['email' => $partner->getEmail()]);
            
            if ($existingPartnerWithEmail) {
                $form->get('email')->addError(new FormError('This email is already being used by another partner.'));
                return $this->render('partner/new.html.twig', [
                    'partner' => $partner,
                    'form' => $form,
                ]);
            }
            
            // Check for duplicate phone number
            $existingPartnerWithPhone = $entityManager->getRepository(Partner::class)
                ->findOneBy(['phone_Number' => $partner->getPhoneNumber()]);
            
            if ($existingPartnerWithPhone) {
                $form->get('phone_Number')->addError(new FormError('This phone number is already being used by another partner.'));
                return $this->render('partner/new.html.twig', [
                    'partner' => $partner,
                    'form' => $form,
                ]);
            }
            
            $logoFile = $form->get('logoFile')->getData();

            if ($logoFile) {
                // Calculate file hash for uniqueness check
                $fileContent = file_get_contents($logoFile->getPathname());
                $fileHash = md5($fileContent);
                
                // Check for duplicate logo content
                $allPartners = $entityManager->getRepository(Partner::class)->findAll();
                $duplicateLogoFound = false;
                
                foreach ($allPartners as $existingPartner) {
                    $existingLogoPath = $existingPartner->getLogo();
                    if ($existingLogoPath) {
                        $fullPath = $this->getParameter('kernel.project_dir') . '/public' . $existingLogoPath;
                        if (file_exists($fullPath)) {
                            $existingContent = file_get_contents($fullPath);
                            $existingHash = md5($existingContent);
                            
                            if ($fileHash === $existingHash) {
                                $duplicateLogoFound = true;
                                break;
                            }
                        }
                    }
                }
                
                if ($duplicateLogoFound) {
                    $form->get('logoFile')->addError(new FormError('This logo image is already being used by another partner.'));
                    return $this->render('partner/new.html.twig', [
                        'partner' => $partner,
                        'form' => $form,
                    ]);
                }
                
                // Generate unique filename
                $newFilename = uniqid() . '.' . $logoFile->guessExtension();

                // Move file to uploads directory
                $logoFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/partners',
                    $newFilename
                );

                // Save path in database
                $partner->setLogo('/uploads/partners/' . $newFilename);
            }

            try {
                $entityManager->persist($partner);
                $entityManager->flush();
                $this->addFlash('success', 'Partner created successfully.');
                return $this->redirectToRoute('app_partner_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $form->addError(new FormError('An error occurred while creating the partner.'));
            }
        }

        return $this->render('partner/new.html.twig', [
            'partner' => $partner,
            'form' => $form,
        ]);
    }

    #[Route('/{id_partner}', name: 'app_partner_show', methods: ['GET'])]
    public function show(Partner $partner): Response
    {
        return $this->render('partner/show.html.twig', [
            'partner' => $partner,
        ]);
    }

    #[Route('/{id_partner}/edit', name: 'app_partner_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Partner $partner, EntityManagerInterface $entityManager): Response
    {
        $originalEmail = $partner->getEmail();
        $originalPhone = $partner->getPhoneNumber();
        
        $form = $this->createForm(PartnerType::class, $partner, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if email changed and if it's unique
            if ($partner->getEmail() !== $originalEmail) {
                $existingPartnerWithEmail = $entityManager->getRepository(Partner::class)
                    ->createQueryBuilder('p')
                    ->where('p.email = :email')
                    ->andWhere('p.id_partner != :id')
                    ->setParameter('email', $partner->getEmail())
                    ->setParameter('id', $partner->getId_partner())
                    ->getQuery()
                    ->getOneOrNullResult();
                
                if ($existingPartnerWithEmail) {
                    $form->get('email')->addError(new FormError('This email is already being used by another partner.'));
                    return $this->render('partner/edit.html.twig', [
                        'partner' => $partner,
                        'form' => $form,
                    ]);
                }
            }
            
            // Check if phone number changed and if it's unique
            if ($partner->getPhoneNumber() !== $originalPhone) {
                $existingPartnerWithPhone = $entityManager->getRepository(Partner::class)
                    ->createQueryBuilder('p')
                    ->where('p.phone_Number = :phone')
                    ->andWhere('p.id_partner != :id')
                    ->setParameter('phone', $partner->getPhoneNumber())
                    ->setParameter('id', $partner->getId_partner())
                    ->getQuery()
                    ->getOneOrNullResult();
                
                if ($existingPartnerWithPhone) {
                    $form->get('phone_Number')->addError(new FormError('This phone number is already being used by another partner.'));
                    return $this->render('partner/edit.html.twig', [
                        'partner' => $partner,
                        'form' => $form,
                    ]);
                }
            }
            
            $logoFile = $form->get('logoFile')->getData();
            $originalLogo = $partner->getLogo();

            // Handle logo validation - no longer required in edit mode
            if (empty($logoFile) && !empty($originalLogo)) {
                // If there's an original logo but no new logo uploaded, check if the file exists
                $logoPath = $this->getParameter('kernel.project_dir') . '/public' . $originalLogo;
                if (!file_exists($logoPath) || !is_file($logoPath)) {
                    $form->get('logoFile')->addError(new FormError('The logo file is missing. Please upload a new logo.'));
                    return $this->render('partner/edit.html.twig', [
                        'partner' => $partner,
                        'form' => $form,
                    ]);
                }
            }

            if ($logoFile) {
                // Calculate file hash for uniqueness check
                $fileContent = file_get_contents($logoFile->getPathname());
                $fileHash = md5($fileContent);
                
                // Check for duplicate logo content
                $allPartners = $entityManager->getRepository(Partner::class)->findAll();
                $duplicateLogoFound = false;
                
                foreach ($allPartners as $existingPartner) {
                    // Skip the current partner
                    if ($existingPartner->getId_partner() === $partner->getId_partner()) {
                        continue;
                    }
                    
                    $existingLogoPath = $existingPartner->getLogo();
                    if ($existingLogoPath) {
                        $fullPath = $this->getParameter('kernel.project_dir') . '/public' . $existingLogoPath;
                        if (file_exists($fullPath)) {
                            $existingContent = file_get_contents($fullPath);
                            $existingHash = md5($existingContent);
                            
                            if ($fileHash === $existingHash) {
                                $duplicateLogoFound = true;
                                break;
                            }
                        }
                    }
                }
                
                if ($duplicateLogoFound) {
                    $form->get('logoFile')->addError(new FormError('This logo image is already being used by another partner.'));
                    return $this->render('partner/edit.html.twig', [
                        'partner' => $partner,
                        'form' => $form,
                    ]);
                }
                
                // Delete old file if exists
                $this->safelyDeleteFile($partner->getLogo());

                // Generate unique filename
                $newFilename = uniqid() . '.' . $logoFile->guessExtension();

                // Move file to uploads directory
                $logoFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/partners',
                    $newFilename
                );

                // Save path in database
                $partner->setLogo('/uploads/partners/' . $newFilename);
            }
            // If no new logo file is uploaded, keep the existing logo

            try {
                $entityManager->flush();
                $this->addFlash('success', 'Partner updated successfully.');
                return $this->redirectToRoute('app_partner_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $form->addError(new FormError('An error occurred while updating the partner.'));
            }
        }

        return $this->render('partner/edit.html.twig', [
            'partner' => $partner,
            'form' => $form,
        ]);
    }

    #[Route('/{id_partner}', name: 'app_partner_delete', methods: ['POST'])]
    public function delete(Request $request, Partner $partner, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$partner->getId_partner(), $request->request->get('_token'))) {
            // Delete the image file if it exists
            if ($partner->getLogo()) {
                $this->safelyDeleteFile($partner->getLogo());
            }
            
            $entityManager->remove($partner);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_partner_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Safely deletes a file if it exists and is a file (not a directory)
     */
    private function safelyDeleteFile(?string $path): bool
    {
        if (!$path) {
            return false;
        }
        
        $fullPath = $this->getParameter('kernel.project_dir') . '/public' . $path;
        
        if (file_exists($fullPath) && is_file($fullPath)) {
            return unlink($fullPath);
        }
        
        return false;
    }
}