<?php


namespace App\Tests;


use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class CartItemTest extends WebTestCase
{
    const USER_TEST_USERNAME_PREFIX = "phpUnitTestUser_";
    const USER_TEST_PASSWORD_PREFIX = "unitTest_";

    /** @var KernelBrowser */
    private $client = null;
    /** @var EntityManager */
    private $em;

    public function setUp()
    {
        //Create a client that will roam our website.
        $this->client = static::createClient();

        //Send all requests through same kernel instance.
        $this->client->disableReboot();

        //Prepare database to not save anything from sessions.
        $this->em = $this->client->getContainer()->get("doctrine.orm.entity_manager");
        //$this->em->getConnection()->setAutoCommit(false);
        $this->em->beginTransaction();

        //Connect session to client.
        $this->session = $this->createSession();
    }

    public function tearDown()
    {
        //Clear all database entries done in here.
        $this->em->rollback();
        //$this->em->getConnection()->setAutoCommit(true);

        //Destroy the session.
        $this->destroySession();
    }

    /**
     * @dataProvider cartPages
     */
    public function testPageNonRegisteredUserIsSuccessful($url)
    {
        $this->client->request("GET", $url);
        $uri = $this->client->getRequest()->getRequestUri();

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame("/cart", $uri);
    }

    /**
     * @dataProvider cartPages
     */
    public function testPageRegisteredUserIsSuccessful($url)
    {
        $this->loginTestAccount();
        $this->client->request("GET", $url);
        $uri = $this->client->getRequest()->getRequestUri();

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame("/cart", $uri);

    }

    public function testUserAddCartItem()
    {
        [$productData, $cartItem, $username, $itemQuantity] = $this->userSetupProductAndItem();
        [$productId, $productName, $productPrice] = $productData;

        $client = $this->client;

        $this->assertResponseRedirects("/cart", 302, $message = "Could not get redirected to /cart page.");
        $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);

        $this->assertNotNull($cartItem, "Couldn't find cartItem in database.");
        $this->assertContains("products/product/" . $productId, $client->getResponse()->getContent(), "/cart page does not contain any sign of associated product.");
        $this->assertContains("Product: " . $productName, $client->getResponse()->getContent());
        $this->assertContains("Cart Price: $" . $productPrice, $client->getResponse()->getContent());
        $this->assertContains("Count: ". $itemQuantity, $client->getResponse()->getContent());
    }



    /**
     * @dataProvider illegalItemQuantities
     */
    public function testUserAddCartItemFailItemIllegalQuantity($itemQuantity)
    {
        [$productData, $cartItem, $username, $itemQuantity] = $this->userSetupProductAndItem
        (
            "TestProduct",
            "25",
            "new",
            "100",
            $itemQuantity
        );
        $this->assertResponseStatusCodeSame(500);
        $this->assertNull($cartItem, "CartItem with ILLEGAL quantity created.");
    }

    /**
     * @depends testUserAddCartItem
     */
    public function testUserEditCartItem()
    {
        /** @var CartItem $cartItem */
        [$productData, $cartItem] = $this->userSetupProductAndItem();
        [$productId, $productName, $productPrice] = $productData;

        $newItemQuantity = 50; //Edit item param.

        $client = $this->client;

        //Edit item
        $client->request("POST", "/cart/edit/" . $cartItem->getId(), ["quantity" => $newItemQuantity]);

        $this->assertResponseRedirects("/cart", 302, "Could not get redirected to /cart page.");
        $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);

        $this->assertContains("products/product/" . $productId, $client->getResponse()->getContent(), "/cart page does not contain any sign of associated product.");
        $this->assertContains("Product: " . $productName, $client->getResponse()->getContent());
        $this->assertContains("Cart Price: $" . $productPrice, $client->getResponse()->getContent());
        $this->assertContains("Count: ". $newItemQuantity, $client->getResponse()->getContent());
    }

    /**
     * @dataProvider illegalItemQuantities
     */
    public function testUserEditCartItem_FailIllegalQuantity ($newItemQuantity)
    {
        [$productData, $cartItem, $username, $itemQuantity] = $this->userSetupProductAndItem();
        //Edit item
        $this->client->request("POST", "/cart/edit/" . $cartItem->getId(), ["quantity" => $newItemQuantity]);

        $this->assertResponseStatusCodeSame(500, "CartItem modified to contain ILLEGAL quantity.");

    }

    /**
     * @depends testUserAddCartItem
     */
    public function testUserDeleteCartItem_Success() {
        $cartItem = $this->userSetupProductAndItem()[1];

        $client = $this->client;
        $client->request("GET", "/cart/delete/". $cartItem->getId());
        $this->assertResponseRedirects("/cart", 302, "Could not get redirected to /cart page.");
        $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);

        //$updatedCartItem = $this->findItem($productId, $username);

        $this->assertNull($cartItem->getId(), "Deleted item still exists in database.");
    }

    public function testSessionAddCartItem_Success() {
        [[$productId, $productName, $productPrice, $productStatus, $productCount], $itemQuantity] = $this->sessionSetupProductAndItem();

        $client = $this->client;

        $this->assertResponseRedirects("/cart", 302, $message = "Could not get redirected to /cart page.");
        $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);

        $cart = $this->getSession()->get("cart");

        $this->assertEquals($cart, [["id" => $productId, "quantity" => $itemQuantity , "price" => $productPrice]], "Unexpected cart in session.");
        $this->assertContains("products/product/" . $productId, $client->getResponse()->getContent(), "/cart page does not contain any sign of associated product.");
        $this->assertContains("Product: " . $productName, $client->getResponse()->getContent());
        $this->assertContains("Cart Price: $" . $productPrice, $client->getResponse()->getContent());
        $this->assertContains("Count: ". $itemQuantity, $client->getResponse()->getContent());

    }

    /**
     * @dataProvider illegalItemQuantities
     */
    public function testSessionAddCartItem_FailIllegalQuantity($itemQuantity)
    {
         $this->sessionSetupProductAndItem
            (
                "TestProduct",
                "25",
                "new",
                "100",
                $itemQuantity
            );

        $this->assertResponseStatusCodeSame(500, $message = "Session CartItem with ILLEGAL quantity created.");
    }

    public function testSessionEditCartItem_Success() {
        [[$productId, $productName, $productPrice, $productStatus, $productCount], $itemQuantity] = $this->sessionSetupProductAndItem();
        $newItemQuantity = 50;
        $client = $this->client;

        $client->request("POST", "/cart/edit/" . $productId, ["quantity" => $newItemQuantity]);

        $this->assertResponseRedirects("/cart", 302, $message = "Could not get redirected to /cart page.");
        $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);

        $cart = $this->getSession()->get("cart");

        $this->assertEquals($cart, [["id" => $productId, "quantity" => $newItemQuantity , "price" => $productPrice]], "Unexpected cart in session.");
        $this->assertContains("products/product/" . $productId, $client->getResponse()->getContent(), "/cart page does not contain any sign of associated product.");
        $this->assertContains("Product: " . $productName, $client->getResponse()->getContent());
        $this->assertContains("Cart Price: $" . $productPrice, $client->getResponse()->getContent());
        $this->assertContains("Count: ". $newItemQuantity, $client->getResponse()->getContent());

    }

    /**
     * @dataProvider illegalItemQuantities
     */
    public function testSessionEditCartItem_FailIllegalQuantity($newItemQuantity) {
        [$productData, $cartItem, $username, $itemQuantity] = $this->userSetupProductAndItem();
        //Edit item
        $this->client->request("POST", "/cart/edit/" . $cartItem->getId(), ["quantity" => $newItemQuantity]);

        $this->assertResponseStatusCodeSame(500, "CartItem modified to contain ILLEGAL quantity.");
    }

    public function testSessionDeleteCartItem_Success() {
        $cartItem = $this->userSetupProductAndItem()[1];

        $client = $this->client;
        $client->request("GET", "/cart/delete/". $cartItem->getId());
        $this->assertResponseRedirects("/cart", 302, "Could not get redirected to /cart page.");
        $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);

        //$updatedCartItem = $this->findItem($productId, $username);

        $cart = $this->getSession()->get("cart");
        
        //Apparently PHP treats empty arrays as null...
        $this->assertNull($cart);
        $this->assertEmpty($cart, "Deleted item still exists in database.");
    }

    /**
     * @depends testSessionAddCartItem_Success
     */
    public function testSessionToClientCartTransaction_SuccessCreateNewItem()
    {
        $productData = ["TestProduct", $price = "100", "new", "250"];
        $itemQuantity = 200;
        $sellerId = 1;
        $buyerId = 2;

        //Login seller account, enlist a product, logout.
        $this->loginTestAccount($sellerId);
        $productId = $this->setupProduct($productData);
        $this->logout();

        //Add the product to session, login buyer account.
        $this->setupSessionItem($productId, $itemQuantity);
        $buyerUsername = $this->loginTestAccount($buyerId);

        $cartItem = $this->findItem($productId, $buyerUsername, $price);
        $this->assertNotNull($cartItem, "Cart item was not created.");
        $this->assertEquals($productId, $cartItem->getProduct()->getId(), "Cart item associated with incorrect product.");
        $this->assertEquals($itemQuantity, $cartItem->getQuantity(), "Cart item has incorrect quantity");
    }
    /**
     * @depends testSessionAddCartItem_Success
     */
    public function testSessionToClientCartTransaction_SuccessUpdateSamePriceItem()
    {
        $productData = ["TestProduct", $price = "100", "new", "250"];
        $itemQuantity = 200;
        $sellerId = 1;
        $buyerId = 2;

        //Login seller account, enlist a product, logout.
        $this->loginTestAccount($sellerId);
        $productId = $this->setupProduct($productData);
        $this->logout();

        //Login buyer account, add the product to account cart, logout
        $buyerUsername = $this->loginTestAccount($buyerId);
        $this->setupUserItem($productId, $buyerUsername, $itemQuantity);
        $this->logout();

        //Add the product to session, login buyer account, logout.
        $this->setupSessionItem($productId, $itemQuantity);
        $this->loginTestAccount($buyerId);

        $cart = $this->getSession()->get("cart");

        //Make sure not a new one is generated.
        $this->assertCount(1, $cartItems = $this->findItems($productId, $buyerUsername));

        $cartItem = $this->findItem($productId, $buyerUsername, $price);
        $this->assertNotNull($cartItem, "Cart item was not created.");
        $this->assertEquals($productId, $cartItem->getProduct()->getId(), "Cart item associated with incorrect product.");
        $this->assertEquals($itemQuantity, $cartItem->getQuantity(), "Cart item has incorrect quantity");
    }

    /**
     * @depends testSessionAddCartItem_Success
     */
    public function testSessionToClientCartTransaction_SuccessCreateSameItemDifferentPriceItem()
    {
        $productData = ["TestProduct", $price = "100", "new", "250"];
        $itemQuantity = 100;
        $sellerId = 1;
        $buyerId = 2;

        //Login seller account, enlist a product, logout.
        $this->loginTestAccount($sellerId);
        $productId = $this->setupProduct($productData);
        $this->logout();

        //Login buyer account, add the product to account cart, logout
        $buyerUsername = $this->loginTestAccount($buyerId);
        $this->setupUserItem($productId, $buyerUsername, $itemQuantity);
        $this->logout();

        //Login seller account again, change price, logout.
        $newProductData = ["TestProduct", $newPrice = "125", "new", "250"];
        $this->loginTestAccount($sellerId);
        $this->setupProduct($newProductData, $productId);
        $this->logout();

        //Add the product to session, login buyer account again.
        $this->setupSessionItem($productId, $itemQuantity);
        $this->loginTestAccount($buyerId);

        //Make sure a new one is generated.
        $this->assertCount(2, $cartItems = $this->findItems($productId, $buyerUsername));

        $cartItem = $this->findItem($productId, $buyerUsername, $price);
        $this->assertNotNull($cartItem, "Cart item was not created.");
        $this->assertEquals($productId, $cartItem->getProduct()->getId(), "Cart item associated with incorrect product.");
        $this->assertEquals($itemQuantity, $cartItem->getQuantity(), "Cart item has incorrect quantity");

        $cartItemNew = $this->findItem($productId, $buyerUsername, $newPrice);
        $this->assertNotNull($cartItemNew, "New Cart item was not created.");
        $this->assertEquals($productId, $cartItemNew->getProduct()->getId(), "New Cart item associated with incorrect product.");
        $this->assertEquals($itemQuantity, $cartItemNew->getQuantity(), "New Cart item has incorrect quantity");
    }
