<?php

namespace App\Controller;

use App\Entity\Course;
use Doctrine\Common\Collections\Criteria;
use App\Form\CourseType;
use App\Service\BillingClient;
use App\Repository\CourseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Annotation\Method;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Cocur\Slugify\Slugify;

/**
 * @Route("/courses")
 */
class CourseController extends AbstractController
{
    /**
     * @Route("/", name="course_index", methods={"GET"})
     */
    public function index(BillingClient $billingClient): Response
    {
        $auth_checker = $this->get('security.authorization_checker');
        if ($auth_checker->isGranted('ROLE_USER')) {
            return $this->render('course/index.html.twig', [
                'courses' => $this->findAllCombined($billingClient->getCourses(), $billingClient->getPaymentTransactions($this->getUser()->getApiToken()))
        ]);
        } else {
            return $this->render('course/index.html.twig', [
                'courses' => $this->findAllCombined($billingClient->getCourses(), null)
            ]);
        }
    }
    /**
     * @Route("/new", name="course_new", methods={"GET","POST"})
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function new(Request $request, BillingClient $billingClient): Response
    {
        $form = $this->createForm(CourseType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            try {
                $addResponse = $billingClient->addCourse($formData, $this->getUser()->getApiToken());
            } catch (HttpException $e) {
                return $this->render('course/new.html.twig', array(
                    'form' => $form->createView(),
                    'error' => "Сервис временно недоступен. Попробуйте добавить урок позднее"
                ));
            }
            if (array_key_exists('code', $addResponse)) {
                return $this->render('course/new.html.twig', array(
                    'form' => $form->createView(),
                    'error' => $addResponse['message']
                ));
            } else {
                $course = new Course();
                $course->setName($formData['name']);
                $course->setDescription($formData['description']);
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($course);
                $entityManager->flush();
                return $this->redirectToRoute('course_index');
            }
        }
        return $this->render('course/new.html.twig', [
            'form' => $form->createView(),
            'error' => null
        ]);
    }

    /**
     * @Route("/{slug}", name="course_show", methods={"GET"})
     */
    public function show($slug, BillingClient $billingClient): Response
    {
        $auth_checker = $this->get('security.authorization_checker');
        if ($auth_checker->isGranted('ROLE_USER')) {
            return $this->render('course/show.html.twig', [
                'course' => $this->findOneCombined($slug, $billingClient->getCourseByCode($slug), $billingClient->getTransactionByCode($slug, $this->getUser()->getApiToken())),
                'user_balance' => $billingClient->getCurentUserBalance($this->getUser()->getApiToken())
            ]);
        } else {
            return $this->render('course/show.html.twig', [
                'course' => $this->findOneCombined($slug, $billingClient->getCourseByCode($slug), null),
            ]);
        }
    }

