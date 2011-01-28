<?php
/**
 * Horde_Service_Facebook_BatchRequest::
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Horde_Service_Facebook
 */
class Horde_Service_Facebook_BatchRequest extends Horde_Service_Facebook_Request
{
    /**
     *  Holds pending operations
     *
     * @var array
     */
    private $_queue = array();

    /**
     * Current mode.
     *
     * @var BATCH_MODE_* Constant
     */
    private $_batchMode;

    /** BATCH_MODE constants **/
    const BATCH_MODE_DEFAULT = 0;
    const BATCH_MODE_SERVER_PARALLEL = 0;
    const BATCH_MODE_SERIAL_ONLY = 2;

    /**
     * @param Horde_Service_Facebook $facebook
     * @param Horde_Http_Client      $http_client
     * @param array                  $params
     */
    public function __construct($facebook, $http_client, $params = array())
    {
        $this->_http = $http_client;
        $this->_facebook = $facebook;

        if (!empty($params['batch_mode'])) {
            $this->_batchMode = $params['batch_mode'];
        } else {
            $this->_batchMode = self::BATCH_MODE_DEFAULT;
        }
    }

    /**
     * Add a method call to the queue
     *
     * @param  $method
     * @param  $params
     * @return unknown_type  Returns a reference to the results that will be
     *                       produced when the batch is run. This reference
     *                       should be saved until after the batch is run and
     *                       the results can be examined.
     */
    public function &add($method, $params)
    {
        $result = null;
        $batch_item = array('m' => $method, 'p' => $params, 'r' => &$result);
        $this->_queue[] = $batch_item;
        return $result;
    }

    /**
     * Execute a set of batch operations.
     *
     * @return void
     */
    public function run()
    {
        $item_count = count($this->_queue);
        $method_feed = array();
        foreach ($this->_queue as $batch_item) {
            $method = $batch_item['m'];
            $params = $batch_item['p'];
            $this->_finalizeParams($method, $params);
            $method_feed[] = $this->_createPostString($params);
        }
        $method_feed_json = json_encode($method_feed);

        $serial_only = ($this->_batchMode == self::BATCH_MODE_SERIAL_ONLY);
        $params = array('method_feed' => $method_feed_json,
                        'serial_only' => $serial_only,
                        'session_key' => $this->_facebook->auth->getSessionKey());
        $json = $this->_postRequest('batch.run', $params);
        $result = json_decode($json, true);

        if (is_array($result) && isset($result['error_code'])) {
          throw new Horde_Service_Facebook_Exception($result['error_msg'],
                                                     $result['error_code']);
        }

        for ($i = 0; $i < $item_count; $i++) {
            $batch_item = $this->_queue[$i];
            $batch_item_json = $result[$i];
            $batch_item_result = json_decode($batch_item_json, true);
            if (is_array($batch_item_result) &&
                isset($batch_item_result['error_code'])) {

                throw new Horde_Service_Facebook_Exception($batch_item_result['error_msg'],
                                                           $batch_item_result['error_code']);
            }
            $batch_item['r'] = $batch_item_result;
        }
    }

}
