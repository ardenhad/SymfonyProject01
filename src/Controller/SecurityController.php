<?php


namespace App\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    //security->firewall->main handles redirects.

    /**
     * @Route("/login", name="security_login")
     * @Security("!is_granted('ROLE_USER')", message="You need to sign out to login.")
     */
    public function login(AuthenticationUtils $authenticationUtils)
    {

        return new Response($this->renderView(
            "security/login.html.twig",
            [
                "last_username" => $authenticationUtils->getLastUsername(),
                "error" => $authenticationUtils->getLastAuthenticationError()
            ]
        ));
    }

    /**
     * @Route("/logout", name="security_logout")
     */
    public function logout()
    {
        //doNothing.
    }
}