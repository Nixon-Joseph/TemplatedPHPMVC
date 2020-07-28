<?php
//https://stackoverflow.com/a/7514523/4765038
/**
 * This class uses session to allow you to quickly/easily save/retreive values accross requests
 */
class TempData {
    public static function get(string $offset): ?string {
        $value = $_SESSION[$offset];
        unset($_SESSION[$offset]);
        return $value;
    }

    public static function set($offset, $value): void {
        $_SESSION[$offset] = $value;
    }

    public static function json_get(string $offset): ?object {
        try {
            return json_decode(get($offset));
        } catch (\Throwable $th) {
            return null;
        }
    }

    public static function json_set(string $offset, any $value): void {
        try {
            set($offset, json_encode($value));
        } catch (\Throwable $th) { }
    }
}
?>