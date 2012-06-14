<?php
/**
 * A data object that indicates to the HordeCore javascript framework that
 * the action was successful, but the page needs to be reloaded.
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
class Horde_Core_Ajax_Response_HordeCore_Reload extends Horde_Core_Ajax_Response_HordeCore
{

    /**
     * Constructor.
     *
     * @param mixed $data  Response data to send to browser. For this class,
     *                     this can be the URL to redirect to. If null, will
     *                     reload the current URL of the browser.
     */
    public function __construct($data = null)
    {
        parent::__construct($data);
    }

    /**
     * Return a single property, 'reload', which indicates to the HordeCore
     * framework that the page should be immediately reloaded.
     */
    protected function _jsonData()
    {
        $ob = new stdClass;
        $ob->reload = is_null($this->data)
            ? true
            : $this->data;

        return $ob;
    }

}
