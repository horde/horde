<?php
/**
 * @category Horde
 * @package  Controller
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/bsd BSD
 */
class Horde_Controller_Request_Mock extends Horde_Controller_Request_Http
{
    /**
     * Request variables.
     *
     * @var array
     */
    protected $_vars;

    /**
     * Constructor.
     *
     * @param array $vars  The request variables.
     */
    public function __construct($vars = array())
    {
        $this->setVars($vars);
        $server = $this->getServerVars();
        if (!empty($server['REDIRECT_URL'])) {
            $this->setPath($server['REDIRECT_URL']);
        } else if (!empty($server['REQUEST_URI'])) {
            $this->setPath($server['REQUEST_URI']);
        }
    } 

    /**
     * Set the request variables GET, POST, COOKIE, SERVER, REQUEST etc.
     *
     * @param array $vars  The request variables.
     */
    public function setVars($vars)
    {
        foreach ($vars as $key => $sub) {
            $this->_vars[strtoupper($key)] = $sub;
        }
    }

    /**
     * Gets the request variables GET, POST, COOKIE, SERVER, REQUEST etc.
     *
     * @param string $name  The name of the superglobal whose vars to return
     */
    protected function getVars($name)
    {
        if (isset($this->_vars[$name])) {
            return $this->_vars[$name];
        }
    }
}
