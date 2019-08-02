<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */

class ProductRepository extends ServiceEntityRepository
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(RegistryInterface $registry, UserRepository $userRepository)
    {
        parent::__construct($registry, Product::class);
        $this->userRepository = $userRepository;
    }

    public function getOwnerProducts(User $owner)
    {
        return $this->createQueryBuilder("p")
            ->where("p.owner = :owner")
            ->setParameter("owner", $owner)
            ->orderBy("p.date_created", "DESC")
            ->getQuery()
            ->getResult();
    }

    public function getSearchResults(array $params)
    {
        [$value, $user, $priceMin, $priceMax] = $params;
        $user = $this->userRepository->findOneBy(["username" => $user]);

        $qb = $this->createQueryBuilder("p")
                ->where("p.price >= :priceMin")
                ->setParameter("priceMin" , $priceMin)
                ->andWhere("p.price <= :priceMax")
                ->setParameter("priceMax" , $priceMax);
            if (!is_null($value) && strlen($value) > 0) {
                $qb = $qb->andWhere("p.name LIKE :name")
                    ->setParameter("name", '%' . $value . '%');
            }
            if (!is_null($user)) {
                $qb = $qb->andWhere("p.owner = :user")
                    ->setParameter("user", $user);
            }
            $qb = $qb->orderBy("p.date_created", "DESC")
                ->getQuery();
        return $qb;
    }

    // /**
    //  * @return Product[] Returns an array of Product objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Product
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
