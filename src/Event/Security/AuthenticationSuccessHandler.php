<?php


namespace App\Event\Security;


use App\Entity\CartItem;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use function mysql_xdevapi\getSession;
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

    public function __construct(HttpUtils $httpUtils, array $options = [], EntityManager $entityManager, ProductRepository $productRepository)
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
        $cart = $request->getSession()->get("cart");
        $user = $token->getUser();

        //TODO: Check if any session cartItem belongs to user.
        //TODO: Check if any session cartItem intersects with user cartItems.
        /*
        if (is_array($cart)) {
            forEach ($cart as $cartItem) {
                $product_id = $cartItem["id"];
                $product_quantity = $cartItem["quantity"];
                $product_price = $cartItem["price"];

                $entityManager = $this->entityManager;

                $productRepository = $entityManager->getRepository(Product::class);
                $product = $productRepository->find($product_id);

                $cartItemEntity = new CartItem;
                $cartItemEntity->setUser($user);
                $cartItemEntity->setProduct($product);
                $cartItemEntity->setQuantity($product_quantity);
                $cartItemEntity->setPrice($product_price);

                try {
                    $entityManager->persist($cartItemEntity);
                    $entityManager->flush();
                } catch (ORMException $e) {
                    var_dump($e);die;
                }
            }
        }
        */
        return parent::onAuthenticationSuccess($request, $token);
    }
}