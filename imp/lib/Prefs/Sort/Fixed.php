<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A fixed (read-only) implementation of the sortpref preference.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Prefs_Sort_Fixed extends IMP_Prefs_Sort
{
    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Garbage collection.
     */
    public function gc()
    {
    }

    /**
     * Upgrade the preference from IMP 4 value.
     */
    public function upgradePrefs()
    {
    }

    /**
     * Save the preference to the backend.
     */
    protected function _save()
    {
    }

    /* ArrayAccess methods. */

    public function offsetExists($offset)
    {
        return true;
    }

    public function offsetGet($offset)
    {
        return new IMP_Prefs_Sort_Sortpref(
            $offset,
            IMP::IMAP_SORT_DATE,
            1
        );
    }

    public function offsetSet($offset, $value)
    {
    }

    public function offsetUnset($offset)
    {
    }

}
