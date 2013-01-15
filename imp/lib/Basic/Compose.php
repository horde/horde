<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl21 GPL
 * @package   IMP
 */

/**
 * Compose page for basic view.
 *
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl21 GPL
 * @package   IMP
 */
class IMP_Basic_Compose
{
    /**
     * Create the IMP_Contents objects needed to create a message.
     *
     * @param Horde_Variables $vars  The variables object.
     *
     * @return IMP_Contents  The IMP_Contents object.
     * @throws IMP_Exception
     */
    public function getContents($vars)
    {
        $indices = new IMP_Indices_Mailbox($vars);
        $ob = null;

        if (count($indices)) {
            try {
                $ob = $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create($indices);
            } catch (Horde_Exception $e) {}
        }

        if (is_null($ob)) {
            $vars->buid = null;
            $vars->type = 'new';
            throw new IMP_Exception(_("Could not retrieve message data from the mail server."));
        }

        return $ob;
    }

}
