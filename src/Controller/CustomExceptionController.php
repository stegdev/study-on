<?php
namespace App\Controller;
use Symfony\Bundle\TwigBundle\Controller\ExceptionController;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Twig\Environment;
class CustomExceptionController extends ExceptionController
{
    protected $debug;
    protected $twig;
    /**
     * @param Environment $twig
     * @param bool        $debug Show error (false) or exception (true) pages by default
     */
    public function __construct(Environment $twig, bool $debug)
    {
        $this->twig = $twig;
        $this->debug = $debug;
    }
    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        $template =  $exception->getStatusCode() . '.html.twig';
        return new Response($this->twig->render($template));
    }
}