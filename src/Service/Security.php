<?php


namespace App\Service;


class Security
{
    public static function isUserRegistered($user) {
        return !is_null($user);
    }
}