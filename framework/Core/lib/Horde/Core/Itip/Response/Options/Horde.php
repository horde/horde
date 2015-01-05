<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see
 * {@link http://www.horde.org/licenses/lgpl21 LGPL}.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Core
 */

/**
 * Handles iTip response options for Horde iTip responses.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Core
 * @since     2.17.0
 */
class Horde_Core_Itip_Response_Options_Horde
extends Horde_Itip_Response_Options_Horde
{
    /**
     */
    public function prepareResponseMimeHeaders(Horde_Mime_Headers $headers)
    {
        $headers->addHeaderOb(
            Horde_Core_Mime_Headers_Received::createHordeHop()
        );

        parent::prepareResponseMimeHeaders($headers);
    }

}
