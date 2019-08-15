<?php


namespace App\DataFixtures;

use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
     private const USERS = [
         [
             "username" => "John",
             "password" => "john123",
             "phone" => "001",
             "roles" => [User::ROLE_USER]
         ],
         [
             "username" => "Alex",
             "password" => "alex123",
             "phone" => "002",
             "roles" => [User::ROLE_USER]
         ],
         [
             "username" => "Stacy",
             "password" => "stacy123",
             "phone" => "003",
             "roles" => [User::ROLE_USER]
         ]
    ];

     private const PRODUCT_NAMES = [
         "chair",
         "table",
         "guitar",
         "phone",
         "vase",
         "monitor",
         "computer",
         "bicycle",
         "car",
     ];

     private const PRODUCT_STATUS = [
         "new",
         "used"
     ];
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
     {
         $this->passwordEncoder = $passwordEncoder;
     }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->loadUsers($manager);
        $this->loadProductsAndCartItems($manager);
    }

    public function loadUsers(ObjectManager $manager) {
        forEach (self::USERS as $userData) {
            $user = new User();
            $user->setUsername($userData["username"]);
            ["password"];

            $password = $this->passwordEncoder->encodePassword(
                $user,
                $userData["password"]
            );
            $user->setPassword($password);

            $user->setPhone($userData["phone"]);
            $user->setRoles($userData["roles"]);

            $this->addReference($userData["username"], $user);

            $manager->persist($user);
        }

        $manager->flush();
    }

    public function loadProductsAndCartItems(ObjectManager $manager) {
        for ($i = 0; $i < 30; $i++) {
            $product = new Product();
            $productName = self::PRODUCT_NAMES[rand(0, count(self::PRODUCT_NAMES) - 1)];
            $productOwner = $this->getReference(self::USERS[rand(0, count(self::USERS) - 1)]["username"]);
            $productPrice = rand(1, 10000);
            $productQuantity = rand(1, 1000);

            $product->setName($productName);
            $product->setOwner($productOwner);
            $product->setPrice($productPrice);
            $product->setQuantity($productQuantity);

            $date = new \DateTime();
            $product->setDateUpdated($date);
            $date = $date->modify("-".rand(0,10) . "day");
            $product->setDateCreated($date);

            $product->setStatus(self::PRODUCT_STATUS[rand(0, count(self::PRODUCT_STATUS) - 1)]);
            $manager->persist($product);
            if (rand(0, 10) > 4) {
                if ($productQuantity === $product->getLockedQuantity()) {
                    //Impossible to create cart item of depleted product.
                    continue;
                }
                $cartItem = new CartItem();
                do {
                    $cartItem->setUser($this->getReference(self::USERS[rand(0, count(self::USERS) - 1)]["username"]));
                } while ($cartItem->getUser() === $productOwner); //cart item and product owner cant be same.
                $cartItem->setProduct($product);
                $cartItem->setPrice($productPrice);
                $cartItem->setQuantity(rand(0, $productQuantity - $product->getLockedQuantity()));

                $manager->persist($cartItem);
            }

        }
        $manager->flush();
    }
}