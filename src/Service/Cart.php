<?php


namespace App\Service;


use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class Cart
{

    private $entityManager;
    private $cartItemRepository;
    private $productRepository;

    function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->cartItemRepository = $entityManager->getRepository(CartItem::class);
        $this->productRepository = $entityManager->getRepository(Product::class);
    }

    public function addUserCartItem(Product $product, User $user, string $quantity)
    {
        //Check if such item exists.
        $sameItem = $this->cartItemRepository->findItem($user, $product, $product->getPrice());

        if (!is_null($sameItem)) {
            throw new \InvalidArgumentException("Item already exists in cart.");
        }

        $cartItem = new CartItem;
        $cartItem->setUser($user);
        $cartItem->setProduct($product);
        $cartItem->setQuantity($quantity);
        $cartItem->setPrice($product->getPrice());

        $this->entityManager->persist($cartItem);
        $this->entityManager->flush();
    }

    public function addSessionCartItem(Product $product, $cart, $quantity) {
        if (!is_null($cart)) {
            forEach ($cart as $cartItem) {
                if ($cartItem["id"] == $product->getId() && $cartItem["price"] == $product->getPrice())
                    throw new \InvalidArgumentException("Item already exists in cart.");
            }
        }

        if (is_null($cart)) {
            $cart = [];
        };
        array_push($cart,
            ["id" => $product->getId(), "quantity" => $quantity, "price" => $product->getPrice()]
        );
        return $cart;
    }

    public function editUserCartItem(int $id, $quantity): void 
    {
        $cartItem = $this->cartItemRepository->find($id);

        $cartItem->setQuantity($quantity);

        $this->entityManager->persist($cartItem);
        $this->entityManager->flush();
    }

    public function editSessionCartItem(int $id, $cart, $quantity): array 
    {
        $product = $this->productRepository->find($id);

        $idColumn = array_column($cart, "id");
        $index = array_search($product->getId(), $idColumn);
        $cart[$index]["quantity"] = $quantity;
        return $cart;
    }

    public function deleteUserCartItem($id) {
        $cartItem = $this->cartItemRepository->find($id);

        $this->entityManager->remove($cartItem);
        $this->entityManager->flush();
    }

    public function deleteSessionCartItem($id, $cart) {
        $product = $this->productRepository->find($id);

        $idColumn = array_column($cart, "id");
        $index = array_search($product->getId(), $idColumn);

        unset($cart[$index]);
        $cart = array_values($cart);
        return $cart;
    }
}