    /**
     * @Route("/{slug}/edit", name="course_edit", methods={"GET","POST"})
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function edit($slug, Request $request, Course $course, BillingClient $billingClient): Response
    {
        $combinedCourse = $this->findOneCombined($slug, $billingClient->getCourseByCode($slug), $billingClient->getTransactionByCode($slug, $this->getUser()->getApiToken()));
        $form = $this->createForm(CourseType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            try {
                $editResponse = $billingClient->updateCourse($formData, $slug, $this->getUser()->getApiToken());
            } catch (HttpException $e) {
                return $this->render('course/edit.html.twig', array(
                    'course' => $combinedCourse,
                    'form' => $form->createView(),
                    'error' => $e->getMessage()
                ));
            }
            if (array_key_exists('code', $editResponse)) {
                return $this->render('course/edit.html.twig', array(
                    'course' => $combinedCourse,
                    'form' => $form->createView(),
                    'error' => $editResponse['message']
                ));
            } else {
                $course->setName($formData['name']);
                $course->setDescription($formData['description']);
                $this->getDoctrine()->getManager()->flush();
                return $this->redirectToRoute('course_index');
            }
        }
        return $this->render('course/edit.html.twig', [
            'course' => $combinedCourse,
            'form' => $form->createView(),
            'error' => null
        ]);
    }

    /**
     * @Route("/{slug}", name="course_delete", methods={"DELETE"})
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function delete($slug, Request $request, Course $course, BillingClient $billingClient): Response
    {
        if ($this->isCsrfTokenValid('delete'.$course->getId(), $request->request->get('_token'))) {
            $foundCourse = $this->findOneCombined($slug, $billingClient->getCourseByCode($slug), $billingClient->getTransactionByCode($slug, $this->getUser()->getApiToken()));
            try {
                $deleteResponse = $billingClient->deleteCourse($slug, $this->getUser()->getApiToken());
            } catch (HttpException $e) {
                return $this->render('course/show.html.twig', array(
                    'error' => "Сервис временно недоступен. Попробуйте отредактировать урок позднее",
                    'course' => $foundCourse
                ));
            }
            if (array_key_exists('code', $deleteResponse)) {
                return $this->render('course/show.html.twig', array(
                    'course' => $course,
                    'error' => $deleteResponse['message']
                ));
            } else {
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->remove($course);
                $entityManager->flush();
            }
        }
        return $this->redirectToRoute('course_index');
    }

    /**
     * @Route("/coursepay/{slug}", name="course_pay", methods={"POST"})
     * @IsGranted("ROLE_USER")
     */
    public function buyCourse($slug, BillingClient $billingClient): Response
    {
        try {
            $result = $billingClient->buyCourse($slug, $this->getUser()->getApiToken());
        } catch (HttpException $e) {
            $this->addFlash('error', 'Сервис временно недоступен. Попробуйте удалить урок позднее"');
            return $this->render('course/show.html.twig', [
                'course' => $this->findOneCombined($slug, $billingClient->getCourseByCode($slug), $billingClient->getTransactionByCode($slug, $this->getUser()->getApiToken())),
                'error' => null
            ]);
        }
        if (array_key_exists('success', $result)) {
            $this->addFlash('success', 'Курс успешно оплачен');
            return $this->render('course/show.html.twig', [
                'course' => $this->findOneCombined($slug, $billingClient->getCourseByCode($slug), $billingClient->getTransactionByCode($slug, $this->getUser()->getApiToken())),
                'error' => null
            ]);
        } elseif (array_key_exists('message', $result)) {
            $this->addFlash('error', $result['message']);
            return $this->render('course/show.html.twig', [
                'course' => $this->findOneCombined($slug, $billingClient->getCourseByCode($slug), $billingClient->getTransactionByCode($slug, $this->getUser()->getApiToken())),
                'error' => null
            ]);
        }
    }

    public function findAllCombined($coursesBilling, $userTransactions)
    {
        $coursesStudyOn = $this->getDoctrine()->getRepository(Course::class)->createQueryBuilder('c')
            ->select('c.id', 'c.name', 'c.description', 'c.slug')
            ->getQuery()
            ->getResult();
        $combinedCourses = $this->mergeByCode($coursesStudyOn, $coursesBilling, function ($item1, $item2) {
            return $item1['slug'] == $item2['code'];
        });
        if (isset($userTransactions)) {
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

    public function findOneCombined($slug, $courseBilling, $userTransaction)
    {
        $course = $this->getDoctrine()->getRepository(Course::class)->findOneBy(['slug' => $slug]);
        if (!$course) {
            throw new HttpException(404);
        } else {
            $orderBy = (Criteria::create())->orderBy([
                'number' => Criteria::ASC,
            ]);
            $lessons = ($course->getLessons())->matching($orderBy);
            $courseStudyOn = $this->getDoctrine()->getRepository(Course::class)->createQueryBuilder('c')
                ->select('c.id', 'c.name', 'c.description', 'c.slug')
                ->andWhere('c.slug = :slug')
                ->setParameter('slug', $slug)
                ->getQuery()
                ->getResult();

            $combinedCourse = $this->mergeByCode($courseStudyOn, $courseBilling, function ($item1, $item2) {
                return $item1['slug'] == $item2['code'];
            });
            $combinedCourse[0]['lessons'] = $lessons;
            if (isset($userTransaction)) {
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