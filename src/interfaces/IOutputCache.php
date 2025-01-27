<?php

namespace devpirates\MVC\Interfaces;

interface IOutputCache
{
    function GetOutputCache(string $key): ?string;
    function SetOutputCache(string $key, int $secondsToLive, string $contents) : void;
    function ClearOutputCache(string $key): bool;
    function ClearAllOutputCache(): bool;
}

?>