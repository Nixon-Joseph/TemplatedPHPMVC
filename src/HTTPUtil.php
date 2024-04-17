<?php

namespace devpirates\MVC;

use \Error;

class HttpUtil
{
    static public ?array $GetAfter = null;

    /** @noinspection MissingParameterTypeDeclarationInspection */
    public static function GetURL(string $URI, $Context): string
    {
        try {
            $HTTPRequest = @fopen($URI, 'rb', false, $Context);
            $DataStream  = fopen("php://memory", "rb+");

            if (!$HTTPRequest) {
                $HTTPRequest = fopen("php://memory", "wb+");

                fprintf($HTTPRequest, "Failed to load resource.");

                rewind($DataStream);
            }

            stream_copy_to_stream($HTTPRequest, $DataStream);

            rewind($DataStream);

            return stream_get_contents($DataStream);
        } catch (Error $e) {
            return $e->getMessage();
        }
    }

    // /**
    // * @noinspection MissingReturnTypeInspection
    // *
    // * @returns resource Context
    // */
    // public static function CreateContext() {
    //     $Options = [
    //         'http' => [
    //         'method' => 'GET',
    //         'timeout' => 120,
    //         'user_agent' => '...',
    //         'protocol_version' => 1.0,
    //         'header' => [
    //             "Referer: {$_SERVER['SERVER_NAME']}"
    //         ]
    //         ]
    //     ];

    //     return stream_context_create($Options);
    // }

    // /**
    // * @noinspection MissingReturnTypeInspection
    // *
    // * @returns resource Context with auth
    // */
    // public static function CreateAuthContext() {
    //     $UserName = ASD;
    //     $Password = DecryptAuth(ASD_PASS);

    //     $SessionCookieName = session_name();
    //     $SessionId = session_id();

    //     $Auth = base64_encode("{$UserName}:{$Password}");

    //     $Options = [
    //         'http' => [
    //         'method' => 'GET',
    //         'timeout' => 120,
    //         'user_agent' => '...',
    //         'protocol_version' => 1.0,
    //         'header' => [
    //             "Referer: {$_SERVER['SERVER_NAME']}",
    //             "Cookie: {$SessionCookieName}={$SessionId}",
    //             "Authorization: Basic {$Auth}"
    //         ]
    //         ]
    //     ];

    //     return stream_context_create($Options);
    // }

    public static function HasAfter(): bool
    {
        return self::$GetAfter !== null && count(self::$GetAfter);
    }

    public static function AddResults(string $JSName, string $Results): bool
    {
        if (self::$GetAfter === null) {
            self::$GetAfter = [];
        }

        self::$GetAfter[] = [
            "Name"    => $JSName,
            "Default" => $Results,
        ];

        return true;
    }

    /**
     * @noinspection MissingParameterTypeDeclarationInspection
     */
    public static function AddResultsAfter(string $JSName, ?string $URI, $Context = null, string $Default = 'undefined'): bool
    {
        if (self::$GetAfter === null) {
            self::$GetAfter = [];
        }

        // if ($Context === null) {
        //     $Context = self::CreateContext();
        // }

        self::$GetAfter[] = [
            "Name"    => $JSName,
            "Default" => $Default,
            "URI"     => $URI,
            "Context" => $Context,
        ];

        return true;
    }

    public static function AddResultsCallAfter(string $JSName, array $Function, ?array $Arguments = null, bool $Serialize = true): bool
    {
        if (self::$GetAfter === null) {
            self::$GetAfter = [];
        }

        self::$GetAfter[] = [
            "Name"      => $JSName,
            "Arguments" => $Arguments,
            "Function"  => $Function,
            "Serialize" => $Serialize
        ];

        return true;
    }

    public static function GetResults(): array
    {
        if (!self::HasAfter()) {
            return [];
        }

        $Data = [];

        foreach (self::$GetAfter as $GetItem) {
            $Result = null;

            if (isset($GetItem["Function"])) {
                [$Class, $Func] = $GetItem["Function"];

                $Object = new $Class;

                $Callable = [$Object, $Func];

                if ($GetItem['Arguments'] !== null) {
                    $Result = call_user_func_array($Callable, $GetItem['Arguments']);
                } else {
                    $Result = $Callable();
                }

                if ($GetItem["Serialize"]) {
                    $Result = $Result->serialize();
                }
            } else if (isset($GetItem["URI"])) {
                $Result = self::GetURL($GetItem["URI"], $GetItem["Context"]);
            }

            if ($Result) {
                $Data[] = "{$GetItem['Name']} = {$Result}";
            } else {
                $Data[] = "{$GetItem['Name']} = {$GetItem['Default']}";
            }
        }

        return $Data;
    }
}
