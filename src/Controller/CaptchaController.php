<?php

namespace App\Controller;

use Gregwar\Captcha\CaptchaBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class CaptchaController extends AbstractController
{
    #[Route('/captcha/generate', name: 'captcha_generate', methods: ['GET'])]
    public function generate(SessionInterface $session): JsonResponse
    {
        $captcha = new CaptchaBuilder();
        $captcha->build();

        // Stocker le code CAPTCHA dans la session pour la validation
        $session->set('captcha_code', $captcha->getPhrase());

        // Retourner l'image CAPTCHA en base64
        return $this->json(['captcha' => $captcha->inline()]);
    }

    #[Route('/captcha/validate', name: 'captcha_validate', methods: ['POST'])]
    public function validate(Request $request, SessionInterface $session): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userCaptcha = $data['captcha'] ?? '';

        // Récupérer le code CAPTCHA depuis la session
        $captchaCode = $session->get('captcha_code');

        // Comparer (insensible à la casse)
        if (strtolower($userCaptcha) === strtolower($captchaCode)) {
            return $this->json(['success' => true], 200);
        }

        return $this->json(['success' => false], 400);
    }
}