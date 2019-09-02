<?php


namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Service\Security as ServiceSecurity;
use App\Service\Cart as ServiceCart;
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
    private $security;
    private $cartService;

    public function __construct(Security $security, ServiceCart $cartService)
    {
        $this->security = $security;
        $this->cartService = $cartService;
    }

    /**
     * @Route("", name="cart_index")
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $isUserRegistered = ServiceSecurity::isUserRegistered($user);
        $products = null;
        if ($isUserRegistered) {
            $cart = $user->getCartItems();
        } else {
            $cart = ($request->getSession()->get("cart"));
            $cart = $this->cartService->sortCartByMostRecent($cart);
            $products = $this->cartService->getProducts();
        }

        return new Response($this->renderView("cart/cart-view.html.twig", [
            "cart" => $cart,
            "products" => $products

        ]));
    }

    /**
     * @Route("/add/{product}", name="cart_addItem", methods={"GET", "POST"})
     */
    public function addCartItem(Request $request, Product $product) {
        $user = $this->security->getUser();
        $quantity = $request->get("quantity");

        if ($user === $product->getOwner()) {
            throw new \InvalidArgumentException("User cannot buy own product.");
        }
        if ($quantity == 0 || $quantity > $product->getAvailableQuantity()) {
            throw new \InvalidArgumentException("Illegal item quantity given.");
        }

        $isUserRegistered = ServiceSecurity::isUserRegistered($user);
        if ($isUserRegistered) {
            //TODO: Maybe let's bind this to session as well till user leaves, relieves db a bit(if user buys before end of session)...
            $this->cartService->addUserCartItem($product, $user, $quantity);
        } else {
            $cart = $request->getSession()->get("cart");
            $newCart = $this->cartService->addSessionCartItem($product, $cart, $quantity);
            $request->getSession()->set("cart", $newCart);
        }
        $this->addFlash("notice", "Item has been successfully added to your cart");

        return $this->redirectToRoute("cart_index");
    }

    /**
     * @Route("/edit/{id}", name="cart_editItem", methods={"GET", "POST"})
     */
    public function editCartItem(Request $request, $id)
    {
        $user = $this->security->getUser();
        $quantity = $request->get("quantity");

        if ($quantity == 0) {
            throw new \InvalidArgumentException("Item quantity exceeds available product quantity");
        }

        $isUserRegistered = ServiceSecurity::isUserRegistered($user);
        if ($isUserRegistered) {
            //TODO: Decide when price will be updated to new one.. Remove and readd to cart sounds non-friendly.
            $this->cartService->editUserCartItem($id, $quantity);
        } else {
            $cart = $request->getSession()->get("cart");
            $newCart = $this->cartService->editSessionCartItem($id, $cart, $quantity);
            $request->getSession()->set("cart", $newCart);
        }
        $this->addFlash("notice", "Item count has been successfully modified");

        return $this->redirectToRoute("cart_index");
    }

    /**
     * @Route("/delete/{id}", name="cart_deleteItem")
     */
    public function deleteCartItem(Request $request, $id)
    {
        $user = $this->security->getUser();
        $isUserRegistered = ServiceSecurity::isUserRegistered($user);

        if ($isUserRegistered) {
            //For registered user, check cart item id.
            $this->cartService->deleteUserCartItem($id);
        } else {
            //For anonymous user, check product id.
            $cart = $request->getSession()->get("cart");
            $newCart = $this->cartService->deleteSessionCartItem($id, $cart);
            $request->getSession()->set("cart", $newCart);
        }
        $this->addFlash("notice", "Item has been successfully removed from your cart.");

        return $this->redirectToRoute("cart_index");
    }


}