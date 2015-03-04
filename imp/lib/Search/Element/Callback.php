<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Interface implementing callbacks to reduce/filter search results.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
interface IMP_Search_Element_Callback
{
    /**
     * Callback allowing search results to be reduced/filtered.
     *
     * @param IMP_Mailbox $mbox  Mailbox.
     * @param array $ids         Sorted ID list.
     *
     * @return array  Sorted ID list.
     */
    public function searchCallback(IMP_Mailbox $mbox, array $ids);

}
