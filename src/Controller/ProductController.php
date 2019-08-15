<?php


namespace App\Controller;


use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Message;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Route("products")
 */
class ProductController extends AbstractController
{
    const PRICE_MIN = 0;
    const PRICE_MAX = 10000;
    const ITEM_PER_PAGE = 10; //Product count per page, used for pagination.

    private $productRepository;
    private $userRepository;
    /**
     * @var PaginatorInterface
     */
    private $paginator;

    public function __construct(ProductRepository $productRepository, UserRepository $userRepository, PaginatorInterface $paginator)
    {
        $this->productRepository = $productRepository;
        $this->userRepository = $userRepository;
        $this->paginator = $paginator;
    }

    /**
     * @Route("", name="product_index")
     */
    public function index(Request $request)
    {
        $filterParams = $this->getSearchParams($request);
        $products = $this->productRepository->getSearchResults($filterParams);

        [$value, $user, $priceMin, $priceMax, $sortType, $sortOrder] = $filterParams;
        if (is_null($value))
            $value = "";
        if (is_null($user))
            $user = "";
        if ($priceMin === 0)
            $priceMin = "";
        if ($priceMax === 10000)
            $priceMax = "";
        if (is_null($sortType) || strlen($sortType) === 0)
            $sortType = "date_changed";
        if (is_null($sortOrder) || strlen($sortOrder) === 0)
            $sortOrder = "asc";

        $filterDisplayedParams = [
            "value" => $value,
            "user" => $user,
            "priceMin" => $priceMin,
            "priceMax" => $priceMax,
            "sortType" => $sortType,
            "sortOrder" => $sortOrder
        ];

        $sortingTypes = [
            "name" => "Name",
            "price" => "Price",
            "owner" => "Seller"
        ];

        $sortingOrders = [
            "asc" => "Ascending",
            "desc" => "Descending"
        ];

        $pagination = $this->paginator->paginate(
            $products,
            $request->query->getInt("page", 1), self::ITEM_PER_PAGE
        );

        return new Response($this->renderView("product/index.html.twig", [
            "pagination" => $pagination,
            "users" => $this->userRepository->findBy([], ["username" => "ASC"]),
            "filterData" => $filterDisplayedParams,
            "types" => $sortingTypes,
            "orders" => $sortingOrders
        ]));
    }

    /**
     * @Route("/product/{id}", name="product_product")
     */
    public function product(Product $product)
    {
        return new Response(
            $this->renderView(
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
            $date = new \DateTime();

            if ($isNew) {
                $product->setDateCreated($date);
                $product->setOwner($this->getUser());
            }
            $product->setDateUpdated($date);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($product);
            $entityManager->flush();

            $message = "";
            if ($isNew)
                $message = "Your product has been successfully listed.";
            else
                $message = "Your product's information has been successfully updated.";
            $this->addFlash("notice", $message);

            $linkToItem = $this->generateUrl(
                "product_product",
                ["id" => $product->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );


            $smsContent = "You have successfully listed a new product:\n". $linkToItem;

//            Message::sendSMS($smsContent);
            return $this->redirectToRoute("product_product" , ["id" => $product->getId()]);
        }

        return $this->render("product/new-product.html.twig", [
            "form" => $form->createView()
        ]);
    }

    /**
     * @Route("/create", name="product_create")
     * @Security(expression="is_granted('ROLE_USER')")
     */
    public function createProduct(Request $request) {
        return $this->setupProduct($request);
    }

    /**
     * @Route("/edit/{id}", name="product_edit")
     * @Security(expression="is_granted('edit', product)")
     */
    public function editProduct(Request $request, Product $product) {
        return $this->setupProduct($request, $product);
    }

    /**
     * @Route("/delete/{id}", name="product_delete")
     * @Security(expression="is_granted('delete', product)")
     */
    public function deleteProduct(Product $product) {

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($product);
        $entityManager->flush();

        $this->addFlash("warning", "Your product has been removed.");

        return $this->redirectToRoute("product_index");
    }

    /**
     * @Route("/{username}", name="product_ownerProducts")
     */
    public function displayOwnerProducts(User $owner) {
        return new Response($this->renderView("product/user-products.html.twig", [
           "products" => $owner->getProducts(),
            "user" => $owner
        ]));
    }

    public function getSearchParams(Request $request) {
        $searchInput = $request->get("q");
        $user = $request->get("user");
        $priceMin = $request->get("priceMin");
        $priceMax = $request->get("priceMax");
        $sortType = $request->get("type");
        $sortOrder = $request->get("order");


        //Setup default priceMin if not set.
        if (is_null($priceMin) || strlen($priceMin) === 0)
            $priceMin = self::PRICE_MIN;
        //Setup default priceMax if not set.
        if (is_null($priceMax) || strlen($priceMax) === 0)
            $priceMax = self::PRICE_MAX;

        //Switch if user mixed them.
        if ($priceMin > $priceMax) {
            $temp = $priceMin;
            $priceMin = $priceMax;
            $priceMax = $temp;
        }
        return [$searchInput, $user, $priceMin, $priceMax, $sortType, $sortOrder];
    }
}