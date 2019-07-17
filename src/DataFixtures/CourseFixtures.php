<?php
namespace App\DataFixtures;

use App\Entity\Lesson;
use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Cocur\Slugify\Slugify;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $course_names = ['Введение в JavaScript',
            'Основы JavaScript',
            'Java для начинающих',
            'Качество кода',
            'Структуры данных'];
        $course_descriptions = ['Про язык JavaScript и окружение для разработки на нём.',
            'Основные кирпичики, из которых состоят скрипты',
            'Изучите основы Java',
            'Умение отладить код и поправить ошибки. Хороший стиль кода.',
            'Изучаем JavaScript: расширенное знакомство со встроенными типами данных, их особенностями.'];
        $lesson_names = [['Справочники и спецификации', 'Редакторы для кода', 'Консоль разработчика'],
            ['Привет, мир!','Внешние скрипты, порядок исполнения','Структура кода'],
            ['JDK и Hello World','Переменные. Примитивные типы данных.','Строки(String) в Java. Ссылочные типы данных.'],
            ['Отладка в браузере Chrome','Советы по стилю кода','Как писать неподдерживаемый код?'],
            ['Введение в методы и свойства','Числа','Строки']];
        for ($i = 0; $i < 5; $i++)
        {
            $course = new Course();
            $slugify = new Slugify();
            $course->setSlug($slugify->slugify($course_names[$i]));
            $course->setName($course_names[$i]);
            $course->setDescription($course_descriptions[$i]);
            $manager->persist($course);
            for ($j = 0; $j < 3; $j++)
            {
                $lesson = new Lesson();
                $lesson->setName($lesson_names[$i][$j]);
                $lesson->setCourse($course);
                $lesson->setNumber($j+1);
                $lesson->setContent("Содержимое ".($j+1));
                $manager->persist($lesson);
            }
        }
        $manager->flush();
    }
}