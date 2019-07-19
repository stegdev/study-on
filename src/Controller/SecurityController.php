<?php
namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\RegistrationType;
use App\Repository\CourseRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Service\BillingClient;
use App\Security\BillingUser;
use App\Security\StudyOnAuthenticator;
class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }
    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request, StudyOnAuthenticator $authenticator, GuardAuthenticatorHandler $guardHandler, BillingClient $billingClient): Response
    {
        $auth_checker = $this->get('security.authorization_checker');
        if ($auth_checker->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('profile');
        } else {
            $form = $this->createForm(RegistrationType::class);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $formData = $form->getData();
                if (trim($formData['password']) != trim($formData['repeatPassword'])) {
                    return $this->render('security/register.html.twig', array(
                        'form' => $form->createView(),
                        'error' => "Passwords must be the same"
                    ));
                } else {
                    try {
                        $regResponse = $billingClient->sendRegisterRequest(trim($formData['email']), trim($formData['password']));
                    } catch (HttpException $ex) {
                        return $this->render('security/register.html.twig', array(
                            'form' => $form->createView(),
                            'error' => "Сервис временно недоступен. Попробуйте зарегистрироваться позднее"
                        ));
                    }
                    if (array_key_exists('code', $regResponse)) {
                        return $this->render('security/register.html.twig', array(
                            'form' => $form->createView(),
                            'error' => $regResponse['message']
                        ));
                    } else {
                        $user = new BillingUser();
                        $user->setEmail(trim($formData['email']));
                        $user->setRefreshToken($regResponse['refresh_token']);
                        $user->setApiToken($regResponse['token']);
                        $user->setRoles($regResponse['roles']);
                        return $guardHandler->authenticateUserAndHandleSuccess(
                            $user,
                            $request,
                            $authenticator,
                            'main'
                        );
                    }
                }
            }
            return $this->render('security/register.html.twig', array(
                'form' => $form->createView(),
                'error' => null
            ));
        }
    }
    /**
     * @Route("/profile", name="profile", methods={"GET"})
     * @IsGranted("ROLE_USER")
     */
    public function profile(BillingClient $billingClient): Response
    {
        return $this->render('security/profile.html.twig', array('balance' => $billingClient->getCurentUserBalance($this->getUser()->getApiToken())));
    }
    /**
     * @Route("/transactions", name="transactions", methods={"GET"})
     * @IsGranted("ROLE_USER")
     */
    public function transactions(CourseRepository $courseRepository, BillingClient $billingClient): Response
    {
        // dump($courseRepository->getAllSlugs());
        // die;
        return $this->render('security/transactions.html.twig', array('transactions' => $billingClient->getAllTransactions($this->getUser()->getApiToken()), 'slugs' => $courseRepository->getAllSlugs()));
    }
    /**
     * @Route("/logout", name="app_logout", methods={"GET"})
     */
    public function logout()
    {
    }
}