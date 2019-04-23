<?php
namespace App\DataFixtures;

use App\Entity\Lesson;
use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $course_names = ['NodeJS - The Complete Guide',
            'The Complete Android N Developer Course',
            'Java для начинающих',
            'Полное руководство по Python 3: от новичка до специалиста',
            'Advanced JavaScript Concepts'];
        $course_descriptions = ['Master Node JS, build REST APIs with Node.js, GraphQL APIs, add Authentication, use MongoDB, SQL & much more!',
            'Learn Android App Development with Android 7 Nougat by building real apps including Uber, Whatsapp and Instagram!',
            'Изучите основы Java',
            'Изучи Python 3 с нуля - один из самых популярных языков программирования в мире',
            'Learn modern advanced JavaScript practices and be in the top 10% of JavaScript developers'];
        $lesson_names = [['Introduction', 'What is Node.js?', 'Installing Node.js and Creating our First App '],
        ['What does the course cover?','How To Get All The Free Stuff','Asking Great Questions & Debugging Your Code'],
            ['JDK и Hello World','Переменные. Примитивные типы данных.','Строки(String) в Java. Ссылочные типы данных.'],
            ['Почему Python?','Python с технической точки зрения','Python 2 vs Python 3'],
            ['How To Succeed In This Course','Javascript Engine','Inside the Engine']];
        for ($i = 0; $i < 5; $i++)
        {
            $course = new Course();
            $course->setName($course_names[$i]);
            $course->setDescription($course_descriptions[$i]);
            $manager->persist($course);
            for ($j = 0; $j < 3; $j++)
            {
                $lesson = new Lesson();
                $lesson->setName($lesson_names[$i][$j]);
                $lesson->setCourseID($course);
                $lesson->setNubmer($j+1);
                $lesson->setContent("Содержимое ".($j+1));
                $manager->persist($lesson);
            }

        }
        $manager->flush();
    }
}