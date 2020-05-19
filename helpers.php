<?php

use System\Classes\PluginManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Debug\Dumper;

if (!function_exists('nc_partial'))
{
    /**
     * Returns the path to the NumenCode partial file.
     *
     * @param string $_fileName
     * @param array $partialData
     * @return string
     */
    function nc_partial($_fileName, $partialData = [])
    {
        extract($partialData, EXTR_OVERWRITE);

        ob_start();

        require base_path('plugins/numencode/fundamentals/partials/' . $_fileName);

        return ob_get_clean();
    }
}

if (!function_exists('validate_request'))
{
    /**
     * Validates current request and flashes errors to the session.
     * Returns true if the request is valid or false if it's not.
     *
     * @param array $rules
     * @return bool
     */
    function validate_request(array $rules, $messages = [])
    {
        if (post('_ajax_validate')) {
            $rules['_ajax_validate_ensure_failure'] = 'required';
        }

        $validator = Validator::make(request()->all(), $rules, $messages);

        if ($validator->fails()) {
            session()->flash('errors', $validator->messages());

            return false;
        }

        session()->forget('errors');

        return true;
    }
}

if (!function_exists('select_options'))
{
    /**
     * Create options for select element
     *
     * @param array $options
     * @return string
     */
    function select_options(array $options)
    {
        $result = [];

        foreach($options as $key => $value) {
            $result[] = '<option value="' . $key . '">' . $value . '</option>';
        }

        return implode("\n", $result);
    }
}

if (!function_exists('array_insert'))
{
    /**
     * Insert a new element to a position inside an array
     *
     * @param $array
     * @param $beforeElement
     * @param $data
     * @return array
     */
    function array_insert($array, $beforeElement, $data)
    {
        if (is_int($beforeElement)) {
            $first = $array;
            $second = array_splice($first, $beforeElement);

            return array_merge($first, $data, $second);
        } else {
            $beforeElement = array_search($beforeElement, array_keys($array));
            return array_merge(
                array_slice($array, 0, $beforeElement),
                $data,
                array_slice($array, $beforeElement)
            );
        }
    }
}

if (!function_exists('recursive_array_search'))
{
    /**
     * Search the array recursively for a given value and return the corresponding keys if successful.
     *
     * @param  string $needle
     * @param  array $haystack
     * @param array $keys
     * @return array
     */
    function recursive_array_search($needle, $haystack, $keys = [])
    {
        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $sub = recursive_array_search($needle, $value, array_merge($keys, [$key]));
                if (count($sub)) {
                    return $sub;
                }
            } elseif ($value === $needle) {
                return array_merge($keys, [$key]);
            }
        }

        return [];
    }
}

if (!function_exists('round_global'))
{
    /**
     * Round the number to a number of decimals defined i a global setting
     *
     * @param mixed $number
     * @return float
     */
    function round_global($number)
    {
        return round($number, config('app.round_decimals', 2));
    }
}

if (!function_exists('array_merge_reference')) {
    /**
     * Merges elements from passed arrays into one array and keeps a reference to the original arrays
     *
     * @param array[] $args
     * @return array
     */
    function &array_merge_reference(array &...$args)
    {
        $result = [];

        foreach ($args as &$arg) {
            foreach ($arg as $key => $value) {
                $result[$key] = &$arg[$key];
            }
        }

        return $result;
    }
}

if (!function_exists('plugin_exists')) {
    /**
     * Check if plugin exists and is enabled.
     *
     * @param string $plugin
     * @return bool
     */
    function plugin_exists($plugin)
    {
        return array_key_exists($plugin, PluginManager::instance()->getPlugins());
    }
}

if (!function_exists('extend_class')) {
    /**
     * Extend a class with a behavior.
     *
     * @param string $class
     * @param $extension
     */
    function extend_class($class, $extension)
    {
        $class::extend(function ($object) use ($extension) {
            $object->extendClassWith($extension);
        });
    }
}

if (!function_exists('dumpbug'))
{
    /**
     * Dumps a simple debug backtrace.
     */
    function dumpbug()
    {
        $vars = func_get_args();
        echo '<pre>';
        foreach($vars as $var) {
            echo '<strong>(' . gettype($var) . ')</strong> ';
            print_r($var);
        }
        echo '</pre>';
        return;
    }
}

if (!function_exists('diebug'))
{
    /**
     * Dies and dumps a simple debug backtrace.
     */
    function diebug()
    {
        die(call_user_func_array('dumpbug', func_get_args()));
    }
}

if (!function_exists('ddd'))
{
    /**
     * Quick fix for not rendering dd in browser's network tab
     */
    function ddd(...$args){
        http_response_code(500);
        call_user_func_array('dd', $args);
    }
}

if (!function_exists('dd_query'))
{
    $_global_query_count = 0;
    /**
     * Dump the next database query.
     *
     * @return void
     */
    function dd_query($count = 1)
    {
        DB::listen(function($query) use ($count)
        {
            global $_global_query_count;

            while(strpos($query->sql, '?')) {
                $query->sql = preg_replace('/\?/', '"' . array_shift($query->bindings) . '"', $query->sql, 1);
            }

            $output = '(' . $query->time . ' ms) ' . $query->sql;

            if(++$_global_query_count == $count) dd($output); else d($output);
        });
    }
}

if (!function_exists('d'))
{
    /**
     * Dump the passed variables and end the script.
     *
     * @param  mixed
     * @return void
     */
    function d()
    {
        array_map(function($x) { (new Dumper)->dump($x); }, func_get_args());
    }
}

if (!function_exists('ddt'))
{
    /**
     * Dies and dumps a simple debug backtrace. Useful for console debugging.
     *
     * @param int $skip Number of last nodes to skip from the output
     * @param bool $die Die after printing the trace
     */
    function ddt($skip = 0, $die = true)
    {
        $stacks = debug_backtrace();
        $output = '';

        foreach ($stacks as $_stack) {
            if (!isset($_stack['file'])) {
                $_stack['file'] = '[PHP Kernel]';
            }
            if (!isset($_stack['line'])) {
                $_stack['line'] = '';
            }

            if ($skip <= 0) {
                $output .= "{$_stack["file"]} : {$_stack["line"]} - {$_stack["function"]}" . PHP_EOL;
            }
            $skip--;
        }

        if($die) die($output);

        d($output);
    }
}
