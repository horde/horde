<?php
/**
 * This class implements an IMP system flag with matching on a headers object.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
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
