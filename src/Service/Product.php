<?php


namespace App\Service;


use App\Entity\Product as ProductEntity;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Product
{
    private $entityManager;
    private $productRepository;
    private $userRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->productRepository = $entityManager->getRepository(ProductEntity::class);
        $this->userRepository = $entityManager->getRepository(User::class);
    }

    public function displayLastFilterParams($filterParams) {
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

        return [$filterDisplayedParams, $sortingTypes, $sortingOrders];
    }

    public function getSearchResults($filterParams) {
        return $this->productRepository->getSearchResults($filterParams);
    }

    public function getUsersSortedByUsername()
    {
        return $this->userRepository->findBy([], ["username" => "ASC"]);
    }

    public function setupProduct(ProductEntity $product, User $user, bool $isNew) {
        $date = new \DateTime();

        if ($isNew) {
            $product->setDateCreated($date);
            $product->setOwner($user);
        }
        $product->setDateUpdated($date);

        $this->entityManager->persist($product);
        $this->entityManager->flush();
    }

    public function sendSetupSMS($url) {

        $smsContent = "You have successfully listed a new product:\n". $url;

//        Message::sendSMS($smsContent);
    }


}