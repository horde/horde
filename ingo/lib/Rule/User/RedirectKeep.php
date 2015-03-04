<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * User-defined keep & redirect rule.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Rule_User_RedirectKeep
extends Ingo_Rule_User
{
    /**
     */
    public function __construct()
    {
        parent::__construct();

        $this->flags = self::FLAG_AVAILABLE;
        $this->label = _("Deliver to my Inbox and redirect to...");
        $this->type = self::TYPE_TEXT;
    }

}
