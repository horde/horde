<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Turba
 */

/**
 * Horde_Injector based factory for the Turba tagger.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Turba
 */
class Turba_Factory_Tagger extends Horde_Core_Factory_Injector
{
    /**
     * Return the tagger instance.
     *
     * @return Horde_Core_Tagger  Tagger instance.
     * @throws Horde_Exception
     */
    public function create(Horde_Injector $injector)
    {
        return empty($GLOBALS['conf']['tags']['enabled'])
            ? new Horde_Core_Tagger_Null()
            : new Turba_Tagger();
    }

}
