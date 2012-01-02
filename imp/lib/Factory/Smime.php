<?php
/**
 * A Horde_Injector based factory for the IMP_Crypt_Smime object.
 *
 * PHP version 5
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 * @package  IMP
 */

/**
 * A Horde_Injector based factory for the IMP_Crypt_Smime object.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @link     http://pear.horde.org/index.php?package=IMP
 * @package  IMP
 */
class IMP_Factory_Smime extends Horde_Core_Factory_Injector
{
    /**
     * Return the IMP_Crypt_Smime instance.
     *
     * @return IMP_Crypt_Smime  The singleton instance.
     */
    public function create(Horde_Injector $injector)
    {
        return $injector->getInstance('Horde_Core_Factory_Crypt')->create('IMP_Crypt_Smime');
    }

}
