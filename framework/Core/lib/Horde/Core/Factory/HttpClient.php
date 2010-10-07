<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_HttpClient
{
    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector  The injector to use.
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Get client object.
     *
     * @param array $opts  Configuration options.
     *
     * @return Horde_Http_Client  Client object.
     * @throws Horde_Http_Client_Exception
     */
    public function create(array $opts = array())
    {
        global $conf;

        $client_opts = array();
        if (!empty($conf['http']['proxy']['proxy_host'])) {
            $client_opts['request.proxyServer'] = $conf['http']['proxy']['proxy_host'];
            $client_opts['request.proxyPort'] = $conf['http']['proxy']['proxy_port'];
            if (!empty($conf['http']['proxy']['proxy_user'])) {
                $client_opts['request.proxyUsername'] = $conf['http']['proxy']['proxy_user'];
                if (!empty($conf['http']['proxy']['proxy_pass'])) {
                    $client_opts['request.proxyPassword'] = $conf['http']['proxy']['proxy_pass'];
                }
            }
        }

        return new Horde_Http_Client(array_merge($client_opts, $opts));
    }

}
