<?php


namespace App\Tests;


use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

class ProductTest extends WebTestCase
{
    /** @var KernelBrowser */
    private $client = null;
    /** @var EntityManager */
    private $em;

    public function setUp()
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->em = $this->client->getContainer()->get("doctrine.orm.entity_manager");
        $this->em->beginTransaction();
        $this->em->getConnection()->setAutoCommit(false);
    }

    public function tearDown()
    {
        $this->em->rollback();
    }

    /**
     * @dataProvider providePublicUrls
     * @param $url
     */
    public function testPublicPageNonRegisteredUserIsSuccessful($url)
    {
        $client = self::createClient();
        $client->request("GET", $url);
        $this->assertResponseStatusCodeSame(200);
    }

    /**
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
     * @dataProvider providePrivateUrls
     */
    public function testPrivatePageNonRegisteredUserIsRedirectedToLogin($url)
    {
        $client = $this->client;
        $client->request("GET", $url);
        $this->assertResponseStatusCodeSame(302);

        $crawler = $client->followRedirect();
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
        $this->setUp();

        $username = "Jack_Doe";
        $password = "jack12345";

        $this->register($username, $password, "333221");
        $this->login($username, $password);

        $this->assertResponseStatusCodeSame(302);

        $client = $this->client;
        $client->followRedirect();
        $uri = $client->getRequest()->getRequestUri();


        $this->assertSame("/products", $uri);
    }

    /**
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
     *
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
     * @dataProvider provideSetupData
     */
    public function testUserDeleteProduct($productCreateData)
    {
        $this->loginTestAccount("Alex", "alex123");
        $client = $this->client;
        $client->request("GET", "/products/delete/142");

        $this->assertResponseStatusCodeSame(302);
        $client->followRedirect();

        $uri = $client->getRequest()->getRequestUri();
        $this->assertSame("/products", $uri);

        $client->request("GET","/products/product/142");
        $this->assertResponseStatusCodeSame(404);

    }

    public function provideSetupData() {
        return [
            [["violin", "250", "new"], ["violin", "200", "new"]],
            [["dishwasher", "750", "used"],["dishwasher", "800", "new"]],
            [["laptop", "1100", "used"], ["computer", "1200", "used"]]
        ];
    }

    public function provideAccountsBadCredentials() {
        return [
            ["IdontExist", "myPassDoesntExist", "135154166"],
            ["Helloworld", "helloWorld", "12315355"],
            ["wrongUsername", "wrongPassword", "123141351"]
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

    public function loginTestAccount($username = "John_Doe", $password = "john12345") {
        $this->register($username, $password, "192837465");
        $this->login($username, $password);
    }

    public function setupProduct($productSetupData,  $productId = -1)
    {

        [$productName, $price, $status] = $productSetupData;

        $client = $this->client;
        $client->followRedirects(true);
        if ($productId === -1)
            $client->request("GET", "/products/create");
        else
            $client->request("GET", "/products/edit/".$productId);

        $client->submitForm("product[Register]", [
            "product[name]" => $productName,
            "product[price]" => $price,
            "product[status]" => $status
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