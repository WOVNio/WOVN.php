<?php
namespace Wovnio\Wovnphp;

class Environment
{
    private $env;
    private $store;

    public function __construct($env, $store)
    {
        $this->env = $env;
        $this->store = $store;
    }

    public function getEnv() {
        return $this->env;
    }

    public function getProtocol() {
        if ($this->store->settings['use_proxy'] && isset($this->env['HTTP_X_FORWARDED_PROTO'])) {
            return $this->env['HTTP_X_FORWARDED_PROTO'];
        } else {
            if (isset($this->env['HTTPS']) && !empty($this->env['HTTPS']) && $this->env['HTTPS'] !== 'off') {
                return 'https';
            } else {
                return 'http';
            }
        }
    }

    public function getHost() {
        if ($this->store->settings['use_proxy'] && isset($this->env['HTTP_X_FORWARDED_HOST'])) {
            return $this->env['HTTP_X_FORWARDED_HOST'];
        } else {
            return $this->env['HTTP_HOST'];
        }
    }

    public function setHost($value) {
        if ($this->store->settings['use_proxy'] && isset($this->env['HTTP_X_FORWARDED_HOST'])) {
            $this->env['HTTP_X_FORWARDED_HOST'] = $value;
        } else {
            $this->env['HTTP_HOST'] = $value;
        }
    }

    public function getServerName() {
        if ($this->store->settings['use_proxy'] && isset($this->env['HTTP_X_FORWARDED_HOST'])) {
            return $this->env['HTTP_X_FORWARDED_HOST'];
        } else {
            return $this->env['SERVER_NAME'];
        }
    }

    public function setServerName($value) {
        if ($this->store->settings['use_proxy'] && isset($this->env['HTTP_X_FORWARDED_HOST'])) {
            $this->env['HTTP_X_FORWARDED_HOST'] = $value;
        } else {
            $this->env['SERVER_NAME'] = $value;
        }
    }

    public function getRequestUri() {
        if ($this->store->settings['use_proxy'] && isset($this->env['HTTP_X_FORWARDED_REQUEST_URI'])) {
            return $this->env['HTTP_X_FORWARDED_REQUEST_URI'];
        } else {
            return $this->env['REQUEST_URI'];
        }
    }

    public function setRequestUri($value) {
        if ($this->store->settings['use_proxy'] && isset($this->env['HTTP_X_FORWARDED_REQUEST_URI'])) {
            $this->env['HTTP_X_FORWARDED_REQUEST_URI'] = $value;
        } else {
            $this->env['REQUEST_URI'] = $value;
        }
    }

    public function getCookies() {
        $value = $this->getEnvVariableSafe('HTTP_COOKIE');
        return $value !== null
            ? $value
            : '';
    }

    public function getReferer() {
        return $this->getEnvVariableSafe('HTTP_REFERER');
    }

    public function setReferer($value) {
        $this->setEnvVariableSafe('HTTP_REFERER', $value);
    }

    public function getQueryString() {
        return $this->getEnvVariableSafe('QUERY_STRING');
    }

    public function setQueryString($value) {
        $this->env['QUERY_STRING'] = $value;
    }

    public function getRedirectUrl() {
        return $this->getEnvVariableSafe('REDIRECT_URL');
    }

    public function setRedirectUrl($value) {
        $this->setEnvVariableSafe('REDIRECT_URL', $value);
    }

    public function getOriginalFullpath() {
        return $this->getEnvVariableSafe('ORIGINAL_FULLPATH');
    }

    public function setOriginalFullpath($value) {
        $this->setEnvVariableSafe('ORIGINAL_FULLPATH', $value);
    }

    private function getEnvVariableSafe($name) {
        if (isset($this->env[$name])) {
            return $this->env[$name];
        }
    }

    private function setEnvVariableSafe($name, $value) {
        if (isset($this->env[$name])) {
            $this->env[$name] = $value;
        }
    }
}
