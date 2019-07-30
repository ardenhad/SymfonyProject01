<?php


namespace App\Controller;


use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("products")
 */
class ProductController extends AbstractController
{
    /**
     * @var \Twig\Environment
     */
    private $twig;
    /**
     * @var ProductRepository
     */
    private $productRepository;

    public function __construct(\Twig\Environment $twig, ProductRepository $productRepository)
    {

        $this->twig = $twig;
        $this->productRepository = $productRepository;
    }
    /**
     * @Route("/", name="product_index")
     */
    public function index()
    {
        $products = $this->productRepository->findAll();

        return new Response($this->twig->render("product/index.html.twig", [
            "products" => $products
        ]));
    }

    /**
     * @Route("/product/{id}", name="product_product")
     */
    public function product(Product $product)
    {
        return new Response(
            $this->twig->render(
                "product/product.html.twig",
                ["product" => $product]
            )
        );
    }
}