/*
    //Unable to keep session to conduct test.(another client with another session failed/connecting to session with client failed.)
    public function testSessionToClientCartTransaction_FailProductNoLongerExist()
    {
        $productData = ["TestProduct", $price = "100", "new", "250"];
        $itemQuantity = 100;
        $sellerId = 1;
        $buyerId = 2;

        //Login seller account, enlist a product, logout.
        $this->loginTestAccount($sellerId);
        $productId = $this->setupProduct($productData);
        $this->logout();

        //Add the product to session
        $this->setupSessionItem($productId, $itemQuantity);

        //Login seller again, remove product, logout.
        $this->loginTestAccount($sellerId);
        $this->removeProduct($productId);
        $this->logout();






    }
    public function testSessionToClientCartTransaction_FailItemDemandExceedsSupply()
    {
    }
*/
    /**
     * @depends testSessionAddCartItem_Success
     */
    public function testSessionToClientCartTransaction_FailCartItemAndSessionItemQuantityTotalExceedsSupply_SamePrice() {
        $productData = ["TestProduct", $price = "100", "new", "250"];
        $itemQuantity = 200;
        $sellerId = 1;
        $buyerId = 2;

        //Login seller account, enlist a product, logout.
        $this->loginTestAccount($sellerId);
        $productId = $this->setupProduct($productData);
        $this->logout();

        //Login buyer account, add the product to account cart, logout
        $buyerUsername = $this->loginTestAccount($buyerId);
        $this->setupUserItem($productId, $buyerUsername, $itemQuantity);
        $this->logout();

        //Add the product to session, login buyer account again.
        $this->setupSessionItem($productId, $itemQuantity); //At this point 200 + 200 = 400 > 250 which is the issue.
        $this->loginTestAccount($buyerId);

        //Make sure a new one is not generated and old one is still around.
        $this->assertCount(1, $cartItems = $this->findItems($productId, $buyerUsername));
        $cartItem = $this->findItem($productId, $buyerUsername, $price);
        $this->assertNotNull($cartItem, "Cart item was not created.");
        $this->assertEquals($productId, $cartItem->getProduct()->getId(), "Cart item associated with incorrect product.");
        $this->assertEquals($itemQuantity, $cartItem->getQuantity(), "Cart item has incorrect quantity");
    }

    /**
     * @depends testSessionAddCartItem_Success
     */
    public function testSessionToClientCartTransaction_FailCartItemAndSessionItemQuantityTotalExceedsSupply_DifferentPrice()
    {
        $productData = ["TestProduct", $price = "100", "new", "250"];
        $itemQuantity = 200;
        $sellerId = 1;
        $buyerId = 2;

        //Login seller account, enlist a product, logout.
        $this->loginTestAccount($sellerId);
        $productId = $this->setupProduct($productData);
        $this->logout();

        //Login buyer account, add the product to account cart, logout
        $buyerUsername = $this->loginTestAccount($buyerId);
        $this->setupUserItem($productId, $buyerUsername, $itemQuantity);
        $this->logout();

        //Login seller account again, change price, logout.
        $newProductData = ["TestProduct", $newPrice = "125", "new", "250"];
        $this->loginTestAccount($sellerId);
        $this->setupProduct($newProductData, $productId);
        $this->logout();

        //Add the product to session, login buyer account again.
        $this->setupSessionItem($productId, $itemQuantity); //At this point 200 + 200 = 400 > 250 which is the issue.
        $this->loginTestAccount($buyerId);

        //Make sure a new one is not generated and old one is still around.
        $this->assertCount(1, $cartItems = $this->findItems($productId, $buyerUsername));
        $cartItem = $this->findItem($productId, $buyerUsername, $price);
        $cartItemNew = $this->findItem($productId, $buyerUsername, $newPrice);
        $this->assertNotNull($cartItem, "Cart item was not created.");
        $this->assertNull($cartItemNew, "Another cart item was generated despite total demand exceeds supply.");
        $this->assertEquals($productId, $cartItem->getProduct()->getId(), "Cart item associated with incorrect product.");
        $this->assertEquals($itemQuantity, $cartItem->getQuantity(), "Cart item has incorrect quantity");
    }

    /**
     * @depends testSessionAddCartItem_Success
     */
    public function testSessionToClientCartTransaction_FailItemProductBelongsToUser()
    {
        $productData = ["TestProduct", $price = "100", "new", "250"];
        $itemQuantity = 100;
        $sellerId = 1;

        //Login seller account, enlist a product, logout.
        $sellerUsername = $this->loginTestAccount($sellerId);
        $productId = $this->setupProduct($productData);
        $this->logout();

        //Add the product to session, login buyer account again.
        $this->setupSessionItem($productId, $itemQuantity); //At this point 200 + 200 = 400 > 250 which is the issue.
        $this->loginTestAccount($sellerId);

        //Make sure a new one is not generated and old one is still around.
        $cartItem = $this->findItem($productId, $sellerUsername, $price);
        $this->assertNull($cartItem, "Cart item was not created.");
    }

    public function register($username, $password, $phone)
    {
        $client = $this->client;

        //Go to register page and register an account.
        $client->request("GET", "/register");
        $client->submitForm("user_Register",
            [
                "user[username]" => $username,
                "user[plainPassword][first]" => $password,
                "user[plainPassword][second]" => $password,
                "user[phone]" => $phone,
                "user[termsAgreed]" => true
            ]
        );
    }

    public function login($username, $password) {
        $client = $this->client;
        $client->request("GET", "/login");
        $client->submitForm("Login",
            [
                "_username" => $username,
                "_password" => $password
            ]
        );
    }

    public function logout() {
        $client = $this->client;
        $client->request("GET", "/logout");
        //We had lost session when we logged in. Create a new one.
        $this->createSession();
    }

    public function loginTestAccount($id = 1) {
        if ($id > 5) {
            echo "ERROR: Test Account ID cannot exceed 5.";
            exit();
        }

        $username = self::USER_TEST_USERNAME_PREFIX . $id;
        $password = self::USER_TEST_PASSWORD_PREFIX . $id;

        $this->login($username, $password);
        return $username;
    }

    public function setupProduct($productSetupData,  $productId = -1)
    {

        [$productName, $price, $status, $quantity] = $productSetupData;

        $client = $this->client;
        $client->followRedirects(true);
        if ($productId === -1){
            $client->request("GET", "/products/create");
        } else{
            $client->request("GET", "/products/edit/".$productId);
        }

        $client->submitForm("product[Register]", [
            "product[name]" => $productName,
            "product[price]" => $price,
            "product[status]" => $status,
            "product[quantity]" => $quantity
        ]);

        $uri = $client->getRequest()->getRequestUri();

        $linkArray = explode("/", $uri);
        $productID = end($linkArray);
        $client->followRedirects(false);
        return $productID;
    }

    public function viewProduct($productId)
    {
        $client = $this->client;
        $client->request("GET", "/products/product/".$productId);

    }

    public function removeProduct($productId)
    {
        $client = $this->client;
        $client->request("GET", "/products/delete/".$productId);
    }

    /**
     * @param string $productName
     * @param string $productPrice
     * @param string $productStatus
     * @param string $productCount
     * @return array
     */
    public function userSetupProductAndItem($productName = "TestProduct", $productPrice = "25", $productStatus = "new", $productCount = "100", $itemQuantity = 50): array
    {
        $this->loginTestAccount();
        $productId = $this->setupProduct([$productName, $productPrice, $productStatus, $productCount]);
        $this->logout();
        $username = $this->loginTestAccount(2);

        $cartItem = $this->setupUserItem($productId, $username, $itemQuantity);

        return array([$productId, $productName, $productPrice, $productStatus, $productCount], $cartItem, $username, $itemQuantity);
    }

    public function sessionSetupProductAndItem($productName = "TestProduct", $productPrice = "25", $productStatus = "new", $productCount = "100", $itemQuantity = 50) {
        $this->loginTestAccount();
        $productId = $this->setupProduct([$productName, $productPrice, $productStatus, $productCount]);
        $this->logout();
        $this->setupSessionItem($productId, $itemQuantity);

        return array([$productId, $productName, $productPrice, $productStatus, $productCount], $itemQuantity);
    }

    /**
     * @param $productId
     * @param $username
     * @param int $itemQuantity
     * @return CartItem|object|null
     */
    public function setupUserItem($productId, $username, int $itemQuantity)
    {
        $this->client->request("POST", "/cart/add/" . $productId, ["quantity" => $itemQuantity]);

        $productRepository = $this->em->getRepository(Product::class);
        $product = $productRepository->find($productId);
        $price = $product->getPrice();

        $cartItem = $this->findItem($productId, $username, $price);

        return $cartItem;
    }

    public function setupSessionItem($productId, int $itemQuantity)
    {
        $this->client->request("POST", "/cart/add/" . $productId, ["quantity" => $itemQuantity]);
    }

    /**
     * @param $productId
     * @param $username
     * @return CartItem|object|null
     */
    //TODO: Add this fnc to CartItem repository.
    public function findItem($productId, $username, $price)
    {
        $userRepository = $this->em->getRepository(User::class);
        $user = $userRepository->findOneBy(["username" => $username]);

        $productRepository = $this->em->getRepository(Product::class);
        $product = $productRepository->find($productId);

        $cartItemRepository = $this->em->getRepository(CartItem::class);
        $cartItem = $cartItemRepository->findOneBy([
            "user" => $user,
            "product" => $product,
            "price" => $price
        ]);
        return $cartItem;
    }


    /**
     * Finds given user's cartItem instances with given product.(Useful in case of price change.)
     *
     * @param $productId
     * @param $username
     * @return CartItem[]|array|object[]
     */
    public function findItems($productId, $username)
    {
        $userRepository = $this->em->getRepository(User::class);
        $user = $userRepository->findOneBy(["username" => $username]);

        $productRepository = $this->em->getRepository(Product::class);
        $product = $productRepository->find($productId);

        $cartItemRepository = $this->em->getRepository(CartItem::class);
        $cartItems = $cartItemRepository->findBy([
            "user" => $user,
            "product" => $product
        ]);
        return $cartItems;
    }

    public function createSession()
    {
        $session = $this->client->getContainer()->get('session');

        $firewall = "secured_area";

        $token = new AnonymousToken("JzUzQau", "anon");
        $session->set("_security_".$firewall, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    public function getSession()
    {
        $session = $this->client->getContainer()->get('session');
        return $session;
    }

    public function destroySession() {
        $session = $this->client->getContainer()->get('session');
        $session->clear();
    }

    public function cartPages() {
        return [
            ["/cart"]
        ];
    }

    public function illegalItemQuantities() {
        return [
            [0],
            [101],
            [500]
        ];
    }

}