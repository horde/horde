<?php
/**
 * View helper class to allow access to Horde accesskey methods.
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
class Horde_Core_View_Helper_Accesskey extends Horde_View_Helper_Base
{
    /**
     * Wrapper around the Horde::getAccessKeyAndTitle() method.
     *
     * @see Horde::getAccessKeyAndTitle()
     */
    public function hordeAccessKeyAndTitle($label, $nocheck = false,
                                           $return_array = false)
    {
        return Horde::getAccessKeyAndTitle($label, $nocheck, $return_array);
    }

}
