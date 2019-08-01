<?php


namespace App\Services;

use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class Message
{

    public static function sendSMS($message, $to = "-", $from = "-") {
        $twilio_sid = "-";
        $twilio_token = "-";
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