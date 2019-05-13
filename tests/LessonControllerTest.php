<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\DataFixtures\CourseFixtures;

class LessonControllerTest extends AbstractTest
{
    public function getFixtures(): array
    {
        return [CourseFixtures::class];
    }

    public function testShowLesson()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $link = $crawler->filter('.card.mt-3 .card-body a')->eq(2)->link();
        $crawler = $client->click($link);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Качество кода', $crawler->filter('h1')->text());

        $link = $crawler->filter('ol li a')->eq(1)->link();
        $crawler = $client->click($link);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Умение отладить код и поправить ошибки. Хороший стиль кода.', $crawler->filter('h1')->text());
    }

    public function netestErrorLesson()
    {
        $client = static::createClient();
        $client->request('GET', '/lessons/123213');

        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }

    public function netestAddLesson()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $link = $crawler->filter('.row .col-sm-5 a')->eq(2)->link();
        $crawler = $client->click($link);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Качество кода', $crawler->filter('h1')->text());

        $crawler = $client->clickLink('Добавить урок');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Создание урока', $crawler->filter('h1')->text());

        $client->submitForm('Save', ['lesson[name]'=>'my name', 'lesson[content]'=>'my content',
            'lesson[number]'=>10]);
        $crawler = $client->followRedirect();

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Качество кода', $crawler->filter('h1')->text());
        $this->assertCount(4, $crawler->filter('ol li a'));
        $this->assertContains('my name', $crawler->filter('ol li a')->eq(3)->text());
    }

    public function netestEditLesson()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $link = $crawler->filter('.row .col-sm-5 a')->eq(2)->link();
        $crawler = $client->click($link);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Качество кода', $crawler->filter('h1')->text());

        $link = $crawler->filter('ol li a')->eq(1)->link();
        $crawler = $client->click($link);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Умение отладить код и поправить ошибки. Хороший стиль кода.', $crawler->filter('h1')->text());

        $crawler = $client->clickLink('Редактировать');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Редактирование Умение отладить код и поправить ошибки. Хороший стиль кода.',
            $crawler->filter('h1')->text());

        $client->submitForm('Update', ['lesson[name]'=>'my name']);
        $crawler = $client->followRedirect();

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('my name', $crawler->filter('h1')->text());

        $crawler = $client->clickLink('Качество кода');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(3, $crawler->filter('ol li a'));
        $this->assertSame('my name', $crawler->filter('ol li a')->eq(1)->text());
    }

    public function netestDeleteLesson()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $link = $crawler->filter('.row .col-sm-5 a')->eq(2)->link();
        $crawler = $client->click($link);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Качество кода', $crawler->filter('h1')->text());

        $link = $crawler->filter('ol li a')->eq(1)->link();
        $crawler = $client->click($link);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Умение отладить код и поправить ошибки. Хороший стиль кода.', $crawler->filter('h1')->text());

        $client->submitForm('Удалить');
        $crawler = $client->followRedirect();

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(2, $crawler->filter('ol li a'));
        $this->assertSame('Качество кода', $crawler->filter('h1')->text());
    }

    public function netestFailAddLesson()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $link = $crawler->filter('.row .col-sm-5 a')->eq(2)->link();
        $crawler = $client->click($link);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Качество кода', $crawler->filter('h1')->text());

        $crawler = $client->clickLink('Добавить урок');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Создание урока', $crawler->filter('h1')->text());

        $crawler = $client->submitForm('Save', ['lesson[name]'=>'', 'lesson[content]'=>'my content',
            'lesson[number]'=>10]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Создание урока', $crawler->filter('h1')->text());
        $this->assertCount(1, $crawler->filter('.form-error-message'));

        $crawler = $client->submitForm('Save', ['lesson[name]'=>'my name', 'lesson[content]'=>'my content',
            'lesson[number]'=>1111111]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Создание урока', $crawler->filter('h1')->text());
        $this->assertCount(1, $crawler->filter('.form-error-message'));
        $this->assertSame('Номер не может быть больше 10000', $crawler->filter('.form-error-message')->text());
    }
}