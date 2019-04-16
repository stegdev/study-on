<?php
namespace App\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CourseRepository")
 */
class Course
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $Name;
    /**
     * @ORM\Column(type="string", length=1000, nullable=true)
     */
    private $Description;
    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Lesson", mappedBy="CourseID", orphanRemoval=true)
     * @ORM\OrderBy({"Nubmer"="ASC"})
     */
    private $Lessons;
    public function __construct()
    {
        $this->Lessons = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
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
    public function getDescription(): ?string
    {
        return $this->Description;
    }
    public function setDescription(?string $Description): self
    {
        $this->Description = $Description;
        return $this;
    }
    /**
     * @return Collection|Lesson[]
     */
    public function getLessons(): Collection
    {
        return $this->Lessons;
    }
    public function addLesson(Lesson $lesson): self
    {
        if (!$this->Lessons->contains($lesson)) {
            $this->Lessons[] = $lesson;
            $lesson->setCourseID($this);
        }
        return $this;
    }
    public function removeLesson(Lesson $lesson): self
    {
        if ($this->Lessons->contains($lesson)) {
            $this->Lessons->removeElement($lesson);
            // set the owning side to null (unless already changed)
            if ($lesson->getCourseID() === $this) {
                $lesson->setCourseID(null);
            }
        }
        return $this;
    }
}