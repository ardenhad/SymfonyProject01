<?php


namespace App\Controller;


use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Security;
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

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @Route("", name="cart_index")
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();
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
     * @Route("/add/{product}", name="cart_addItem")
     */
    public function addProductToCart(Request $request, Product $product) {
        $user = $request->getUser();
        $isUserRegistered = Security::isUserRegistered($user);

        if ($isUserRegistered) {
            //TODO: Maybe let's bind this to session as well till user leaves..
            $cartItem = new CartItem();
            $cartItem->setUser($user);
            $cartItem->setProduct($product);
//            $cartItem->setQuantity();
            $cartItem->setPrice($product->getPrice());
        } else {
            //TODO: Add to session cart.
        }
    }

}