<?php
namespace App\Controller\UserModule;

use App\Entity\Ressource;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExcelController extends AbstractController
{
    #[Route('/export/ressources', name: 'export_ressources', methods: ['GET'])]
    public function export(EntityManagerInterface $entityManager): Response
    {
        try {
            $ressources = $entityManager->getRepository(Ressource::class)->findAll();
            $countAvailable = count(array_filter($ressources, fn($r) => $r->isAvailability()));
            $percentageAvailable = $ressources ? ($countAvailable / count($ressources)) * 100 : 0;

            $ressourcesByType = [];
            foreach ($ressources as $ressource) {
                $ressourcesByType[$ressource->getType()][] = $ressource;
            }

            $spreadsheet = new Spreadsheet();
            $spreadsheet->removeSheetByIndex(0);

            foreach ($ressourcesByType as $type => $ressourcesOfType) {
                $sheet = $spreadsheet->createSheet();
                $sheet->setTitle($type);

                $sheet->setCellValue('A1', 'ID');
                $sheet->setCellValue('B1', 'Nom');
                $sheet->setCellValue('C1', 'Type');
                $sheet->setCellValue('D1', 'Disponible');

                $rowNum = 2;
                foreach ($ressourcesOfType as $ressource) {
                    $sheet->setCellValue('A' . $rowNum, $ressource->getId());
                    $sheet->setCellValue('B' . $rowNum, $ressource->getName());
                    $sheet->setCellValue('C' . $rowNum, $ressource->getType());
                    $sheet->setCellValue('D' . $rowNum, $ressource->isAvailability() ? 'Oui' : 'Non');
                    $rowNum++;
                }

                $sheet->setCellValue('A' . $rowNum, 'Pourcentage de disponibilité');
                $sheet->setCellValue('B' . $rowNum, sprintf('%.2f%%', $percentageAvailable));
            }

            $chartSheet = $spreadsheet->createSheet();
            $chartSheet->setTitle('Graphiques');
            $chartSheet->setCellValue('A1', 'Les graphiques doivent être générés côté client.');

            $writer = new Xlsx($spreadsheet);
            $filename = 'ressources_export.xlsx';

            ob_start();
            $writer->save('php://output');
            $content = ob_get_clean();

            return new Response(
                $content,
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => 'attachment;filename="' . $filename . '"',
                    'Cache-Control' => 'max-age=0',
                ]
            );
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la génération du fichier Excel : ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/ressources', name: 'api_ressources', methods: ['GET'])]
    public function getRessources(EntityManagerInterface $entityManager): Response
    {
        try {
            $ressources = $entityManager->getRepository(Ressource::class)->findAll();
            $data = array_map(fn($r) => [
                'id' => $r->getId(),
                'name' => $r->getName(),
                'type' => $r->getType(),
                'availability' => $r->isAvailability(),
            ], $ressources);

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur : ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
?>