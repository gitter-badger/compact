<?php
namespace compact\http;

/**
 * HttpContext holds all http related classes
 *
 * @author elger
 */
class HttpContext
{

    /**
     *
     * @var compact\http\HttpRequest
     */
    private $request;

    /**
     *
     * @var compact\http\HttpResponse
     */
    private $response;

    /**
     *
     * @var compact\http\HttpSession
     */
    private $session;

    /**
     * Constructor
     */
    public function __construct()
    {
        //
    }

    /**
     * Returns the http request
     *
     * @return \compact\http\HttpRequest
     */
    public function getRequest()
    {
        if ($this->request === null) {
            $this->request = new HttpRequest();
        }
        return $this->request;
    }

    /**
     * Reurns the http response
     *
     * @return \compact\http\HttpResponse
     */
    public function getResponse()
    {
        if ($this->response === null) {
            $this->response = new HttpResponse();
        }
        return $this->response;
    }

    /**
     * Returns the cookie manager
     *
     * @return \compact\http\HttpCookieManager
     */
    public function getCookieManager()
    {
        return HttpCookieManager::get();
    }

    /**
     * Returns the session
     *
     * @return \compact\http\HttpSession
     */
    public function getSession()
    {
        if ($this->session === null) {
            $this->session = HttpSession::getInstance();
        }
        
        return $this->session;
    }
}