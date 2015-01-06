<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based factory for IMP's identity object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Identity extends Horde_Core_Factory_Injector
{
    /**
     * Return the IMP identity instance.
     *
     * @return IMP_Prefs_Identity  The singleton instance.
     */
    public function create(Horde_Injector $injector)
    {
        return $injector->getInstance('Horde_Core_Factory_Identity')->create(null, 'imp');
    }

}
