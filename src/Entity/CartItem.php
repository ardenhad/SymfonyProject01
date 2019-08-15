<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use http\Exception\InvalidArgumentException;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CartItemRepository")
 */
class CartItem
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @param User $user
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Product")
     * @param Product $product
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     */
    private $product;

    /**
     * @ORM\Column(type="integer")
     */
    private $quantity;

    /**
     * @ORM\Column(type="decimal")
     */
    private $price;

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param mixed $product
     */
    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    /**
     * @return mixed
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param mixed $requestedQuantity
     */
    public function setQuantity($requestedQuantity): void
    {
        /** @var Product $product */
        $product = $this->product;

        //If quantity requested is higher than (actual quantity - reserved quantity)...
        if ($requestedQuantity > $product->getQuantity() - $product->getLockedQuantity()) {
            throw new \InvalidArgumentException("Requested quantity by cart exceeded available quantity");
        }

        //Lock (prevLockedCountOnProduct - prevLockedByThisItem) + currentRequestByThisItem
        $productUpdatedLockedQuantity = ($product->getLockedQuantity() - $this->quantity ) + $requestedQuantity;
        $product->setLockedQuantity($productUpdatedLockedQuantity);

        $this->quantity = $requestedQuantity;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param mixed $price
     */
    public function setPrice($price): void
    {
        $this->price = $price;
    }



    public function getId(): ?int
    {
        return $this->id;
    }
}
