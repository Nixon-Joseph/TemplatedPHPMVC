<?php

namespace devpirates\MVC;

class Authentication
{
    public static function GenerateHashAndSalt(string $password): array
    {
        $intermediateSalt = md5(uniqid(rand(), true));
        $salt = substr($intermediateSalt, 0, 6);
        return array("hash" => Authentication::GenerateHash($password, $salt), "salt" => $salt);
    }

    public static function GenerateHash(string $password, string $salt): string
    {
        return hash("sha512", $password . $salt);
    }

    public static function CheckPassword(string $password, string $salt, string $hash): bool
    {
        return Authentication::GenerateHash($password, $salt) === $hash;
    }
}
