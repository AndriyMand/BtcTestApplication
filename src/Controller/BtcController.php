<?php

namespace App\Controller;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Mailer\MailerInterface;

class BtcController extends AbstractController
{
    private $client;
    private $fsObject;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->fsObject = new Filesystem();
    }

    #[Route('/', name: 'app_main')]
    public function index(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $response = $this->subscribe($request);
            $this->addFlash(
                $response->getStatusCode() == 200 ? 'success' : 'danger',
                $response->getContent()
            );
        }

        return $this->render('main/index.html.twig', [
            'rate' => $this->getBtcRate()
        ]);
    }

    #[Route('/rate', name: 'app_rate')]
    public function rate(): Response
    {
        return new Response($this->getBtcRate());
    }

    #[Route('/subscribe', name: 'app_subscribe')]
    public function subscribe(Request $request): Response
    {
        $email = $request->get('email');

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $subscribersPath = $this->getParameter('subscribers_path');
            try {
                if (!$this->fsObject->exists($subscribersPath))
                {
                    $this->fsObject->mkdir($subscribersPath, 0700);
                }
            } catch (IOExceptionInterface $exception) {
                return new Response("Error creating directory at ". $exception->getPath(), 500);
            }
            if ( !file_exists($subscribersPath . '/' . $email) ) {
                if (file_put_contents($subscribersPath . '/' . $email, 1)) {
                    return new Response("Email is successfully subscribed", 200);
                } else {
                    return new Response("Error creating file", 500);
                }
            } else {
                return new Response('Email is already subscribed', 409);
            }
        } else {
            return new Response('Email is not valid', 406);
        } 
    }
    

    #[Route('/sendEmails', name: 'app_send_emails')]
    public function sendEmails(MailerInterface $mailer): Response
    {
        $emailList = preg_grep('/^([^.])/', scandir($this->getParameter('subscribers_path')));
        foreach($emailList as $email) {
            $emailContent = (new TemplatedEmail())
                ->from('Andriy BTC Sender <mandybur.andriy10@gmail.com>')
                ->to($email)
                ->subject('Курс BTC до UAH на ' . date('d.m.Y H:i'))
                ->htmlTemplate('emails/btc.html.twig')
                ->context([
                    'rate' => $this->getBtcRate(),
                ]);

            $result = $mailer->send($emailContent);
        }

        return new Response("Success", 200);
    }

    private function getBtcRate()
    {
        $response = $this->client->request(
            'GET',
            'https://financialmodelingprep.com/api/v3/quote/BTCUSD?apikey=' . $this->getParameter('btc_api_key')
        );
        $statusCode = $response->getStatusCode();
        $content = $response->getContent();
        $content = $response->toArray();
        $priceBtcUsd = current($content)['price'];

        $response = $this->client->request(
            'GET',
            "https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?valcode=USD&date=".date('Ymd')."&json"
        );
        $statusCode = $response->getStatusCode();
        $content    = $response->getContent();
        $content    = $response->toArray();
        $priceUsdUah = current($response->toArray())['rate'];

        $rateUah = round($priceUsdUah * $priceBtcUsd, 2);
        
        return $rateUah;
    }
    

}
