<?php


namespace App\Controller;


use App\Entity\User;
//use App\Event\UserRegisterEvent;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class RegisterController extends AbstractController
{
    private $flashBag;

    public function __construct(FlashBagInterface $flashBag)
    {
        $this->flashBag = $flashBag;
    }

    /**
     * @Route("/register", name="user_register")
     */
    public function register(
        UserPasswordEncoderInterface $passwordEncoder,
        Request $request,
        EventDispatcherInterface $eventDispatcher
        /*TokenGenerator $tokenGenerator*/
    )
    {

        $user = new User();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $passwordEncoder->encodePassword(
                $user,
                $user->getPlainPassword()
            );
            $user->setPassword($password);
//            $user->setConfirmationToken($tokenGenerator->getRandomSecureToken(30));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            //TODO: Add flashBag here after completing phone req.
            $this->redirect("product_index");
        }

        return $this->render("register/register.html.twig", [
            "form" => $form->createView()
        ]);
    }
}