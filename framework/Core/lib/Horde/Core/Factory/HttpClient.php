<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_HttpClient extends Horde_Core_Factory_Base
{
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
        $opts['request.headers'] = array_merge(
            empty($opts['request.headers']) ? array() : $opts['request.headers'],
            array('Expect' => ''));
        return new Horde_Http_Client(array_merge($client_opts, $opts));
    }

}
