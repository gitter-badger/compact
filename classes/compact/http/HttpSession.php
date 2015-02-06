<?php
namespace compact\http;

/**
 *
 * @package web
 * @subpackage http
 */
class HttpSession
{

    /**
     *
     * @var HttpSession
     */
    private static $instance;

    /**
     * Creates a new HttpSession
     */
    public function __construct()
    {
        if (self::$instance !== null) {
            throw new \Exception("Singleton!");
        }
    }

    /**
     * Destroys the session
     */
    public function destroy()
    {
        session_unset();
        session_destroy();
        
        $this->instance = null;
    }

    /**
     * Checks if a variable name exists in the current session
     *
     * @param String $aVarName            
     * @return boolean
     */
    public function exists($aVarName)
    {
        $result = false;
        if (! isset($_SESSION) || (! $_SESSION))
            return $result;
        
        if (array_key_exists($aVarName, $_SESSION)) {
            $result = true;
        }
        
        return $result;
    }

    /**
     * Gets a variable from SESSION
     *
     * @param $name String            
     * @return mixed or false when not set
     */
    public function get($aName)
    {
        if (isset($_SESSION[$aName])) {
            $result = unserialize(base64_decode(($_SESSION[$aName])));
            
            return $result;
        }
        
        return false;
    }

    /**
     * Get the current cache limiter
     *
     * @return string
     */
    public function getCacheControl()
    {
        return session_cache_limiter();
    }

    /**
     * Return current cache expire
     *
     * @return int
     */
    public function getCacheExpire()
    {
        return session_cache_expire();
    }

    /**
     * Returns the instance of HttpSession
     *
     * @return HttpSession
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new HttpSession();
        }
        
        return self::$instance;
    }

    /**
     * Returns the session id
     *
     * @return string
     */
    public function id()
    {
        return session_id();
    }

    /**
     * Checks if the session is started
     *
     * @return boolean
     */
    public function isStarted()
    {
        $id = $this->id();
        return (empty($id)) ? false : true;
    }

    /**
     * Removes a var from SESSION
     *
     * @param String $name            
     */
    public function remove($aName)
    {
        if (isset($_SESSION[$aName])) {
            unset($_SESSION[$aName]);
        }
    }

    /**
     * Sets a variable in SESSION
     *
     * @param String $name            
     * @param Mixed $var            
     */
    public function set($aName, $aVar)
    {
        if ($aName !== null && $aName !== "" && $aVar !== null && $aVar !== "") {
            $_SESSION[$aName] = base64_encode(serialize($aVar));
        }
    }

    /**
     * Return current cache expire
     *
     * @param int $value The cache expiration in minutes
     */
    public function setCacheExpire($value)
    {
        session_cache_expire($value);
    }

    /**
     * Set the current cache limiter
     *
     * @param
     *            string one of: none, nocache, private, private_no_expire, public
     */
    public function setCacheControl($value)
    {
        session_cache_limiter($value);
    }

    /**
     * Starts the session
     *
     * @throws \RuntimeException when headers have already been sent
     *        
     * @return boolean
     */
    public function start()
    {
        $result = false;
        
        if ($this->isStarted()) {
            $result = true;
        } elseif (headers_sent()) {
            throw new \RuntimeException('Could not start session, headers have already been sent');
        } else {
            session_start();
            $result = true;
        }
        
        return $result;
    }
}

