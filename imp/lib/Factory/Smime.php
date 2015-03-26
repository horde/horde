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
 * A Horde_Injector based factory for the IMP_Smime object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Smime extends Horde_Core_Factory_Injector
{
    /**
     * Return the IMP_Smime instance.
     *
     * @return IMP_Smime  The singleton instance.
     */
    public function create(Horde_Injector $injector)
    {
        return new IMP_Smime(
            $injector->getInstance('Horde_Core_Factory_Crypt')->create(
                'Horde_Crypt_Smime'
            )
        );
    }

}
