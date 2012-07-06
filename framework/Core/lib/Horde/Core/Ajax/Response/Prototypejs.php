<?php
/**
 * A data object that represents JSON data that is output with prototypejs
 * security delimiters.
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
class Horde_Core_Ajax_Response_Prototypejs extends Horde_Core_Ajax_Response
{
    /**
     */
    public function send()
    {
        header('Content-Type: application/json');
        echo str_replace("\00", '', Horde::escapeJson($this->data));
    }

}
