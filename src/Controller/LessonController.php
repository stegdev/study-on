<?php

namespace App\Controller;

use App\Entity\Lesson;
use App\Entity\Course;
use App\Form\LessonType;
use App\Service\BillingClient;
use App\Repository\LessonRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Annotation\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/lessons")
 */
class LessonController extends AbstractController
{
    /**
     * @Route("/new", name="lesson_new", methods={"GET","POST"})
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function new(Request $request): Response
    {
        $courseId = $request->query->get('course_id');
        if ($courseId) {
            $course = $this->getDoctrine()->getManager()->getRepository(Course::class)->find($courseId);
            if ($course) {
                $lesson = new Lesson();
                $lesson->setCourse($course);
                $form = $this->createForm(LessonType::class, $lesson);
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($lesson);
                    $course->addLesson($lesson);
                    $entityManager->persist($course);
                    $entityManager->flush();
                    $response = $this->redirectToRoute('course_show', ['slug' => $course->getSlug()]);
                    return $response;
                }
            } else {
                return $this->render('error404.html.twig');
            }
        } else {
            return $this->render('error404.html.twig');
        }
        return $this->render('lesson/new.html.twig', [
            'lesson' => $lesson,
            'form' => $form->createView()
        ]);
    }
    /**
     * @Route("/{id}", name="lesson_show", requirements={"id"="\d{1,10}"}, methods={"GET"})
     * @IsGranted("ROLE_USER")
     */
    public function show(Lesson $lesson, LessonRepository $repository, BillingClient $billingClient): Response
    {
        $id = $lesson->getCourse()->getId();
        $course = $this->getDoctrine()->getRepository(Course::class)->findOneBy(['id' => $id]);
        if (!$course) {
            throw new HttpException(404, 'Course not found');
        } else {
            $slug = $course->getSlug();
            $parentCourse = $this->getDoctrine()->getRepository(Course::class)->findOneCombined($slug, $billingClient->getCourseByCode($slug), $billingClient->getTransactionByCode($slug, $this->getUser()->getApiToken()));

            if ((($parentCourse['type'] == 'rent' || $parentCourse['type'] == 'buy') && array_key_exists('transaction_type', $parentCourse)) || $parentCourse['type'] == 'free' || \in_array("ROLE_SUPER_ADMIN", $this->getUser()->getRoles())) {
                return $this->render('lesson/show.html.twig', [
                    'lesson' => $lesson
                ]);
            } else {
                throw new HttpException(403);
            }
        }
    }
    /**
     * @Route("/{id}/edit", name="lesson_edit", methods={"GET","POST"})
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function edit(Request $request, Lesson $lesson): Response
    {
        $form = $this->createForm(LessonType::class, $lesson);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('lesson_show', [
                'id' => $lesson->getId(),
            ]);
        }
        return $this->render('lesson/edit.html.twig', [
            'lesson' => $lesson,
            'form' => $form->createView(),
        ]);
    }
    /**
     * @Route("/{id}", name="lesson_delete", methods={"DELETE"})
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function delete(Request $request, Lesson $lesson): Response
    {
        if ($this->isCsrfTokenValid('delete'.$lesson->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($lesson);
            $entityManager->flush();
        }
        $response = $this->forward('App\Controller\CourseController::show', [
            'slug'  => $lesson->getCourse()->getSlug()
        ]);
        return $response;
    }
}