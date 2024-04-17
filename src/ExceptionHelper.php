<?php

namespace devpirates\MVC;

class ExceptionHelper
{
    /**
     * provide a Java style exception trace
     *
     * @param \Throwable $e
     * @param any $seen- array passed to recursive calls to accumulate trace lines already seen
     *                   leave as NULL when calling this function
     * @return array
     */
    public static function JTraceEx(\Throwable $e, $seen = null): string
    {
        $starter = $seen ? 'Caused by: ' : '';
        $result = array();
        if (!$seen) $seen = array();
        $trace  = $e->getTrace();
        $prev   = $e->getPrevious();
        $result[] = sprintf('%s%s: %s', $starter, get_class($e), $e->getMessage());
        $file = $e->getFile();
        $line = $e->getLine();
        while (true) {
            $current = "$file:$line";
            if (is_array($seen) && in_array($current, $seen)) {
                $result[] = sprintf(' ... %d more', count($trace) + 1);
                break;
            }
            $result[] = sprintf(
                ' at %s%s%s(%s%s%s)',
                count($trace) && array_key_exists('class', $trace[0]) ? str_replace('\\', '.', $trace[0]['class']) : '',
                count($trace) && array_key_exists('class', $trace[0]) && array_key_exists('function', $trace[0]) ? '.' : '',
                count($trace) && array_key_exists('function', $trace[0]) ? str_replace('\\', '.', $trace[0]['function']) : '(main)',
                $line === null ? $file : basename($file),
                $line === null ? '' : ':',
                $line === null ? '' : $line
            );
            if (is_array($seen))
                $seen[] = "$file:$line";
            if (!count($trace))
                break;
            $file = array_key_exists('file', $trace[0]) ? $trace[0]['file'] : 'Unknown Source';
            $line = array_key_exists('file', $trace[0]) && array_key_exists('line', $trace[0]) && $trace[0]['line'] ? $trace[0]['line'] : null;
            array_shift($trace);
        }
        $result = join("\n", $result);
        if ($prev)
            $result .= "\n" . self::JTraceEx($prev, $seen);

        return $result;
    }

    /**
     * provide a Java style exception trace as string
     *
     * @param \Throwable $e
     * @param any $seen- array passed to recursive calls to accumulate trace lines already seen
     *                   leave as NULL when calling this function
     * @return array
     */
    public static function JTraceExAsString(\Throwable $e, $seen = null): string
    {
        $trace = self::JTraceEx($e, $seen);
        if (isset($trace)) {
            return json_encode($trace);
        }
        return null;
    }
}
