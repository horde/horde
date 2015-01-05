<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Manage the expanded folders list.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ftree_Prefs_Expanded extends IMP_Ftree_Prefs
{
    /* Constants for nav_expanded. */
    const NO = 0;
    const YES = 1;
    const LAST = 2;

    /**
     * Value of nav_expanded pref.
     *
     * @var integer
     */
    protected $_expanded;

    /**
     * Constructor.
     */
    public function __construct()
    {
        global $prefs;

        if (($folders = @unserialize($prefs->getValue('expanded_folders'))) &&
            is_array($folders)) {
            $this->_data = $folders;
        }

        $this->_expanded = $prefs->getValue('nav_expanded');
        $this->_locked = $prefs->isLocked('expanded_folders');
    }

    /**
     */
    public function shutdown()
    {
        $GLOBALS['prefs']->setValue('expanded_folders', serialize($this->_data));
    }

    /**
     */
    public function offsetGet($offset)
    {
        switch ($this->_expanded) {
        case self::NO:
            return false;

        case self::YES:
            return true;

        case self::LAST:
            return parent::offsetGet($offset);
        }
    }

}
