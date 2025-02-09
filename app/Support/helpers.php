<?php

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\App as Laravel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

if (! function_exists('is_json')) {
    /**
     * If the string is valid JSON, return true, otherwise return false
     *
     * @param string $str The string to check.
     *
     * @return bool The function is_json() is returning a boolean value.
     */
    function is_json(string $str): bool
    {
        json_decode($str);

        return json_last_error() === JSON_ERROR_NONE;
    }
}

if (! function_exists('user_http_build_query')) {
    /**
     * http_build_query 的实现。
     *
     * ```
     * $queryPayload = [
     *     1 => 'a',
     *     '10' => 'b',
     *     '01' => 'c',
     *     'keyO1' => null,
     *     'keyO2' => false,
     *     'keyO3' => true,
     *     'keyO4' => 0,
     *     'keyO5' => 1,
     *     'keyO6' => 0.0,
     *     'keyO7' => 0.1,
     *     'keyO8' => [],
     *     'keyO9' => '',
     *     'key10' => new \stdClass(),
     *     'pastimes' => ['golf', 'opera', 'poker', 'rap'],
     *     'user' => [
     *         'name' => 'Bob Smith',
     *         'age' => 47,
     *         'sex' => 'M',
     *         'dob' => '5/12/1956'
     *     ],
     *     'children' => [
     *         'sally' => ['age' => 8, 'sex' => null],
     *         'bobby' => ['sex' => 'M', 'age' => 12],
     *     ],
     * ];
     * ```
     *
     * @param  array  $queryPayload
     * @param  string  $numericPrefix
     * @param  string  $argSeparator
     * @param  int  $encType
     *
     * @return string
     */
    function user_http_build_query(array $queryPayload, string $numericPrefix = '', string $argSeparator = '&', int $encType = PHP_QUERY_RFC1738): string
    {
        /**
         * 转换值是非标量的情况
         *
         * @param  string  $key
         * @param  array|object $value
         * @param  string  $argSeparator
         * @param  int  $encType
         *
         * @return string
         */
        $toQueryStr = static function (string $key, $value, string $argSeparator, int $encType) use (&$toQueryStr): string {
            $queryStr = '';
            foreach ($value as $k => $v) {
                // 特殊值处理
                if ($v === null) {
                    continue;
                }
                if ($v === 0 || $v === false) {
                    $v = '0';
                }

                $fullKey = "{$key}[{$k}]";
                $queryStr .= is_scalar($v)
                    ? sprintf("%s=%s$argSeparator", $encType === PHP_QUERY_RFC3986 ? rawurlencode($fullKey) : urlencode($fullKey), $encType === PHP_QUERY_RFC3986 ? rawurlencode($v) : urlencode($v))
                    : $toQueryStr($fullKey, $v, $argSeparator, $encType); // 递归调用
            }

            return $queryStr;
        };

        reset($queryPayload);
        $queryStr = '';
        foreach ($queryPayload as $k => $v) {
            // 特殊值处理
            if ($v === null) {
                continue;
            }
            if ($v === 0 || $v === false) {
                $v = '0';
            }

            // 为了对数据进行解码时获取合法的变量名
            if (is_numeric($k) && ! is_string($k)) {
                $k = $numericPrefix . $k;
            }

            $queryStr .= is_scalar($v)
                ? sprintf("%s=%s$argSeparator", $encType === PHP_QUERY_RFC3986 ? rawurlencode($k) : urlencode($k), $encType === PHP_QUERY_RFC3986 ? rawurlencode($v) : urlencode($v))
                : $toQueryStr($k, $v, $argSeparator, $encType);
        }

        return substr($queryStr, 0, -strlen($argSeparator));
    }
}

if (! function_exists('validate')) {
    /**
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     *
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    function validate(array $data = [], array $rules = [], array $messages = [], array $customAttributes = [])
    {
        return validator($data, $rules, $messages, $customAttributes)->validate();
    }
}

if (! function_exists('array_filter_filled')) {
    /**
     * @param  array  $array
     *
     * @return array
     */
    function array_filter_filled(array $array)
    {
        return array_filter($array, function ($item) {
            return filled($item);
        });
    }
}

if (! function_exists('call')) {
    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  callable|string  $callback
     * @param  array  $parameters
     * @param  string|null  $defaultMethod
     * @return mixed
     */
    function call($callback, array $parameters = [], $defaultMethod = null)
    {
        app()->call($callback, $parameters, $defaultMethod);
    }
}

if (! function_exists('stopwatch')) {
    /**
     * @param  string  $name
     * @param  callable|string $callback
     * @param  array  $parameters
     * @param  null|string  $category
     * @param  bool  $morePrecision
     * @param  null|\Psr\Log\LoggerInterface  $logger
     *
     * @return mixed
     */
    function stopwatch(string $name, $callback, array $parameters = [], string $category = null, bool $morePrecision = false, LoggerInterface $logger = null)
    {
        $stopwatch = new Stopwatch($morePrecision);

        $logger or $logger = Laravel::make(LoggerInterface::class);

        $stopwatch->start($name, $category) and $logger->info("Start stopwatch [$name]");

        $called = Laravel::call($callback, $parameters);

        $stopwatchEvent = $stopwatch->stop($name) and $logger->info("End stopwatch [$name] [$stopwatchEvent]");

        return $called;
    }
}

if (! function_exists('rercord_query_log')) {
    /**
     * @param callable|string $callback
     * @param ...$parameter
     *
     * @return array
     */
    function rercord_query_log($callback, ...$parameter)
    {
        return (new Pipeline())
            ->send($callback)
            ->through(function ($callback, $next) {
                DB::enableQueryLog();
                DB::flushQueryLog();

                return $next($callback);
            })
            ->then(function ($callback) use ($parameter) {
                Laravel::call($callback, $parameter);

                return DB::getQueryLog();
            });
    }
}

if (! function_exists('dump_to_array')) {
    function dump_to_array(...$vars)
    {
        foreach ($vars as $var) {
            ($var instanceof Arrayable or method_exists($var, 'toArray')) ? dump($var->toArray()) : dump($var);
        }
    }
}

if (! function_exists('dd_to_array')) {
    function dd_to_array(...$vars)
    {
        dump_to_array(...$vars);
        exit(1);
    }
}

if (! function_exists('array_reduces')) {
    /**
     * @param  array  $array
     * @param  callable  $callback
     * @param  null  $carry
     *
     * @return null|mixed
     */
    function array_reduces(array $array, callable $callback, $carry = null)
    {
        foreach ($array as $key => $value) {
            $carry = call_user_func($callback, $carry, $value, $key);
        }

        return $carry;
    }
}

if (! function_exists('array_maps')) {
    /**
     * @param  callable  $callback
     * @param  array  $array
     *
     * @return array
     */
    function array_maps(callable $callback, array $array)
    {
        $arr = [];
        foreach ($array as $key => $value) {
            $arr[$key] = call_user_func($callback, $value, $key);
        }

        return $arr;
    }
}
