<?php
/**
 * Copyright 2004-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @category  Horde
 * @copyright 2004-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL
 * @package   Spam
 */

/**
 * Spam/innocent reporting base class.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2004-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL
 * @package   Spam
 */
class Horde_Spam
{
    /* Action constants. */
    const INNOCENT = 1;
    const SPAM = 2;
}
