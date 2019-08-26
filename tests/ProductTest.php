<?php


namespace App\Tests;


use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductTest extends WebTestCase
{
    const USER1_TEST_USERNAME = "phpUnitTestUser1";
    const USER1_TEST_PASSWORD = "unitTest1";
    const USER2_TEST_USERNAME = "phpUnitTestUser2";
    const USER2_TEST_PASSWORD = "unitTest2";

    /** @var KernelBrowser */
    private $client = null;
    /** @var EntityManager */
    private $em;

    public function setUp()
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->em = $this->client->getContainer()->get("doctrine.orm.entity_manager");
        //$this->em->getConnection()->setAutoCommit(false);
        $this->em->beginTransaction();
    }

    public function tearDown()
    {
        $this->em->rollback();
        //$this->em->getConnection()->setAutoCommit(true);
    }

    /**
     * @dataProvider providePublicUrls
     * @param $url
     */
    public function testPublicPageNonRegisteredUserIsSuccessful($url)
    {
        $this->client->request("GET", $url);
        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * @dataProvider providePrivateUrls
     */
    public function testPrivatePageNonRegisteredUserIsRedirectedToLogin($url)
    {
        $client = $this->client;
        $client->request("GET", $url);
        $this->assertResponseStatusCodeSame(302);

        $client->followRedirect();
        $uri = $client->getRequest()->getRequestUri();

        $this->assertSame("/login", $uri);
    }

    public function providePublicUrls()
    {
        return [
            ["/products"]
        ];
    }

    public function providePrivateUrls() {
        return [
            ["/products/create"]
        ];
    }

    public function testSuccessfulRegister() {
        $this->register("John_Doe", "johndoe123", "122333");

        //Check if registration redirects to /products.
        $client = $this->client;
        $this->assertResponseStatusCodeSame(302);

        $client->followRedirect();
        $uri = $client->getRequest()->getRequestUri();

        $this->assertSame("/products", $uri);
    }

    public function testSuccessfulLogin() {
        $this->loginTestAccount();

        $this->assertResponseStatusCodeSame(302);

        $client = $this->client;
        $client->followRedirect();
        $uri = $client->getRequest()->getRequestUri();


        $this->assertSame("/products", $uri);
    }

    /**
     * @depends testSuccessfulLogin
     */
    public function testSuccessfulLogout() {
        $this->loginTestAccount();
        $this->logout();

        $client = $this->client;
        $client->followRedirect();

        $uri = $client->getRequest()->getRequestUri();

        $this->assertSame("/products", $uri);
    }

    /**
     * @depends testSuccessfulLogin
     * @dataProvider providePublicUrls
     * @dataProvider providePrivateUrls
     */
    public function testPageRegisteredUserIsSuccessful($url)
    {
        $this->loginTestAccount();
        $this->client->request("GET", $url);
        $this->assertResponseIsSuccessful();
    }

    /**
     * @depends testSuccessfulLogin
     * @dataProvider provideSetupData
     */
    public function testRegisteredUserCreateProduct($productCreateData)
    {
        $this->loginTestAccount();
        $this->setupProduct($productCreateData);

        $uri = $this->client->getRequest()->getRequestUri();
        $this->assertContains("/products/product/", $uri);
    }

    /**
     * @depends testSuccessfulLogin
     * @dataProvider provideSetupData
     */
    public function testUserViewProduct($productCreateData)
    {
        $this->loginTestAccount();
        $productId = $this->setupProduct($productCreateData);
        $this->viewProduct($productId);

        $pageContent = $this->client->getResponse()->getContent();
        $this->assertContains("Product: ".$productCreateData[0] , $pageContent);
        $this->assertContains("Price: $".$productCreateData[1], $pageContent);
        $this->assertContains("Status: ".$productCreateData[2], $pageContent);
    }

    /**
     * @depends testSuccessfulLogin
     * @depends testRegisteredUserCreateProduct
     * @dataProvider provideSetupData
     */
    public function testUserEditProduct($productCreateData, $productEditData)
    {
        $this->loginTestAccount();
        $productId = $this->setupProduct($productCreateData);
        $this->viewProduct($productId);
        $this->setupProduct($productEditData, $productId);

        $pageContent = $this->client->getResponse()->getContent();
        $this->assertContains("Product: ". $productEditData[0], $pageContent);
        $this->assertContains("Price: $".$productEditData[1], $pageContent);
        $this->assertContains("Status: ".$productEditData[2], $pageContent);
    }

    /**
     * @depends testSuccessfulLogin
     * @depends testRegisteredUserCreateProduct
     * @dataProvider provideSetupData
     */
    public function testUserDeleteProduct($productCreateData)
    {
        $this->loginTestAccount();
        $client = $this->client;

        $productId = $this->setupProduct($productCreateData);
        $client->request("GET", "/products/delete/".$productId);

        $this->assertResponseStatusCodeSame(302);
        $client->followRedirect();

        $uri = $client->getRequest()->getRequestUri();
        $this->assertSame("/products", $uri);

        $client->request("GET","/products/product/".$productId);
        $this->assertResponseStatusCodeSame(404);

    }

    public function provideSetupData() {
        return [
            [["violin", "250", "new", "2000"], ["violin", "200", "new", "2000"]],
            [["dishwasher", "750", "used", "3000"],["dishwasher", "800", "new", "3500"]],
            [["laptop", "1100", "used","4000"], ["computer", "1200", "used", "5000"]]
        ];
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
    }

    public function loginTestAccount($username = self::USER1_TEST_USERNAME, $password = self::USER1_TEST_PASSWORD) {
        $this->login($username, $password);
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

}