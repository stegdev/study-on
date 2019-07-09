<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\DataFixtures\CourseFixtures;
use App\Tests\AbstractTest;
use App\Tests\Mock\BillingClientMock;
use App\Entity\Course;
class LessonControllerTest extends AbstractTest
{
    public function getFixtures(): array
    {
        return [CourseFixtures::class];
    }

    public function authClient($email, $password)
    {
        $client = static::createClient();
        $client->disableReboot();
        $client->getContainer()->set('App\Service\BillingClient', new BillingClientMock($_ENV['BILLING_HOST']));
        $client->request('GET', '/courses/');
        $crawler = $client->clickLink('Войти');
        $form = $crawler->selectButton('Войти')->form();
        $form["email"] = $email;
        $form["password"] = $password;
        $client->submit($form);
        $client->followRedirect();
        return $client;
    }

    public function testShowLesson()
    {
        $client = $this->authClient('adminUser@gmail.com', 'passwordForAdminUser');
        $crawler = $client->request('GET', '/courses/');
        $link = $crawler->filter('a:contains("Пройти курс")')->eq(3)->link();
        $crawler = $client->click($link);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Качество кода', $crawler->filter('h1')->text());
        $link = $crawler->filter('ol li a')->eq(1)->link();
        $crawler = $client->click($link);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Советы по стилю кода', $crawler->filter('h2')->text());
    }

    public function testErrorLesson()
    {
        $client = $this->authClient('adminUser@gmail.com', 'passwordForAdminUser');
        $client->request('GET', '/lessons/123213');
        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }

    public function testAddLesson()
    {
        $client = $this->authClient('adminUser@gmail.com', 'passwordForAdminUser');
        $crawler = $client->request('GET', '/courses/');
        $link = $crawler->filter('a:contains("Пройти курс")')->eq(3)->link();
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
        $this->assertCount(3, $crawler->filter('ol li a'));
    }

    public function testEditLesson()
    {
        $client = $this->authClient('adminUser@gmail.com', 'passwordForAdminUser');
        $crawler = $client->request('GET', '/courses/');
        $link = $crawler->filter('a:contains("Пройти курс")')->eq(3)->link();
        $crawler = $client->click($link);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Качество кода', $crawler->filter('h1')->text());
        $link = $crawler->filter('ol li a')->eq(1)->link();
        $crawler = $client->click($link);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Советы по стилю кода', $crawler->filter('h2')->text());
        $crawler = $client->clickLink('Редактировать');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Советы по стилю кода',
            $crawler->filter('h1')->text());
        $client->submitForm('Сохранить', ['lesson[name]'=>'my name']);
        $crawler = $client->followRedirect();
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('my name', $crawler->filter('h2')->text());
        $crawler = $client->clickLink('Качество кода');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(3, $crawler->filter('ol li a'));
        $this->assertSame('my name', $crawler->filter('ol li a')->eq(1)->text());
    }

    public function testDeleteLesson()
    {
        $client = $this->authClient('adminUser@gmail.com', 'passwordForAdminUser');
        $crawler = $client->request('GET', '/courses/');
        $link = $crawler->filter('a:contains("Пройти курс")')->eq(3)->link();
        $crawler = $client->click($link);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Качество кода', $crawler->filter('h1')->text());
        $link = $crawler->filter('ol li a')->eq(1)->link();
        $crawler = $client->click($link);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Советы по стилю кода', $crawler->filter('h2')->text());
        $client->submitForm('Удалить');
        $crawler = $client->followRedirect();
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(2, $crawler->filter('ol li a'));
        $this->assertSame('Качество кода', $crawler->filter('h1')->text());
    }

    public function testNotBlank()
    {
        $client = $this->authClient('adminUser@gmail.com', 'passwordForAdminUser');
        $crawler = $client->request('GET', '/courses/');
        $link = $crawler->filter('a:contains("Пройти курс")')->eq(3)->link();
        $crawler = $client->click($link);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Качество кода', $crawler->filter('h1')->text());
        $crawler = $client->clickLink('Добавить урок');
        $this->assertSame('Создание урока', $crawler->filter('h1')->text());
        $crawler = $client->submitForm('Save', ['lesson[name]'=>'', 'lesson[content]'=>'Content',
            'lesson[number]'=>20]);
        $this->assertCount(0, $crawler->filter('.form-error-message'));
        $crawler = $client->submitForm('Save', ['lesson[name]'=>'Name', 'lesson[content]'=>'Content',
            'lesson[number]'=>100000]);
        $this->assertCount(0, $crawler->filter('.form-error-message'));
        $s = substr( str_shuffle( str_repeat( '0123456789', 100 ) ), 0, 300 );
        $crawler = $client->submitForm('Save', ['lesson[name]'=>$s, 'lesson[content]'=>'Content',
            'lesson[number]'=>10]);
        $this->assertCount(0, $crawler->filter('.form-error-message'));
    }

    public function testLessonAddWithWrongNumber()
    {
        $client = $this->authClient('adminUser@gmail.com', 'passwordForAdminUser');
        $client->clickLink('Пройти курс');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $crawler = $client->clickLink('Добавить урок');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $form = $crawler->selectButton('Save')->form();
        $form["lesson[name]"] = "Новый урок";
        $form["lesson[content]"] = "Описание нового урока";
        $form["lesson[number]"] = 100000;
        $crawler = $client->submit($form);
        $this->assertTrue($crawler->filter('html:contains("Номер не может быть больше 10000")')->count() > 0);
    }

    public function testAddNewLessonNotUser()
    {
        $client = static::createClient();
        $client->request('GET', '/lessons/new');
        $this->assertTrue($client->getResponse()->isRedirect('/login'));
    }

    public function testAddNewLessonUser()
    {
        $client = $this->authClient('simpleUser@gmail.com', 'passwordForSimpleUser');
        $crawler = $client->request('GET', '/lessons/new');
        $this->assertTrue($crawler->filter('html:contains("Доступ запрещен!")')->count() > 0);
    }

    public function testEditLessonUser()
    {
        $client = $this->authClient('simpleUser@gmail.com', 'passwordForSimpleUser');
        $client->request('GET', '/courses/');
        $crawlerCourse = $client->clickLink('Пройти курс');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $countOfLessonsBefore = $crawlerCourse->filter('.btn-link')->count();
        $addButtonCheck = $crawlerCourse->filter('Редактировать')->count();
        $this->assertSame(0,$addButtonCheck);
    }
    public function testDeleteLessonUser()
    {
        $client = $this->authClient('simpleUser@gmail.com', 'passwordForSimpleUser');
        $client->request('GET', '/courses/');
        $crawlerCourse = $client->clickLink('Пройти курс');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $countOfLessonsBefore = $crawlerCourse->filter('.btn-link')->count();
        $addButtonCheck = $crawlerCourse->filter('удалить')->count();
        $this->assertSame(0,$addButtonCheck);
    }
}