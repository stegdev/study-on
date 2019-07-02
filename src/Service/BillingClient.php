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
        return $this->execCurl(json_encode(['username' => $username, 'password' => $password]), '/api/v1/auth');
    }
    public function sendRegisterRequest($email, $password)
    {
        return $this->execCurl(json_encode(['email' => $email, 'password' => $password]), '/api/v1/register');
    }
    public function execCurl($payload, $route)
    {
        $ch = curl_init($this->billingHost . $route);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($payload)));
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