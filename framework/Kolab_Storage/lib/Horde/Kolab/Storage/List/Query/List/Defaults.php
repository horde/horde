<?php
/**
 * Handles the list of default folders and protects against more than default of
 * a single folder type.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Handles the list of default folders and protects against more than default of
 * a single folder type.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
abstract class Horde_Kolab_Storage_List_Query_List_Defaults
{
    /**
     * Has the list of defaults been assembled completely?
     *
     * @var boolean
     */
    private $_complete = false;

    /**
     * The complete list of defaults.
     *
     * @var array
     */
    private $_defaults = array();

    /**
     * The complete list of personal defaults.
     *
     * @var array
     */
    private $_personal_defaults = array();

    /**
     * A list of duplicate personal defaults.
     *
     * @var array
     */
    private $_duplicate_defaults = array();

    /**
     * Remember a default folder.
     *
     * @param string  $folder   The folder name.
     * @param string  $type     The folder type.
     * @param string  $owner    The folder owner.
     * @param boolean $personal Is this a folder owned by the current user?
     */
    public function rememberDefault($folder, $type, $owner, $personal)
    {
        if (isset($this->_defaults[$owner][$type])) {
            $this->doubleDefault($this->_defaults[$owner][$type], $folder, $owner, $type);
            if (!isset($this->_duplicate_defaults[$type][$owner])) {
                $this->_duplicate_defaults[$type][$owner] = array(
                    $this->_defaults[$owner][$type], $folder
                );
            } else {
                $this->_duplicate_defaults[$type][$owner][] = $folder;
            }
        }
        $this->_defaults[$owner][$type] = $folder;
        if ($personal) {
            $this->_personal_defaults[$type] = $folder;
        }
    }

    /**
     * Return the complete list of default folders available.
     *
     * @return array The list of default folders accessible to the current user.
     */
    public function getDefaults()
    {
        return $this->_defaults;
    }

    /**
     * Reset the list of defaults.
     */
    public function reset()
    {
        $this->_defaults = array();
        $this->_personal_defaults = array();
        $this->_duplicate_defaults = array();
    }

    /**
     * Return the list of personal defaults of the current user.
     *
     * @return array The list of default folder owned by the current user.
     */
    public function getPersonalDefaults()
    {
        return $this->_personal_defaults;
    }

    /**
     * Mark the list of defaults as completed.
     */
    public function markComplete()
    {
        $this->_complete = true;
    }

    /**
     * Query if the list of defaults has been completely assembled.
     *
     * @return boolean True, if the list is complete.
     */
    public function isComplete()
    {
        return $this->_complete;
    }

    /**
     * Return any duplicates.
     *
     * @return array The list of duplicate default folders accessible to the current user.
     */
    public function getDuplicates()
    {
        return $this->_duplicate_defaults;
    }

    /**
     * React on detection of more than one default folder.
     *
     * @param string  $first  The first default folder name.
     * @param string  $second The second default folder name.
     * @param string  $type   The folder type.
     * @param string  $owner  The folder owner.
     */
    abstract protected function doubleDefault($first, $second, $owner, $type);
}