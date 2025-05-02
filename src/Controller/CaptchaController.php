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

        // Stocker le code CAPTCHA dans la session
        $session->set('captcha_code', $captcha->getPhrase());

        // Log pour vérifier que le code est stocké
        $this->addFlash('debug', 'CAPTCHA généré : ' . $session->get('captcha_code'));

        return $this->json(['captcha' => $captcha->inline()]);
    }

    #[Route('/captcha/validate', name: 'captcha_validate', methods: ['POST'])]
    public function validate(Request $request, SessionInterface $session): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userCaptcha = isset($data['captcha']) ? trim($data['captcha']) : '';
        $captchaCode = $session->get('captcha_code');

        // Log pour débogage
        $this->addFlash('debug', 'Saisie utilisateur : ' . $userCaptcha . ' | Code stocké : ' . $captchaCode);

        if (empty($userCaptcha) || empty($captchaCode)) {
            return $this->json([
                'success' => false,
                'error' => 'Code CAPTCHA manquant ou session expirée.'
            ], 400);
        }

        if (strtolower($userCaptcha) === strtolower($captchaCode)) {
            $session->remove('captcha_code');
            return $this->json(['success' => true], 200);
        }

        return $this->json([
            'success' => false,
            'error' => 'Code CAPTCHA incorrect.'
        ], 400);
    }
}