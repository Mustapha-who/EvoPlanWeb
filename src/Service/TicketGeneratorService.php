<?php

namespace App\Service;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Symfony\Component\Filesystem\Filesystem;
use Imagine\Image\Font\Font;
use Imagine\Image\Palette\RGB;

class TicketGeneratorService
{
    private $imagine;
    private $filesystem;
    private $ticketsDirectory;
    private $eventImagesDirectory;

    public function __construct(string $ticketsDirectory,string $eventImagesDirectory)
    {
        $this->imagine = new Imagine();
        $this->filesystem = new Filesystem();
        $this->ticketsDirectory = $ticketsDirectory;
        $this->eventImagesDirectory = $eventImagesDirectory;
    }

    public function generateTicket(string $eventName, string $eventDate, string $price, string $location, string $eventImageFilename, string $clientInfo): string
    {
        $qrResult = Builder::create()
            ->writer(new PngWriter())
            ->data($clientInfo)
            ->size(150)
            ->margin(10)
            ->build();

        $qrPath = $this->ticketsDirectory.'/qr_'.uniqid().'.png';
        $qrResult->saveToFile($qrPath);

        $width = 1000;
        $height = 350;
        $palette = new \Imagine\Image\Palette\RGB();
        $ticket = $this->imagine->create(new Box($width, $height), $palette->color('fff'));

        // Section gauche (gris clair)
        $ticket->draw()->rectangle(
            new Point(0, 0),
            new Point(250, $height),
            $palette->color('f5f5f5'),
            true
        );

        // Section droite (violet)
        $ticket->draw()->rectangle(
            new Point(750, 0),
            new Point(1000, $height),
            $palette->color('3A2463'),
            true
        );

        // 👉 Construire le chemin complet vers l'image à partir du nom de fichier
        $fullImagePath = $this->eventImagesDirectory . '/' . $eventImageFilename;

        // Charger image de l’événement
        if (file_exists($fullImagePath)) {
            error_log('Image path OK: ' . $fullImagePath);
            $eventImage = $this->imagine->open($fullImagePath)->resize(new Box(500, $height));
            $ticket->paste($eventImage, new Point(250, 0));

            // Assombrir pour lisibilité
            $overlay = $this->imagine->create(new Box(500, $height), $palette->color([0, 0, 0], 5));
            $ticket->paste($overlay, new Point(250, 0));
        } else {
            error_log('Image path NOT FOUND: ' . $fullImagePath);
        }

        // Charger QR code
        $qrImage = $this->imagine->open($qrPath)->resize(new Box(150, 150));
        $ticket->paste($qrImage, new Point(790, 190));

        $font = $this->imagine->font(__DIR__.'/../../public/fonts/arial.ttf', 22, $palette->color('000'));
        $boldFont = $this->imagine->font(__DIR__.'/../../public/fonts/arialbd.ttf', 26, $palette->color('3A2463'));

        // Section gauche
        $ticket->draw()->text("EVENT NAME", $boldFont, new Point(20, 40), 0);
        $ticket->draw()->text($eventName, $font, new Point(20, 80), 0);

        $ticket->draw()->text("DATE", $boldFont, new Point(20, 140), 0);
        $ticket->draw()->text($this->formatDate($eventDate), $font, new Point(20, 180), 0);

        $ticket->draw()->text("LOCATION", $boldFont, new Point(20, 250), 0);
        $ticket->draw()->text($location, $font, new Point(20, 290), 0);

        // Section droite
        $whiteFont = $this->imagine->font(__DIR__.'/../../public/fonts/arialbd.ttf', 24, $palette->color('fff'));
        $ticket->draw()->text("TICKET", $whiteFont, new Point(770, 40), 0);
        $ticket->draw()->text("VIP: ".$price."ENTRY PASS", $font, new Point(770, 80), 0);
        $ticket->draw()->text("DAY: ".$this->formatShortDate($eventDate), $font, new Point(770, 120), 0);

        $ticketPath = $this->ticketsDirectory.'/ticket_'.uniqid().'.png';
        $ticket->save($ticketPath);

        $this->filesystem->remove($qrPath);
        return $ticketPath;
    }

    private function formatDate(string $dateString): string
    {
        $date = \DateTime::createFromFormat('Y-m-d', $dateString);
        return $date ? $date->format('d.m.Y') : $dateString;
    }

    private function formatShortDate(string $dateString): string
    {
        $date = \DateTime::createFromFormat('Y-m-d', $dateString);
        return $date ? $date->format('d - Y') : $dateString;
    }

}