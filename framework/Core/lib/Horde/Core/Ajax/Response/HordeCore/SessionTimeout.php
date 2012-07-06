<?php
/**
 * Sends a session timeout request to the HordeCore JS framework.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Ajax_Response_HordeCore_SessionTimeout extends Horde_Core_Ajax_Response_HordeCore
{
    /**
     * App of the last JSON request.
     *
     * @var string
     */
    protected $_app;

    /**
     * Constructor.
     *
     * @param string $app  App of the last JSON request.
     */
    public function __construct($app)
    {
        $this->_app = $app;
    }

    /**
     */
    protected function _jsonData()
    {
        $msg = new stdClass;
        $msg->message = strval($GLOBALS['registry']->getLogoutUrl(array(
            'reason' => Horde_Auth::REASON_SESSION
        ))->add('url', Horde::url('', false, array(
            'app' => $this->_app,
            'append_session' => -1
        ))));
        $msg->type = 'horde.ajaxtimeout';

        $ob = new stdClass;
        $ob->msgs = array($msg);
        $ob->response = false;

        return $ob;
    }

}
