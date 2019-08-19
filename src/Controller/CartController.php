<?php


namespace App\Controller;


use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Security as ServiceSecurity;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("cart")
 */
class CartController extends AbstractController
{
    /**
     * @var UserRepository
     */
    private $userRepository;
    private $security;

    public function __construct(UserRepository $userRepository, Security $security, SessionInterface $session)
    {
        $this->userRepository = $userRepository;
        $this->security = $security;
    }

    /**
     * @Route("", name="cart_index")
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $this->security->getUser();
        if (is_null($user)) {
            //TODO: Retrieve cart from session.
            $cart = [];
        } else {
            $cart = $user->getCartItems();
        }

        return new Response($this->renderView("cart/cart-view.html.twig", [
            "cart" => $cart,

        ]));
    }

    /**
     * @Route("/add/{product}", name="cart_addItem", methods={"GET", "POST"})
     */
    public function addCartItem(Request $request, Product $product) {
        $user = $this->security->getUser();
        $isUserRegistered = ServiceSecurity::isUserRegistered($user);
        $quantity = $request->get("quantity");
        if ($isUserRegistered) {
            //TODO: Maybe let's bind this to session as well till user leaves, relieves db a bit(if user buys before end of session)...
            $cartItem = new CartItem;

            $cartItem->setUser($user);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);
            $cartItem->setPrice($product->getPrice());

            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->persist($cartItem);
            $entityManager->flush();

            $this->addFlash("notice", "Item has been successfully added to your cart");
        } else {
            //TODO: Add to session cart.
        }
        return $this->redirectToRoute("cart_index");
    }

    /**
     * @Route("/edit/{cartItem}", name="cart_editItem", methods={"GET", "POST"})
     */
    public function editCartItem(Request $request, CartItem $cartItem)
    {
        $user = $this->security->getUser();
        $isUserRegistered = ServiceSecurity::isUserRegistered($user);
        $quantity = $request->get("quantity");

        if ($isUserRegistered) {
            //TODO: Decide when price will be updated to new one.. Remove and readd to cart sounds non-friendly.
            $cartItem->setQuantity($quantity);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($cartItem);
            $entityManager->flush();

            $this->addFlash("notice", "Item count has been successfully modified");
        } else {
            //TODO: Edit session cartItem.
        }

        return $this->redirectToRoute("cart_index");
    }

    /**
     * @Route("/delete/{cartItem}", name="cart_deleteItem")
     */
    public function deleteCartItem(Request $request, CartItem $cartItem)
    {

        $user = $this->security->getUser();
        $isUserRegistered = ServiceSecurity::isUserRegistered($user);
        if ($isUserRegistered) {
            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->remove($cartItem);
            $entityManager->flush();

            $this->addFlash("notice", "Item has been successfully removed from your cart.");
        } else {
            //TODO: Delete cartItem from session.
        }

        return $this->redirectToRoute("cart_index");
    }

}