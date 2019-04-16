<?php

namespace App\Form\DataTransformer;

use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CourseToString implements DataTransformerInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Transforms an object (course) to a string (number).
     *
     * @param  Course|null $issue
     * @return string
     */
    public function transform($course)
    {
        if (null === $course) {
            return '';
        }

        return $course->getId();
    }

    /**
     * Transforms a string (number) to an object (course).
     *
     * @param  string $courseNumber
     * @return Course|null
     * @throws TransformationFailedException if object (course) is not found.
     */
    public function reverseTransform($courseNumber)
    {
        // no issue number? It's optional, so that's ok
        if (!$courseNumber) {
            return;
        }

        $course = $this->entityManager
            ->getRepository(Course::class)
            ->find($courseNumber);

        if (null === $course) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(sprintf(
                'An issue with number "%s" does not exist!',
                $courseNumber
            ));
        }

        return $course;
    }
}