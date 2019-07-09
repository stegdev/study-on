<?php

namespace App\Tests\mock;

use App\Service\BillingClient;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BillingClientMock extends BillingClient
{
    public function sendLoginRequest($username, $password)
    {
        $trueUserName = "simpleUser@gmail.com";
        $trueUserPassword = "passwordForSimpleUser";
        $trueAdminName = "adminUser@gmail.com";
        $trueAdminPassword = "passwordForAdminUser";
        if ($username == $trueUserName && $password == $trueUserPassword) {
            return ['token' => 'someToken', 'roles' => ["ROLE_USER"]];
        } elseif ($username == $trueAdminName && $password == $trueAdminPassword) {
            return ['token' => 'someToken', 'roles' => ["ROLE_SUPER_ADMIN"]];
        } elseif ($username == "throwException@mail.ru") {
            throw new HttpException(500);
        } else {
            return ['code' => 401, 'message' => 'Bad credentials, please verify your username and password'];
        }
    }
    public function sendRegisterRequest($email, $password)
    {
        $trueUserEmail= "simpleUser@gmail.com";
        $trueUserPassword = "passwordForSimpleUser";
        if ($email == $trueUserEmail) {
            return ['code' => 400, 'message' => ["Email already exists"]];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['code' => 400, 'message' => ['Invalid email']];
        } elseif ($email == "throwException@mail.ru") {
            throw new HttpException(500);
        } else {
            return ['token' => 'someToken', 'roles' => ["ROLE_USER"]];
        }
    }
}