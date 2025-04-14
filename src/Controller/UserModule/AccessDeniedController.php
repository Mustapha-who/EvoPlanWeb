<?php
namespace App\Controller\UserModule;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Twig\Environment;

class AccessDeniedController implements AccessDeniedHandlerInterface
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): Response
    {
        // Render the Twig template for the 403 error page
        return new Response($this->twig->render('/auth/error-403.html.twig'), 403);
    }
}