<?php
namespace App\Service;
use Symfony\Component\HttpKernel\Exception\HttpException;
class BillingClient
{
    private $billingHost;

    public function __construct($billingHost)
    {
        $this->billingHost = $billingHost;
    }

    public function sendLoginRequest($username, $password)
    {
        return $this->execCurl("POST", json_encode(['username' => $username, 'password' => $password]), '/api/v1/auth', '');
    }

    public function sendRegisterRequest($email, $password)
    {
        return $this->execCurl("POST", json_encode(['email' => $email, 'password' => $password]), '/api/v1/register', '');
    }

    public function getCurentUserBalance($token)
    {
        $user =  $this->execCurl("GET", '', '/api/v1/users/current', $token);
        return $user['balance'];
    }

    public function sendRefreshRequest($refreshToken)
    {
        return $this->execCurl("POST", json_encode(['refresh_token' => $refreshToken]), '/api/v1/token/refresh', '');
    }

    public function getCourses()
    {
        return $this->execCurl("GET", '', '/api/v1/courses', '');
    }

    public function getCourseByCode($slug)
    {
        return $this->execCurl("GET", '', '/api/v1/courses/'. $slug, '');
    }

    public function buyCourse($slug, $token)
    {
        return $this->execCurl('POST', '', '/api/v1/courses/'.$slug.'/pay', $token);
    }

    public function getPaymentTransactions($token)
    {
        try {
            return $this->execCurl('GET', '', '/api/v1/transactions?type=payment&skip_expired=true', $token);
        } catch (HttpException $e) {
            return '';
        }
    }

    public function getTransactionByCode($slug, $token)
    {
        try {
            return $this->execCurl('GET', '', '/api/v1/transactions?skip_expired=true&course_code='.$slug, $token);
        } catch (HttpException $e) {
            return '';
        }
    }

    public function getAllTransactions($token)
    {
        return $this->execCurl('GET', '', '/api/v1/transactions', $token);
    }

    public function decodePayload($token)
    {
        $tokenParts = explode(".", $token);
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtPayload = json_decode($tokenPayload);
        return $jwtPayload;
    }

    public function execCurl($method, $payload, $route, $token)
    {
        $ch = curl_init($this->billingHost . $route);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        if ($method == "POST") {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer ' . $token));
        try {
            $result = curl_exec($ch);
        } catch (HttpException $e) {
            throw new HttpException(503);
        }
        if ($result === false) {
            throw new HttpException(503, curl_error($ch));
        } else {
            $parsedResult = json_decode($result, true);
            if ($parsedResult == null) {
                throw new HttpException(500, "Invalid JSON");
            } else {
                return $parsedResult;
            }
        }
        curl_close($ch);
    }
}