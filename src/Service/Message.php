<?php


namespace App\Service;


use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class Message
{

    public static function sendSMS($message, $to = null, $from = null) {
        if (is_null($to))
            $to = getenv("TWILIO_TO");
        if (is_null($from))
            $from = getenv("TWILIO_FROM");

        $twilio_sid = getenv("TWILIO_SID");
        $twilio_token = getenv("TWILIO_TOKEN");
        try {
            $twilio = new Client($twilio_sid, $twilio_token);

            $twilio->messages->create($to,
                [
                    "body" => $message,
                    "from" => $from,
                ]
            );
        } catch (TwilioException $exception) {
            print_r($exception->getMessage());die;
        }
    }
}