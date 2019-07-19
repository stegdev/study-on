<?php

namespace App\Controller;

use App\Entity\Course;
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

/**
 * @Route("/courses")
 */
class CourseController extends AbstractController
{
    /**
     * @Route("/", name="course_index", methods={"GET"})
     */
    public function index(CourseRepository $courseRepository, BillingClient $billingClient): Response
    {
        return $this->render('course/index.html.twig', [
            'courses' => $courseRepository->findAllCombined($billingClient, $this->getUser())
        ]);
    }
    /**
     * @Route("/new", name="course_new", methods={"GET","POST"})
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function new(Request $request): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($course);
            $entityManager->flush();
            return $this->redirectToRoute('course_index');
        }
        return $this->render('course/new.html.twig', [
            'course' => $course,
            'form' => $form->createView(),
        ]);
    }
    /**
     * @Route("/{slug}", name="course_show", methods={"GET"})
     */
    public function show($slug, CourseRepository $courseRepository, BillingClient $billingClient): Response
    {
        $auth_checker = $this->get('security.authorization_checker');
        if ($auth_checker->isGranted('ROLE_USER')) {
            return $this->render('course/show.html.twig', [
                'course' => $courseRepository->findOneCombined($slug, $billingClient, $this->getUser()),
                'user_balance' => $billingClient->getCurentUserBalance($this->getUser()->getApiToken())
            ]);
        } else {
            return $this->render('course/show.html.twig', [
                'course' => $courseRepository->findOneCombined($slug, $billingClient, null)
            ]);
        }
    }
    /**
     * @Route("/{slug}/edit", name="course_edit", methods={"GET","POST"})
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function edit(Request $request, Course $course): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('course_index');
        }
        return $this->render('course/edit.html.twig', [
            'course' => $course,
            'form' => $form->createView(),
        ]);
    }
    /**
     * @Route("/{slug}", name="course_delete", methods={"DELETE"})
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function delete(Request $request, Course $course): Response
    {
        if ($this->isCsrfTokenValid('delete'.$course->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($course);
            $entityManager->flush();
        }
        return $this->redirectToRoute('course_index');
    }
    /**
     * @Route("/coursepay/{slug}", name="course_pay", methods={"POST"})
     * @IsGranted("ROLE_USER")
     */
    public function buyCourse($slug, BillingClient $billingClient, CourseRepository $courseRepository): Response
    {
        $result = $billingClient->buyCourse($slug, $this->getUser()->getApiToken());
        if (array_key_exists('success', $result)) {
            $this->addFlash('success', 'Курс успешно оплачен');
            return $this->render('course/show.html.twig', [
                'course' => $courseRepository->findOneCombined($slug, $billingClient, $this->getUser())
            ]);
        } elseif (array_key_exists('message', $result)) {
            $this->addFlash('error', $result['message']);
            return $this->render('course/show.html.twig', [
                'course' => $courseRepository->findOneCombined($slug, $billingClient, $this->getUser())
            ]);
        }
    }
}