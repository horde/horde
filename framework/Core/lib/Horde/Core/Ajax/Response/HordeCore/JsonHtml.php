<?php
/**
 * Extends the base HordeCore object by outputting the JSON data in HTML
 * format.
 *
 * Copyright 2012-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Ajax_Response_HordeCore_JsonHtml extends Horde_Core_Ajax_Response_HordeCore
{
    /**
     */
    public function send()
    {
        $this->jsonhtml = true;
        parent::send();
    }

}
