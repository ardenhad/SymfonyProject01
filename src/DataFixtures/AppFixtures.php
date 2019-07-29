<?php


namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

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
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->loadUsers($manager);
        $this->loadProducts($manager);
    }

    public function loadUsers(ObjectManager $manager) {
        forEach (self::USERS as $userData) {
            $user = new User();
            $user->setUsername($userData["username"]);
            $user->setPassword($userData["password"]);
            $user->setPhone($userData["phone"]);
            $user->setRoles($userData["roles"]);

            $this->addReference($userData["username"], $user);

            $manager->persist($user);
        }

        $manager->flush();
    }

    public function loadProducts(ObjectManager $manager) {
        for ($i = 0; $i < 30; $i++) {
            $product = new Product();
            $product->setName(self::PRODUCT_NAMES[rand(0, count(self::PRODUCT_NAMES) - 1)]);
            $product->setOwner($this->getReference(self::USERS[rand(0, count(self::USERS) - 1)]["username"]));
            $product->setPrice(rand(1, 10000));

            $date = new \DateTime();
            $product->setDateUpdated($date);
            $date->modify("-".rand(0,10) . "day");
            $product->setDateCreated($date);

            $product->setStatus(self::PRODUCT_STATUS[rand(0, count(self::PRODUCT_STATUS) - 1)]);

            $manager->persist($product);
        }

        $manager->flush();
    }
}