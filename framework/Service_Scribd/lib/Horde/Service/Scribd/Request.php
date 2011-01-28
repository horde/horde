<?php
/**
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Service_Scribd
 */

/**
 * Scribd request class
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Service_Scribd
 */
class Horde_Service_Scribd_Request
{
    protected $_args = array();
    protected $_config = array();
    protected $_method;

    public function __construct($method, $args = array())
    {
        $this->_method = $method;
        $this->_args = $args;
    }

    public function run()
    {
        $args = array_merge(
            $this->_args,
            $this->_config,
            array(
                'method' => $this->_method,
            )
        );
        if (!empty($this->_config['api_secret'])) {
            $args['api_sig'] = $this->_sign($args);
        }

        $client = Horde_Service_Scribd::getHttpClient();
        $response = $client->post(Horde_Service_Scribd::ENDPOINT, $args);
        return new Horde_Service_Scribd_Response($response->getBody());
    }

    /**
     * @param array  $config
     */
    public function setConfig($config)
    {
        $this->_config = $config;
    }

    /**
     * @param array  $args
     */
    protected function _sign($args)
    {
        $signature = $this->_config['api_secret'];
        ksort($args);
        foreach ($args as $k => $v) {
            $signature .= $k . $v;
        }

        return hash('md5', $signature);
    }

}
