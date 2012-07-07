<?php
/**
 * Horde_Service_Facebook_BatchRequest::
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Service_Facebook
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
     * Constructor
     *
     * @param Horde_Service_Facebook $facebook
     */
    public function __construct(Horde_Service_Facebook $facebook)
    {
        $this->_facebook = $facebook;
        $this->_http = $facebook->http;
    }

    /**
     * Add a method call to the queue
     *
     * @param string $method  The API method to call.
     * @param array $params  The API method parameters.
     * @param string $request  The type of HTTP request to make.
     */
    public function &add($method, array $params, $options = array())
    {
        $request = empty($options['request']) ? 'GET' : $options['request'];
        $url = new Horde_Url($this->_facebook->getFacebookUrl('graph') . '/' . $method);
        $url->add('access_token', $this->_facebook->auth->getSessionKey());
        if ($request == 'GET' || $request == 'DELETE') {
            $url->add($params);
        }
        $batch_item = array(
            'method' => $request,
            'relative_url' => $url->toString(false, false)
        );
        if ($request == 'POST' || $request == 'PUT') {
            $batch_item['body'] = $this->_createPostString($params);
        }
        $this->_queue[] = $batch_item;
    }

    /**
     * Execute a set of batch operations.
     *
     * @return void
     */
    public function run()
    {
        $request = new Horde_Service_Facebook_Request_Graph(
            $this->_facebook,
            '',
            array('batch' => json_encode($this->_queue)),
            array('request' => 'POST'));

        return $request->run();
    }

}
