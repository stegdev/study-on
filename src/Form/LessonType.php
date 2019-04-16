<?php

namespace App\Form;

use App\Entity\Lesson;
use App\Form\DataTransformer\CourseToString;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class LessonType extends AbstractType
{
    private $transformer;

    public function __construct(CourseToString $transformer)
    {
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('Name')
            ->add('Content')
            ->add('Nubmer')
            ->add('CourseID', HiddenType::class)
        ;

        $builder->get('CourseID')->addModelTransformer($this->transformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
        ]);
    }
}
