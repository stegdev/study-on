<?php

namespace App\Repository;

use App\Entity\Course;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpKernel\Exception\HttpException;
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

    public function findAllCombined($billingClient, $user)
    {
        $coursesStudyOn = $this->createQueryBuilder('c')
            ->select('c.id', 'c.name', 'c.description', 'c.slug')
            ->getQuery()
            ->getResult();

        $coursesBilling = $billingClient->getCourses();
        $combinedCourses = $this->mergeByCode($coursesStudyOn, $coursesBilling, function ($item1, $item2) {
            return $item1['slug'] == $item2['code'];
        });
        if (isset($user)) {
            $userTransactions = $billingClient->getPaymentTransactions($user->getApiToken());
            if ($userTransactions == '') {
                return $combinedCourses;
            } else {
                for ($i = 0; $i < count($combinedCourses); $i++) {
                    foreach ($userTransactions as $transaction) {
                        if ($transaction['course_code'] == $combinedCourses[$i]['slug']) {
                            $combinedCourses[$i]['transaction_type'] = $transaction['type'];
                            $combinedCourses[$i]['expires_at'] = $transaction['expires_at'];
                        }
                    }
                }
            }
        }
        return $combinedCourses;
    }

    public function findOneCombined($slug, $billingClient, $user)
    {
        $course = $this->findOneBy(['slug' => $slug]);
        if (!$course) {
            throw new HttpException(404);
        } else {
            $orderBy = (Criteria::create())->orderBy([
                'number' => Criteria::ASC,
            ]);
            $lessons = ($course->getLessons())->matching($orderBy);
            $courseStudyOn = $this->createQueryBuilder('c')
                ->select('c.id', 'c.name', 'c.description', 'c.slug')
                ->andWhere('c.slug = :slug')->setParameter('slug', $slug)
                ->getQuery()
                ->getResult();

            $courseBilling = $billingClient->getCourseByCode($slug);
            $combinedCourse = $this->mergeByCode($courseStudyOn, $courseBilling, function ($item1, $item2) {
                return $item1['slug'] == $item2['code'];
            });
            $combinedCourse[0]['lessons'] = $lessons;
            if (isset($user)) {
                $userTransaction = $billingClient->getTransactionByCode($slug, $user->getApiToken());
                if ($userTransaction == '') {
                    return $combinedCourse[0];
                } else {
                    $combinedCourse[0]['transaction_type'] = $userTransaction[0]['type'];
                    $combinedCourse[0]['expires_at'] = $userTransaction[0]['expires_at'];
                }
            }
            return $combinedCourse[0];
        }
    }

    public function mergeByCode($array1, $array2, $predicate)
    {
        $result = array();

        foreach ($array1 as $item1) {
            foreach ($array2 as $item2) {
                if ($predicate($item1, $item2)) {
                    $result[] = array_merge($item1, $item2);
                }
            }
        }

        return $result;
    }
}