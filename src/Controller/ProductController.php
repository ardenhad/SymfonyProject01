<?php


namespace App\Controller;


use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
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

    public function setupProduct(Request $request, Product $product = Null)
    {
        $isNew = false;
        if (is_null($product)) {
            $product = new Product;
            $isNew = true;
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $date = new\DateTime();

            if ($isNew) {
                $product->setDateCreated($date);
                $product->setOwner($this->getUser());
            }
            $product->setDateUpdated($date);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($product);
            $entityManager->flush();

            //TODO: Send SMS to confirm that the product is successfully added/modified.
            return $this->redirectToRoute("product_index");
        }

        return $this->render("product/new-product.html.twig", [
            "form" => $form->createView(),
            "products" => $this->productRepository->findAll()
        ]);
    }
    /**
     * @Route("/create", name="product_create")
     */
    public function createProduct(Request $request) {
        return $this->setupProduct($request);
    }

    /**
     * @Route("/edit/{id}", name="product_edit")
     */
    public function editProduct(Request $request, Product $product) {
        return $this->setupProduct($request, $product);
    }

    /**
     * @Route("/delete/{id}", name="product_delete")
     */
    public function deleteProduct(Product $product) {

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($product);
        $entityManager->flush();

        return new RedirectResponse($this->router->generate("product/index.html.twig"));
    }
}