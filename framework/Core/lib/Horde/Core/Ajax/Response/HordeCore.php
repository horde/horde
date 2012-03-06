<?php
/**
 * A data object that represents the full JSON data expected by the HordeCore
 * javascript framework.
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
class Horde_Core_Ajax_Response_HordeCore extends Horde_Core_Ajax_Response
{
    /**
     * Task data to send to the browser.
     *
     * @var object
     */
    public $tasks = null;

    /**
     * Constructor.
     *
     * @param mixed $data      Response data to send to browser.
     * @param mixed $tasks     Task data to send to browser.
     * @param boolean $notify  If true, adds notification info to object.
     */
    public function __construct($data = null, $tasks = null, $notify = false)
    {
        parent::__construct($data, $notify);
        $this->tasks = $tasks;
    }

    /**
     * This object adds the 'tasks' entry to the standard response.
     */
    public function jsonData()
    {
        $ob = parent::jsonData();

        if (!empty($this->tasks)) {
            $ob->tasks = $this->tasks;
        }

        return $ob;
    }

}
