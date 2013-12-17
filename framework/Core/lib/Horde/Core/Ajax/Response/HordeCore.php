<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * A data object that represents the full JSON data expected by the HordeCore
 * javascript framework.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */
class Horde_Core_Ajax_Response_HordeCore extends Horde_Core_Ajax_Response
{
    /**
     * Javscript files to be loaded by the browser.
     *
     * @since 2.10.0
     *
     * @var array
     */
    public $jsfiles = array();

    /**
     * Task data to send to the browser.
     *
     * @var object
     */
    public $tasks = null;

    /**
     * Constructor.
     *
     * @param mixed $data   Response data to send to browser.
     * @param mixed $tasks  Task data to send to browser.
     */
    public function __construct($data = null, $tasks = null)
    {
        parent::__construct($data);
        $this->tasks = $tasks;
    }

    /**
     */
    public function send()
    {
        header('Content-Type: application/json');
        echo str_replace("\00", '', Horde::escapeJson($this->_jsonData()));
    }

    /**
     * HordeCore JSON responses are a structured object which always
     * includes the response in the 'response' property.
     *
     * The object may include a 'jsfiles' property, which is an array of
     * URL-accessible javascript files to be loaded by the browser (since
     * 2.10.0).
     *
     * The object may include a 'msgs' property, which is an array of
     * notification message objects with 2 properties: message and type.
     *
     * The object may include a 'tasks' property, which is an array of
     * keys (task names) / values (task data).
     *
     * @return object  HordeCore JSON object.
     */
    protected function _jsonData()
    {
        global $notification, $page_output;

        $ob = new stdClass;
        $ob->response = $this->data;

        $stack = $notification->notify(array(
            'listeners' => array('status', 'audio'),
            'raw' => true
        ));

        if (!empty($stack)) {
            $ob->msgs = $stack;
        }

        foreach ($page_output->hsl as $val) {
            $this->jsfiles[] = strval($val->url);
        }
        $page_output->hsl->clear();

        if (!empty($this->jsfiles)) {
            $ob->jsfiles = array_values(array_unique($this->jsfiles));
        }

        if (!empty($this->tasks)) {
            $ob->tasks = $this->tasks;
        }

        return $ob;
    }

}
