<?php namespace devpirates\MVC;
class Cache {
    private $cacheLoc;

    public function __construct(string $cacheLoc) {
        $this->cacheLoc = $cacheLoc;
        if (!file_exists($cacheLoc)) {
            mkdir($cacheLoc);
        }
    }

    private function getOutputCacheFilename(string $key): string {
        return "$this->cacheLoc/output-$key.cache";
    }

    public function GetOutputCache(string $key): ?string {
        $filename = $this->getOutputCacheFilename($key);
        if (file_exists($filename)) {
            $contents = file_get_contents($filename);
            if ($contents != false) {
                try {
                    $cacheObj = OutputCacheObject::FromString($contents);
                    if (isset($cacheObj) && time() <= $cacheObj->Expiry) {
                        return $cacheObj->Value;
                    } else {
                        // delete file
                        unlink($filename);
                    }
                } catch (\Throwable $th) {
                    // delete file
                    unlink($filename);
                }
            }
        }
        return null;
    }

    public function SetOutputCache(string $key, int $secondsToLive, string $contents) {
        $filename = $this->getOutputCacheFilename($key);
        try {
            $cacheFile = fopen($filename, 'w');
            fwrite($cacheFile, new OutputCacheObject($contents, time() + $secondsToLive));
        } catch (\Throwable $th) {
            
        } finally {
            fclose($cacheFile);
        }
    }

    public function ClearOutputCache(string $key): bool {
        $filename = $this->getOutputCacheFilename($key);
        if (file_exists($filename)) {
            return unlink($filename);
        }
        return false;
    }

    public function ClearAllOutputCache(): bool {
        if ($dir = opendir($this->cacheLoc)) {
            while (($file = readdir($dir)) !== false) {
                if (unlink($file) === false) {
                    return false;
                }
            }
        }
        return true;
    }
}

class OutputCacheObject extends CacheObjectBase {
    /**
     * @var string
     */
    public $Value;

    public function __construct(?string $value = null, ?int $expiryTime = -1) {
        if (!isset($expiryTime) || $expiryTime <= 0) {
            $expiryTime = (Time() + 120);
        }
        parent::__construct($expiryTime);
        $this->Value = $value;
    }

    public static function FromString(string $json): ?OutputCacheObject {
        try {
            $data = json_decode($json, true);
            return new OutputCacheObject($data{'Value'}, $data{'Expiry'});
        } catch (\Throwable $th) {
            return null;
        }
    }
}

class CacheObject extends CacheObjectBase {
    /**
     * @var object
     */
    public $Value;

    public function __construct(?object $value = null, ?int $expiryTime = -1) {
        if (!isset($expiryTime) || $expiryTime <= 0) {
            $expiryTime = (Time() + 120);
        }
        parent::__construct($expiryTime);
        $this->Value = $value;
    }

    public static function FromString(string $json, object $mapToObj): ?CacheObject {
        try {
            $data = json_decode($json, true);
            $jm = new JsonMapper();
            return $jm->map($data, $mapToObj);
        } catch (\Throwable $th) {
            return null;
        }
    }
}

abstract class CacheObjectBase {
    /**
     * @var int
     */
    public $Expiry;

    public function __construct(int $expiryTime) {
        $this->Expiry = $expiryTime;
    }

    public function __toString() {
        try {
            return json_encode($this);
        } catch (\Throwable $th) {
            return '';
        }
    }
}
?>