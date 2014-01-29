<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based factory for the IMP_Maillog object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Maillog extends Horde_Core_Factory_Injector
{
    /**
     * Return the IMP_Maillog instance.
     *
     * @return IMP_Maillog|null  The singleton instance, or null if maillog
     *                           is not available.
     */
    public function create(Horde_Injector $injector)
    {
        return empty($GLOBALS['conf']['maillog']['use_maillog'])
            ? null
            : new IMP_Maillog();
    }

}
