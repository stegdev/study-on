<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LessonRepository")
 */
class Lesson
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Course", inversedBy="Lessons")
     * @ORM\JoinColumn(nullable=false)
     */
    private $CourseID;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $Name;

    /**
     * @ORM\Column(type="text")
     */
    private $Content;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Range(min = 1, max = 10000,
     * minMessage = "Номер не может быть отрицательным",
     * maxMessage = "Номер не может быть больше 10000")
     */
    private $Nubmer;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCourseID(): ?Course
    {
        return $this->CourseID;
    }

    public function setCourseID(?Course $CourseID): self
    {
        $this->CourseID = $CourseID;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->Name;
    }

    public function setName(string $Name): self
    {
        $this->Name = $Name;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->Content;
    }

    public function setContent(string $Content): self
    {
        $this->Content = $Content;

        return $this;
    }

    public function getNubmer(): ?int
    {
        return $this->Nubmer;
    }

    public function setNubmer(?int $Nubmer): self
    {
        $this->Nubmer = $Nubmer;

        return $this;
    }
}
