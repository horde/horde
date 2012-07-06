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

        $copts = array();
        if (!empty($conf['http']['proxy']['proxy_host'])) {
            $copts['request.proxyServer'] = $conf['http']['proxy']['proxy_host'];
            $copts['request.proxyPort'] = $conf['http']['proxy']['proxy_port'];
            if (!empty($conf['http']['proxy']['proxy_user'])) {
                $copts['request.proxyUsername'] = $conf['http']['proxy']['proxy_user'];
                if (!empty($conf['http']['proxy']['proxy_pass'])) {
                    $copts['request.proxyPassword'] = $conf['http']['proxy']['proxy_pass'];
                }
            }
        }

        $opts['request.headers'] = array_merge(
            empty($opts['request.headers']) ? array() : $opts['request.headers'],
            array('Expect' => '')
        );

        return new Horde_Http_Client(array_merge($copts, $opts));
    }

}
