<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Interface to allow flagging a message based on bodystructure data.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
interface IMP_Flag_Match_Structure
{
    /**
     * Set flag by doing body structure matching.
     *
     * @param Horde_Mime_Part $data  Structure object for a message.
     *
     * @return boolean  True if the flag should be set.
     */
    public function matchStructure(Horde_Mime_Part $data);

}
