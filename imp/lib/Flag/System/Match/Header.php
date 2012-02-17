<?php
/**
 * This class implements an IMP system flag with matching on a headers object.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
abstract class IMP_Flag_System_Match_Header extends IMP_Flag_Base
{
    /**
     * @param Horde_Mime_Headers $data  Headers object for a message.
     */
    public function match(Horde_Mime_Headers $data)
    {
        return false;
    }

}
