<?php

namespace App\Tests;

use App\Tests\AbstractTest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\DataFixtures\CourseFixtures;
use App\Tests\mock\BillingClientMock;

class CourseControllerTest extends AbstractTest
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

    public function testIndexPage()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

//    public function testAdminNewCourse()
//    {
//        $client = $this->authClient('adminUser@gmail.com', 'passwordForAdminUser');
//        $crawler = $client->request('GET', '/courses/');
//        $client->clickLink('Новый курс');
//        $this->assertSame(200, $client->getResponse()->getStatusCode());
//    }

    public function testShowCourse()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses/');
        $client->clickLink('Пройти курс');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

//    public function testEditCourse()
//    {
//        $client = $this->authClient('adminUser@gmail.com', 'passwordForAdminUser');
//        $crawler = $client->request('GET', '/courses/');
//        $client->clickLink('Пройти курс');
//        $this->assertSame(200, $client->getResponse()->getStatusCode());
//        $client->clickLink('Редактировать курс');
//        $this->assertEquals(200, $client->getResponse()->getStatusCode());
//    }

    public function testCountCourses()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertEquals(5, $crawler->filter('.card-title')->count());
    }

    public function testCourse404()
    {
        $client = static::createClient();
        $client->request('GET', '/courses/notexist');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testCourseEdit404()
    {
        $client = $this->authClient('adminUser@gmail.com', 'passwordForAdminUser');
        $client->request('GET', '/courses/notexist/edit');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testNewLessonPage()
    {
        $client = $this->authClient('adminUser@gmail.com', 'passwordForAdminUser');
        $crawler = $client->request('GET', '/courses/');
        $client->clickLink('Пройти курс');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $client->clickLink('Добавить урок');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

//    public function testDeleteCourse()
//    {
//        $client = $this->authClient('adminUser@gmail.com', 'passwordForAdminUser');
//        $crawler = $client->request('GET', '/courses/');
//        $link = $crawler->filter('.row .col-md-6 a')->eq(3)->link();
//        $crawler = $client->click($link);
//        $this->assertSame(200, $client->getResponse()->getStatusCode());
//        $this->assertSame('Качество кода', $crawler->filter('h1')->text());
//        $client->submitForm('Удалить');
//        $crawler = $client->followRedirect();
//        $this->assertSame('Курсы', $crawler->filter('h1')->text());
//        $this->assertCount(4, $crawler->filter('.row .col-md-6'));
//        $this->assertSame(200, $client->getResponse()->getStatusCode());
//    }

//    public function testAddCourse()
//    {
//        $client = $this->authClient('adminUser@gmail.com', 'passwordForAdminUser');
//        $crawler = $client->request('GET', '/courses/');
//        $crawler = $client->clickLink('Новый курс');
//        $this->assertSame(200, $client->getResponse()->getStatusCode());
//        $this->assertSame('Новый курс', $crawler->filter('h1')->text());
//        $client->submitForm('Сохранить', ['course[name]'=>'my name', 'course[description]'=>'my description']);
//        $crawler = $client->followRedirect();
//        $this->assertSame('Курсы', $crawler->filter('h1')->text());
//        $this->assertCount(6, $crawler->filter('.row .col-md-6'));
//        $this->assertSame(200, $client->getResponse()->getStatusCode());
//    }

//    public function testAdminEditCourse()
//    {
//        $client = $this->authClient('adminUser@gmail.com', 'passwordForAdminUser');
//        $crawler = $client->request('GET', '/courses/');
//        $link = $crawler->filter('a:contains("Пройти курс")')->eq(3)->link();
//        $crawler = $client->click($link);
//        $this->assertSame(200, $client->getResponse()->getStatusCode());
//        $this->assertSame('Качество кода', $crawler->filter('h1')->text());
//        $crawler = $client->clickLink('Редактировать курс');
//        $this->assertSame(200, $client->getResponse()->getStatusCode());
//        $this->assertSame('Качество кода', $crawler->filter('h1')->text());
//        $client->submitForm('Сохранить', ['course[name]'=>'New Course 90909', 'course[description]'=>'New description']);
//        $crawler = $client->followRedirect();
//        $this->assertSame(200, $client->getResponse()->getStatusCode());
//        $this->assertSame('Курсы', $crawler->filter('h1')->text());
//        $this->assertSame('New Course 90909', $crawler->filter('.card-title')->eq(4)->text());
//    }

    public function testTooLongName()
    {
        $client = $this->authClient('adminUser@gmail.com', 'passwordForAdminUser');
        $crawler = $client->request('GET', '/courses/');
        $crawler = $client->clickLink('Новый курс');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Новый курс', $crawler->filter('h1')->text());
        $s = substr( str_shuffle( str_repeat( '0123456789', 100 ) ), 0, 300 );
        $crawler = $client->submitForm('Сохранить', ['course[name]'=>$s, 'course[description]'=>'my description']);
        //print_r($crawler);
        $this->assertSame('Новый курс', $crawler->filter('h1')->text());
        $this->assertCount(0, $crawler->filter('.form-error-message'));
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testNewCourseWithoutTitle()
    {
        $client = $this->authClient('adminUser@gmail.com', 'passwordForAdminUser');
        $crawler = $client->clickLink('Новый курс');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $form = $crawler->selectButton('Сохранить')->form();
        $form["course[name]"] = '';
        $form["course[description]"] = 'description';
        $crawler = $client->submit($form);
        $this->assertTrue($crawler->filter('html:contains("This value should not be blank")')->count() > 0);
    }

    public function testNewCourseWithoutDescription()
    {
        $client = $this->authClient('adminUser@gmail.com', 'passwordForAdminUser');
        $crawler = $client->clickLink('Новый курс');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $form = $crawler->selectButton('Сохранить')->form();
        $form["course[name]"] = 'course name';
        $form["course[description]"] = '';
        $crawler = $client->submit($form);
        $this->assertTrue($crawler->filter('html:contains("This value should not be blank")')->count() > 0);
    }

    public function testAddCourseWithoutLogin()
    {
        $client = static::createClient();
        $client->request('GET', '/courses/new');
        $this->assertTrue($client->getResponse()->isRedirect('/login'));
    }

    public function testUserEditCourse()
    {
        $client = $this->authClient('simpleUser@gmail.com', 'passwordForSimpleUser');
        $client->request('GET', '/courses/');
        $crawler = $client->clickLink('Пройти курс');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame(0,$crawler->filter('Редактировать курс')->count());
    }

    public function testUserDeleteCourse()
    {
        $client = $this->authClient('simpleUser@gmail.com', 'passwordForSimpleUser');
        $client->request('GET', '/courses/');
        $crawler = $client->clickLink('Пройти курс');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame(0,$crawler->filter('Удалить')->count());
    }

    public function testLoginUserAddCourse()
    {
        $client = $this->authClient('simpleUser@gmail.com', 'passwordForSimpleUser');
        $crawler = $client->request('GET', '/courses/new');
        $this->assertTrue($crawler->filter('html:contains("Доступ запрещен!")')->count() > 0);
    }
}