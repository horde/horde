<?php
/**
 * The Kolab specific notepads handler.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Mnemo
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/apache
 * @link     http://www.horde.org/apps/mnemo
 */

/**
 * The Kolab specific notepads handler.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category Horde
 * @package  Mnemo
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/apache
 * @link     http://www.horde.org/apps/mnemo
 */
class Mnemo_Notepads_Kolab
extends Mnemo_Notepads_Base
{
    /**
     * Return the name of the default share.
     *
     * @return string The name of a default share.
     */
    protected function getDefaultShareName()
    {
        return _("Notes");
    }
}