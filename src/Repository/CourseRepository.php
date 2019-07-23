<?php

namespace App\Repository;

use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Course|null find($id, $lockMode = null, $lockVersion = null)
 * @method Course|null findOneBy(array $criteria, array $orderBy = null)
 * @method Course[]    findAll()
 * @method Course[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CourseRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Course::class);
    }

    public function getAllSlugs()
    {
        $finalSlugs = [];
        $slugs = $this->createQueryBuilder('c')
            ->select('c.slug')
            ->getQuery()
            ->getResult();

        foreach ($slugs as $slug) {
            array_push($finalSlugs, $slug['slug']);
        }
        return $finalSlugs;
    }

}