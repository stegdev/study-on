<?php
namespace App\Tests;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\DataFixtures\CourseFixtures;
class CourseControllerTest extends AbstractTest
{
    public function getFixtures(): array
    {
        return [CourseFixtures::class];
    }
    public function testIndexResponse()
    {
        $client = static::createClient();
        $client->request('GET', '/courses/');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testShowCourse()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $link = $crawler->filter('a:contains("Пройти курс")')->eq(3)->link();
        $crawler = $client->click($link);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Качество кода', $crawler->filter('h1')->text());
    }
    public function testErrorCourse()
    {
        $client = static::createClient();
        $client->request('GET', '/courses/123213123');
        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }
    public function testCountCourses()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertCount(5, $crawler->filter('.row .col-md-6'));
    }
    public function testDeleteCourse()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $link = $crawler->filter('.row .col-md-6 a')->eq(3)->link();
        $crawler = $client->click($link);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Качество кода', $crawler->filter('h1')->text());
        $client->submitForm('Удалить');
        $crawler = $client->followRedirect();
        $this->assertSame('Курсы', $crawler->filter('h1')->text());
        $this->assertCount(4, $crawler->filter('.row .col-md-6'));
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }
    public function testAddCourse()
    {
        $client = static::createClient();
        $client->request('GET', '/');
        $crawler = $client->clickLink('Новый курс');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Новый курс', $crawler->filter('h1')->text());
        $client->submitForm('Сохранить', ['course[name]'=>'my name', 'course[description]'=>'my description']);
        $crawler = $client->followRedirect();
        $this->assertSame('Курсы', $crawler->filter('h1')->text());
        $this->assertCount(6, $crawler->filter('.row .col-md-6'));
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }
    public function testEditCourse()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $link = $crawler->filter('a:contains("Пройти курс")')->eq(3)->link();
        $crawler = $client->click($link);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Качество кода', $crawler->filter('h1')->text());
        $crawler = $client->clickLink('Редактировать курс');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Качество кода', $crawler->filter('h1')->text());
        $client->submitForm('Сохранить', ['course[name]'=>'New Course 90909', 'course[description]'=>'New description']);
        $crawler = $client->followRedirect();
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Курсы', $crawler->filter('h1')->text());
        $this->assertSame('New Course 90909', $crawler->filter('.card-title')->eq(4)->text());
    }
    public function testTooLongName()
    {
        $client = static::createClient();
        $client->request('GET', '/');
        $crawler = $client->clickLink('Новый курс');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Новый курс', $crawler->filter('h1')->text());
        $s = substr( str_shuffle( str_repeat( '0123456789', 100 ) ), 0, 300 );
        $crawler = $client->submitForm('Сохранить', ['course[name]'=>$s, 'course[description]'=>'my description']);
        //print_r($crawler);
        $this->assertSame('Новый курс', $crawler->filter('h1')->text());
        $this->assertCount(1, $crawler->filter('.form-error-message'));
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }
    public function testCourse404()
    {
        $client = static::createClient();
        $client->request('GET', '/courses/25');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }
    public function testCourseEdit404()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $link = $crawler->filter('a:contains("Пройти курс")')->eq(3)->link();
        $crawler = $client->click($link);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Качество кода', $crawler->filter('h1')->text());
        $crawler = $client->clickLink('Редактировать');
        $client->request('GET', '/courses/25/edit');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }
}