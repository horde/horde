<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Image
 */

/**
 * This class implements only the basic Horde_Image API.
 *
 * It's a fallback to still be able to use API even if no image manipulation
 * service is available.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Image
 */
class Horde_Image_Null extends Horde_Image_Base
{
    /**
     */
    public function __construct($params, $context = array())
    {
        parent::__construct($params, $context);
        if (!empty($params['filename'])) {
            $this->loadFile($params['filename']);
        } elseif (!empty($params['data'])) {
            $this->loadString($params['data']);
        }
    }
}
