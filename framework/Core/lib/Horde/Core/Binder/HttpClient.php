<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_HttpClient implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
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

        return new Horde_Http_Client($client_opts);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
