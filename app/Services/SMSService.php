<?php

namespace App\Services;

use GuzzleHttp\Client;

use Illuminate\Support\Facades\Http;

class SMSService
{

    // private $clientId = 'xjxWRml44RnoZ5dvMFIfQl3e18rGA7tv';
    private $clientId = 'nHGG0L8hBadaSPud8yja2MjYfmS183kh';
    private $clientSecret = 'Zc7RUVp7pBPBH4iS';
    private $sender = '701280'; // Sender ID configuré dans Orange SMS API
    private $tokenUrl = 'https://api.orange.com/oauth/v3/token';
    private $smsUrl = 'https://api.orange.com/smsmessaging/v1/outbound';

    // /**
    //  * Obtenir un jeton d'accès pour l'API Orange
    //  */


    // /**
    //  * Envoyer un OTP via l'API Orange
    //  */

    public function sendSmsByInfobipAPI($phoneNumber,$dataMessage)
    {
        $from="YAKO AFRICA";
        $url = "https://wp2e3q.api.infobip.com/sms/2/text/advanced";
        // $cleApi = "ca9b1e97d87d27dc425b2d598aa83c46-cbbd83f5-f0af-49ae-9bc0-02ba090ecac3";
        // $cleApi = "b16ee3ba1ca448e8b81ecdd93f80ccce-1e6a09a1-4ee9-4e44-bb4c-fec55fe5e892";
        $cleApi = config('services.info_bip_api_key');
        $headers = [
                    'Authorization' => "App $cleApi",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
        ];

        $body = [
            "messages" => [
                [
                    "from" => $from,
                    "destinations" => [
                        ["to" => $phoneNumber]
                    ],
                    "text" => $dataMessage
                ]
            ]
        ];

        try {
            
            $response = Http::withHeaders($headers)
                ->post($url, $body);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }

       
    }

  
}
