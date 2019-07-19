<?php

namespace App\Tests\Mock;

use App\Service\BillingClient;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BillingClientMock extends BillingClient
{
    private $coursesResponse;
    private $transactionsResponse;

    public function __construct()
    {
        $this->coursesResponse = json_decode(file_get_contents(__DIR__ ."/courses.json"), true);
        $this->transactionsResponse = json_decode(file_get_contents(__DIR__ ."/transactions.json"), true);
    }

    public function createToken($username)
    {
        $tokenHeader = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.';
        $tokenSignature = '.jBqzJ_BWOs7_nXFncCtM_PNiLVIgoZqaXtsa0bmUV5sHfUbZczuZxLsW6jsuSK4soLAP3qmlUoQOLJvD9xrgMXTxTtGWsCwQ7a8qcBoQVUqlWQtwhDBDWTKkt48GmodXLUe7iIs08st31E3C6ly6DjZZvLFvfBuh3G7kcHWGsKpPZLiuX8BCiMZbgXFzeBEEObXNaFZn6DTx6NYBTt5kB6CpTbraogO30T2sxkXt2n8g-7RhQjb2dAdFg5FQx6k75W07lXqFkHleVCSXgRAgEJ_8eA1WfkuNWf2NGJLUsfAZTjPNGFuIjWl3bFhimkG8CeihqyNhjKrxUPfgmnMRJMTGE37_WPq4emAkSmb0SoxvKsqi9VTxzpyrOv6bN6BIuk6pCpwPRA6nHMXC2TL0AyMPh0ZeOWnjX9hhUdaS0G_asXDLXw7lVWoRH3BTmDv8fYq8obVwG4M6ojvdtuqiexmbT0JSodP241QhvUNIIQS2DyQ3m2GL2nHLuFs2FDRjiE8b4vWeCpzNCXnjiluB_OWU3ar7BCdMV5M49sBWX51WAHX8x7QyqC5zuoVBM8Rd1k-nMe_5v2BMpx-BRYhoos5Kh9jyCDLL8OcQCNXG2TLJBuGYPBH2_On1iGWXcRQZWhOCA7SntovUyYf7Z3IgUXZ4R4Hd-XMdxq5tCxKCntk';

        $payload = new \stdClass();
        $payload->iat = (new \DateTime())->getTimestamp();
        $payload->exp = ((new \DateTime())->modify('+1 week'))->getTimestamp();
        $payload->username = $username;

        if ($username == 'adminUser@gmail.com') {
            $payload->roles = ['ROLE_USER', 'ROLE_SUPER_ADMIN'];
        } elseif ($username == 'simpleUser@gmail.com') {
            $payload->roles = ['ROLE_USER'];
        }
        $base64Payload = base64_encode(json_encode($payload));
        $finalToken = $tokenHeader . $base64Payload . $tokenSignature;
        return $finalToken;
    }

    public function sendLoginRequest($username, $password)
    {
        $trueUserName = "simpleUser@gmail.com";
        $trueUserPassword = "passwordForSimpleUser";
        $trueAdminName = "adminUser@gmail.com";
        $trueAdminPassword = "passwordForAdminUser";
        if ($username == $trueUserName && $password == $trueUserPassword) {
            return ['token' => $this->createToken($username), 'roles' => ["ROLE_USER"], 'refresh_token' => '6c511dd5e0e2ad0ae0dbd82aa1a89160385a611f622cd1d6e9908713e0f24f38d0c3189f5c1e8b419412391a6e8680c7035033d3c0f2ed7f6b192b5fdfa265be'];
        } elseif ($username == $trueAdminName && $password == $trueAdminPassword) {
            return ['token' => $this->createToken($username), 'roles' => ["ROLE_USER", "ROLE_SUPER_ADMIN"], 'refresh_token' => 'ed2e20e571a9284abb0c6cc8d00a2abc01bd2dcc9f702024733aa941b6be31c91fbdcb9e348b372be32f64c90c28cd52b175a8d44aa13fbff9ae70a26485ce10'];
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
            return ['token' => $this->createToken($email), 'roles' => ["ROLE_USER"], 'refresh_token' => 'refreshToken'];
        }
    }

    public function sendRefreshRequest($refreshToken)
    {
        return ['token' => $this->createToken('user@gmail.com')];
    }
    public function getCurentUserBalance($token)
    {
        if (isset($token)) {
            return 1000;
        }
    }

    public function getCourses()
    {
        return $this->coursesResponse;
    }
    public function getCourseByCode($slug)
    {
        return array_filter($this->coursesResponse, function ($var) use ($slug) {
            return ($var['code'] == $slug);
        });
    }

    public function buyCourse($slug, $token)
    {
        if (isset($token)) {
            if ($slug == 'mern-stack-front-to-back-full-stack-react-redux-node-js') {
                return ["success" => true, "course_type" => "rent", "exrires_at" => "2019-06-24T12:56:54+00:00"];
            } elseif ($slug == 'build-a-blockchain-and-a-cryptocurrency-from-scratch') {
                return ["success" => true, "course_type" => "buy", "exrires_at" => "2019-06-24T12:55:45+00:00"];
            }
        }
    }

    public function getPaymentTransactions($token)
    {
        if (isset($token)) {
            return array_filter($this->transactionsResponse, function ($var) {
                return ($var['type'] == 'payment');
            });
        }
    }

    public function getAllTransactions($token)
    {
        if (isset($token)) {
            return $this->transactionsResponse;
        }
    }

    public function getTransactionByCode($slug, $token)
    {
        if (isset($token)) {
            $result = [];
            foreach ($this->transactionsResponse as $transaction) {
                if (array_key_exists('course_code', $transaction)) {
                    if ($transaction['course_code'] == $slug) {
                        array_push($result, $transaction);
                    }
                }
            }
            return $result;
        }
    }
}