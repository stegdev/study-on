<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\DataFixtures\CourseFixtures;
use App\Tests\AbstractTest;
use App\Tests\Mock\BillingClientMock;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SecurityControllerTest extends AbstractTest
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
        return $client;
    }

    public function regClient($email, $password, $repeatPassword)
    {
        $client = static::createClient();
        $client->disableReboot();
        $client->getContainer()->set('App\Service\BillingClient', new BillingClientMock($_ENV['BILLING_HOST']));
        $client->request('GET', '/courses/');
        $client->clickLink('Войти');
        $crawler = $client->clickLink('Зарегистрироваться');
        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form["registration[email]"] = $email;
        $form["registration[password]"] = $password;
        $form["registration[repeatPassword]"] = $repeatPassword;
        $client->submit($form);
        return $client;
    }

    public function fillRegForm($client, $email, $password, $repeatPassword)
    {
        $client->disableReboot();
        $client->getContainer()->set('App\Service\BillingClient', new BillingClientMock($_ENV['BILLING_HOST']));
        $client->request('GET', '/courses/');
        $client->clickLink('Войти');
        $crawler = $client->clickLink('Зарегистрироваться');
        $form = $crawler->selectButton('Зарегистрироваться')->form();
        $form["registration[email]"] = $email;
        $form["registration[password]"] = $password;
        $form["registration[repeatPassword]"] = $repeatPassword;
        return $form;
    }

    public function testAdminLogin()
    {
        $client = $this->authClient('adminUser@gmail.com', 'passwordForAdminUser');
        $client->followRedirect();
        $crawler = $client->clickLink('adminUser@gmail.com');
        $this->assertTrue($crawler->filter('html:contains("Роль: Администратор")')->count() > 0);
    }

    public function testUserLogin()
    {
        $client = $this->authClient('simpleUser@gmail.com', 'passwordForSimpleUser');
        $client->followRedirect();
        $crawler = $client->clickLink('simpleUser@gmail.com');
        $this->assertTrue($crawler->filter('html:contains("Роль: Пользователь")')->count() > 0);
    }
    public function testLoginWrongEmail()
    {
        $client = $this->authClient('user@gmail.com', 'passwordForSimpleUser');
        $crawler = $client->followRedirect();
        $this->assertTrue($crawler->filter('html:contains("Bad credentials, please verify your username and password")')->count() > 0);
    }

    public function testUserLogout()
    {
        $client = $this->authClient('simpleUser@gmail.com', 'passwordForSimpleUser');
        $client->followRedirect();
        $crawler = $client->clickLink('simpleUser@gmail.com');
        $this->assertTrue($crawler->filter('html:contains("Роль: Пользователь")')->count() > 0);
        $client->request('GET', '/courses/');
        $client->clickLink('Выйти');
        $crawler = $client->followRedirect();
        $this->assertTrue($crawler->filter('html:contains("Войти")')->count() > 0);
    }

    public function testRegisterUser()
    {
        $client = $this->regClient('user@gmail.com', '1234567', '1234567');
        $crawler = $client->followRedirect();
        $this->assertTrue($crawler->filter('html:contains("user@gmail.com")')->count() > 0);
    }

    public function testRegShortPassword()
    {
        $client = static::createClient();
        $crawler = $client->submit($this->fillRegForm($client, 'simpleUser@gmail.com', '123', '123'));
        $this->assertTrue($crawler->filter('html:contains("Пароль должен содержать не менее 6 символов")')->count() > 0);
    }

    public function testRegInvalidEmail()
    {
        $client = static::createClient();
        $crawler = $client->submit($this->fillRegForm($client, 'usergmail.com', '1234567', '1234567'));
        $this->assertTrue($crawler->filter('html:contains("Invalid email")')->count() > 0);
    }

}