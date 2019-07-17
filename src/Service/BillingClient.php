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
        $result = curl_exec($ch);
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