<?php


namespace App\Event\Security;


use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;

class AuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{


    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(HttpUtils $httpUtils, EntityManager $entityManager, array $options = [])
    {
        parent::__construct($httpUtils, $options);
        $this->entityManager = $entityManager;
    }
    /**
     * This is called when an interactive authentication attempt succeeds. This
     * is called by authentication listeners inheriting from
     * AbstractAuthenticationListener.
     *
     * @return Response never null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $sessionCart = $request->getSession()->get("cart");
        /** @var User $user */
        $user = $token->getUser();

        $entityManager = $this->entityManager;
        $dbTransactionSuccessful = true;

        //TODO: Check if any session cartItem belongs to user. X Test Successful
        //TODO: Check if any session cartItem intersects with user cartItems. X Test Successful
        //TODO: Check if product still exists. X Test Successful
        //TODO: Check if quantity can still be locked. X Test Successful
        if (is_array($sessionCart)) {
            $cartItemRepository = $entityManager->getRepository(CartItem::class);
            $productRepository = $entityManager->getRepository(Product::class);

            forEach ($sessionCart as $sessionCartItem) {
                //We are not leaving any cartItem behind!
                $entityManager->getConnection()->setAutoCommit(false);
                $entityManager->getConnection()->beginTransaction();


                //Find the product mentioned in session.
                $product = $productRepository->find($sessionCartItem["id"]);
                //Find if there is already a cartItem for same user-product.
                $cart = $cartItemRepository->findBy(
                    [
                        "product" => $productRepository->find($sessionCartItem["id"]),
                        "user" => $user
                    ]
                );

                if (
                    is_null($product) ||
                    $product->getOwner() == $user ||
                    $product->getAvailableQuantity() < $sessionCartItem["quantity"]
                ) {
                    //Product doesnt exist || Attempt to buy own product || Session demand exceeds current supply
                    continue;
                }

                if (sizeof($cart) > 0) {
                    $samePriceCartItemExists = false;
                    foreach ($cart as $cartItemEntity) {
                        if ($sessionCartItem["price"] == $cartItemEntity->getPrice()) {
                            //Same price, just update this one.

                            $newQuantity = $cartItemEntity->getQuantity() + $sessionCartItem["quantity"];
                            $cartItemEntity->setQuantity($newQuantity);
                            $samePriceCartItemExists = true;
                            break;
                        }
                    }
                }

                if (sizeof($cart) == 0 || !$samePriceCartItemExists) {
                    //CartItem does NOT exist.
                    //Add a new cartItem.

                    $product_id = $sessionCartItem["id"];
                    $product_quantity = $sessionCartItem["quantity"];
                    $product_price = $sessionCartItem["price"];

                    $product = $productRepository->find($product_id);

                    $cartItemEntity = new CartItem;
                    $cartItemEntity->setUser($user);
                    $cartItemEntity->setProduct($product);
                    $cartItemEntity->setQuantity($product_quantity);
                    $cartItemEntity->setPrice($product_price);
                }

                try {
                    $entityManager->persist($cartItemEntity);
                    $entityManager->flush();
                } catch (ORMException $e) {
                    $dbTransactionSuccessful = false;
                    var_dump("ORM Exception: ".$e);
                    die;
                }
            }
        }

        try {
            if ($dbTransactionSuccessful) {
                $entityManager->getConnection()->commit();
            } else {
                $entityManager->getConnection()->rollback();
            }
        } catch (ConnectionException $e) {
            var_dump("Connection exception: ".$e);
            die;
        } finally {
            $entityManager->getConnection()->setAutoCommit(true);
        }
        //NOTE: Looks like it auto-deletes session variables after login.
        return parent::onAuthenticationSuccess($request, $token);
    }
}