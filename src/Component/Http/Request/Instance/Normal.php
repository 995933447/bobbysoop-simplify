<?php
namespace Bobby\Component\Http\Request\Instance;

use Bobby\Contract\Http\Request as RequestContract;

/**
 * 一般模式(cli or fpm or apache etc)http请求封装组件
 */
class Normal implements RequestContract
{

    private $server;

    private $post;

    private $get;

    private $files;

    private $coockie;

    private $PHPInput;

    private $allHttpData;

    private $header;

    public $filter;

    public function get($name = null, $defaultValue = null) {
        return $this->call('get', $name ? [$name, $defaultValue] : null);
    }

    public function post($name = null, $defaultValue = null) {
        return $this->call('post', $name ? [$name, $defaultValue] : null);
    }

    public function files($name = null, $defaultValue = null) {
        return $this->call('files', $name ? [$name, $defaultValue] : null);
    }

    public function coockie($name = null, $defaultValue = null) {
        return $this->call('cookie', $name ? [$name, $defaultValue] : null);
    }

    public function server($name = null, $defaultValue = null) {
        return $this->call('server', $name ? [$name, $defaultValue] : null);
    }

    private function setParamters($paramterName)
    {

        if(!$this->$paramterName) {

            switch ($paramterName) {
                case 'server':
                    $paramters = $_SERVER;
                    if('cli-server' === PHP_SAPI) {
                        if(isset($_SERVER['HTTP_CONTENT_LENGTH'])) {
                            $_SERVER['CONTENT_LENGTH'] = $_SERVER['HTTP_CONTENT_LENGTH'];
                            unset($_SERVER['HTTP_CONTENT_LENGTH']);
                        }
                        if(isset($_SERVER['HTTP_CONTENT_TYPE'])) {
                            $_SERVER['CONTENT_TYPE'] = $_SERVER['HTTP_CONTENT_TYPE'];
                            unset($_SERVER['HTTP_CONTENT_TYPE']);
                        }
                    }
                    break;
                case 'get':
                    $paramters = $_GET;
                    break;
                case 'post':
                    $paramters = $_POST;
                    break;
                case 'files':
                    $paramters = $_FILES;
                    break;
                case 'coockie':
                    $paramters = $_COOKIE;
            }

            array_walk($paramters, function($value, $key) use($paramterName) {
                if($paramterName === 'server') $key = strtolower($key);
                $this->$paramterName[$key] = $value;
            });

        }

    }

    private function call($method, $parameters = null)
    {
        $this->setParamters($method);

        if(!$parameters) return $this->$method;

        if(isset($parameters[1]))
            list($parameterName, $defaultValue) = $parameters;
        else
            list($parameterName, $defaultValue) = [$parameters[0], null];

        if(is_array($parameterName)) return $this->$method[key($parameterName)] = current($parameterName);

        return $this->$method[$parameterName]?? $defaultValue;
    }

    public function getPHPInput($paramterName = null, $defaultValue = null)
    {
        if(!$this->PHPInput) $this->PHPInput = file_get_contents('php://input');

        if(!$paramterName) return $this->PHPInput;
        return $this->PHPInput[$paramterName]?? $defaultValue;
    }

    private function setAllHttpData()
    {

        throw new \Exception("This method only just support swoole extension mode", 500);

    }

    public function http()
    {
        $this->setAllHttpData();
        return $this->allHttpData;
    }

    public function header($name = null)
    {
        $this->setAllHttpData();
        return $name ? $this->header[$name] : $this->header;
    }

    public function uri()
    {
        if($pathinfo =$this->server('path_info')) {
            return $pathinfo;
        }

        $uri = ltrim($this->server('request_uri'), $this->server('php_self'));
        $uri = $uri == '' ? '/' : $uri;

        return substr($uri, strpos($uri, '?') !== false ? strpos($uri, '?') + 1 : 0);
    }

    public function uriWithQuery()
    {
        return $this->uri() . '?' . $this->server('query_string');
    }

    public function method()
    {
        return $this->server('request_method');
    }

    public function time()
    {
        return $this->server('request_time');
    }

    public function setGlobalFilter(array $callbacks)
    {
        $this->filter = $callbacks;
    }

    public function filter(string $name, array $callbacks = null, $append = true)
    {
        if($append && $this->filter) $callbacks = array_merge($this->filter, $callbacks);
        $name = explode('.', $name, 2);

        list($method, $parameter) = isset($name[1]) ? $name : [$name[0], null];
        $value = is_null($parameter) ? $this->$method() : $this->$method($parameter);

        return $callbacks ? $this->filterVar($value, $callbacks) : $value;
    }

    private function filterVar($value, $callbacks)
    {
        if(is_array($value)) foreach ($value as $k => $v) $value[$k] = $this->filterVar($v, $callbacks);

        else return array_reduce($callbacks, function($value, $callback) {
                return $callback($value);
            }, $value);

        return $value;
    }

}