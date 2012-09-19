<?php
/**
 * View helper class to allow access to the Horde::label() method.
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
class Horde_Core_View_Helper_Label extends Horde_View_Helper_Base
{
    /**
     * Wrapper around the Horde::label() method.
     *
     * @see Horde::label()
     */
    public function hordeLabel($for, $label, $ak = null)
    {
        return Horde::label($for, $label, $ak);
    }

}
