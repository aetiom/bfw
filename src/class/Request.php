<?php

namespace BFW;

class Request
{
    protected static $instance = null;
    
    protected $ip;
    protected $lang;
    protected $referer;
    protected $method;
    protected $ssl;
    protected $request;

    protected function __construct()
    {
        $this->detectIp();
        $this->detectLang();
        $this->detectReferer();
        $this->detectMethod();
        $this->detectSsl();
        $this->detectRequest();
    }
    
    public static function getInstance()
    {
        if(self::$instance === null) {
            self::$instance = new self;
        }
        
        return self::$instance;
    }
    
    public function getIp()
    {
        return $this->ip;
    }
    
    public function getLang()
    {
        return $this->lang;
    }
    
    public function getReferer()
    {
        return $this->referer;
    }
    
    public function getMethod()
    {
        return $this->method;
    }
    
    public function getSsl()
    {
        return $this->ssl;
    }
    
    public function getRequest()
    {
        return $this->request;
    }

    public function getServerVar($keyName)
    {
        if(!isset($_SERVER[$keyName])) {
            return '';
        }

        return $_SERVER[$keyName];
    }

    protected function detectIp()
    {
        $this->ip = $this->getServerVar('REMOTE_ADDR');
    }
    
    protected function detectLang()
    {
        /*
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] -> fr-FR,fr;q=0.8,en-US;q=0.6,en;q=0.4
        D'abord "fr-FR" (préférence 1/1)
        Après dans l'ordre, "fr" (préférence 0.8 / 1)
        Puis "en-US" (préférence 0.6/1)
        Enfin "en" (préférence 0.4/1)
        */
        
        $acceptLang  = $this->getServerVar('HTTP_ACCEPT_LANGUAGE');
        $acceptLangs = explode(',', $acceptLang);

        $firstLang = explode(';', $acceptLangs[0]);
        $lang = strtolower($firstLang[0]);

        if(strpos($lang, '-') !== false)
        {
            $minLang = explode('-', $lang);
            $lang    = $minLang[0];
        }
        
        $this->lang = $lang;
    }
    
    protected function detectReferer()
    {
        $this->referer = $this->getServerVar('HTTP_REFERER');
    }
    
    protected function detectMethod()
    {
        $this->method = $this->getServerVar('REQUEST_METHOD');
    }
    
    protected function detectSsl()
    {
        $serverHttps = $this->getServerVar('HTTPS');
        $fwdProto    = $this->getServerVar('HTTP_X_FORWARDED_PROTO');
        $fwdSsl      = $this->getServerVar('HTTP_X_FORWARDED_SSL');
        
        $this->ssl = false;
        
        if(!empty($serverHttps) && $serverHttps !== 'off') {
            $this->ssl = true;
        }
        elseif(!empty($fwdProto) && $fwdProto === 'https') {
            $this->ssl = true;
        }
        elseif(!empty($fwdSsl) && $fwdSsl === 'on') {
            $this->ssl = true;
        }
    }
    
    protected function detectRequest()
    {
        $parseUrl = parse_url($this->getServerVar('REQUEST_URI'));
        
        $this->request = [
            'scheme'   => '',
            'host'     => $this->getServerVar('HTTP_HOST'),
            'port'     => '',
            'user'     => '',
            'pass'     => '',
            'path'     => '',
            'query'    => '',
            'fragment' => '',
        ];
        
        $this->request = (object) array_merge($parseUrl, $this->request);
    }
}
