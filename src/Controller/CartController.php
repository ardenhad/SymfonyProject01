<?php


namespace App\Controller;


use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Service\Security as ServiceSecurity;
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

    public function __construct(Security $security)
    {
        $this->security = $security;
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
            if (is_array($cart)) {
                $cart = array_reverse($cart);
            }
            $entityManager = $this->getDoctrine()->getManager();
            $productRepository = $entityManager->getRepository(Product::class);
            $products = $productRepository->findAll();

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

        } else {
            if ($quantity <= $product->getAvailableQuantity()) {

                $cart = $request->getSession()->get("cart");
                if (is_null($cart)) {
                    $cart = [];
                };
                array_push($cart,
                    ["id" => $product->getId(), "quantity" => $quantity, "price" => $product->getPrice()]
                );
                $request->getSession()->set("cart", $cart);
            }
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
        $isUserRegistered = ServiceSecurity::isUserRegistered($user);
        $quantity = $request->get("quantity");

        $entityManager = $this->getDoctrine()->getManager();

        if ($isUserRegistered) {
            //TODO: Decide when price will be updated to new one.. Remove and readd to cart sounds non-friendly.
            //For registered user, check cart item id.
            $cartItem = $entityManager->getRepository(Product::class)->find($id);

            $cartItem->setQuantity($quantity);

            $entityManager->persist($cartItem);
            $entityManager->flush();

        } else {
            //For anonymous user, check product id.
            $productRepository = $entityManager->getRepository(Product::class);

            $product = $productRepository->find($id);

            $cart = $request->getSession()->get("cart");

            $idColumn = array_column($cart, "id");
            $index = array_search($product->getId(), $idColumn);
            $cart[$index]["quantity"] = $quantity;

            $request->getSession()->set("cart", $cart);
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

        $entityManager = $this->getDoctrine()->getManager();

        if ($isUserRegistered) {
            //For registered user, check cart item id.
            $cartItem = $entityManager->getRepository(CartItem::class)->find($id);
            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->remove($cartItem);
            $entityManager->flush();
        } else {
            //For anonymous user, check product id.
            $product = $entityManager->getRepository(Product::class)->find($id);

            $cart = $request->getSession()->get("cart");

            $idColumn = array_column($cart, "id");
            $index = array_search($product->getId(), $idColumn);

            unset($cart[$index]);
            $cart = array_values($cart);

            $request->getSession()->set("cart", $cart);
        }
        $this->addFlash("notice", "Item has been successfully removed from your cart.");

        return $this->redirectToRoute("cart_index");
    }


}