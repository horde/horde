<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @link      http://pear.horde.org/index.php?package=Ingo
 * @package   Ingo
 */

/**
 * A Horde_Injector based share factory for Ingo.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @link      http://pear.horde.org/index.php?package=Ingo
 * @package   Ingo
 */
class Ingo_Factory_Shares extends Horde_Core_Factory_Injector
{
    /**
     */
    public function create(Horde_Injector $injector)
    {
        global $injector, $session;

        return $session->exists('ingo', 'personal_share')
            ? $injector->getInstance('Horde_Core_Factory_Share')->create()
            : null;
    }

}
