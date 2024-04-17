<?php

namespace devpirates\MVC;

/**
 * This class is used to return useful information from DB transactions
 */
class ResponseInfo
{
    /**
     * Id value of transaction
     *
     * @var string
     */
    public $Id;
    /**
     * Error or info message
     *
     * @var string
     */
    public $Message;
    /**
     * Whether or not the action succeeded
     *
     * @var bool
     */
    public $Success;

    public function __construct(bool $success = false, ?string $id = "", ?string $message = "")
    {
        $this->Id = $id;
        $this->Message = $message;
        $this->Success = $success;
    }

    /**
     * Creates a Successful ResponseInfo
     *
     * @param string|null $id
     * @param string|null $message
     * @return ResponseInfo
     */
    public static function Success(?string $id = "", ?string $message = ""): ResponseInfo
    {
        return new ResponseInfo(true, $id, $message);
    }

    /**
     * Creates an unsuccessful ResponseInfo
     *
     * @param string $message
     * @return ResponseInfo
     */
    public static function Error(string $message): ResponseInfo
    {
        return new ResponseInfo(false, null, $message);
    }
}
