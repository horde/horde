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
 * Spam reporting driver base interface.
 *
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
interface IMP_Spam_Base
{
    /**
     * Reports a list of messages as innocent/spam.
     *
     * @param IMP_Contents $contents  A contents object.
     * @param integer $action         Either IMP_Spam::SPAM or
     *                                IMP_Spam::INNOCENT.
     *
     * @return boolean  True on success.
     */
    public function report(IMP_Contents $contents, $action);

}
