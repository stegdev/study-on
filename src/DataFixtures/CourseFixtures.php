<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\Course;
use App\Entity\Lesson;
use Cocur\Slugify\Slugify;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $courseNames = ['Введение в JavaScript',
            'Основы JavaScript',
            'Java для начинающих',
            'Качество кода',
            'Структуры данных'];
        $courseContent = ['Про язык JavaScript и окружение для разработки на нём.',
            'Основные кирпичики, из которых состоят скрипты',
            'Изучите основы Java',
            'Умение отладить код и поправить ошибки. Хороший стиль кода.',
            'Изучаем JavaScript: расширенное знакомство со встроенными типами данных, их особенностями.'];

        $lessonName = [['Справочники и спецификации', 'Редакторы для кода', 'Консоль разработчика'],
            ['Привет, мир!','Внешние скрипты, порядок исполнения','Структура кода'],
            ['JDK и Hello World','Переменные. Примитивные типы данных.','Строки(String) в Java. Ссылочные типы данных.'],
            ['Отладка в браузере Chrome','Советы по стилю кода','Как писать неподдерживаемый код?'],
            ['Введение в методы и свойства','Числа','Строки']];
        for ($i = 0; $i < count($courseNames); $i++) {
            $course = new Course();
            $slugify = new Slugify();
            $course->setSlug($slugify->slugify($courseNames[$i]));
            $course->setName($courseNames[$i]);
            $course->setDescription($courseContent[$i]);
            $manager->persist($course);
        }
        $manager->flush();
        $courses = $manager->getRepository(Course::class)->findAll();
        for ($i = 0; $i < count($courses); $i++) {
            $course = $manager->getRepository(Course::class)->find($courses[$i]->getId());
            for ($j = 0; $j < 3; $j++)
            {
                $lesson = new Lesson();
                $lesson->setName($lessonName[$i][$j]);
                $lesson->setCourse($course);
                $lesson->setNumber($j+1);
                $lesson->setContent("Содержимое ".($j+1));
                $manager->persist($lesson);
            }
        }
        $manager->flush();
    }
}