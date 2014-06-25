<?php
/**
 * Components_Release_Notes:: deals with the information associated to a
 * release.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Release_Notes:: deals with the information associated to a
 * release.
 *
 * Copyright 2011-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Release_Notes
{
    /**
     * The release information.
     *
     * @var array
     */
    protected $_notes = array();

    /**
     * The component that should be released
     *
     * @var Components_Component
     */
    protected $_component;

    /**
     * The task output.
     *
     * @var Components_Output
     */
    protected $_output;

    /**
     * Constructor.
     *
     * @param Components_Output $output Accepts output.
     */
    public function __construct(Components_Output $output)
    {
        $this->_output = $output;
    }

    /**
     * Set the component this task should act upon.
     *
     * @param Components_Component $component The component to be released.
     *
     * @return NULL
     */
    public function setComponent(Components_Component $component)
    {
        $this->_component = $component;
        if ($file = $component->getReleaseNotesPath()) {
            $this->_notes = include $file;
        }
    }

    /**
     * The branch information for this component. This is empty for framework
     * components and the Horde base application and has a value like "H3",
     * "H4", etc. for applications.
     *
     * @return string The branch name.
     */
    public function getBranch()
    {
        if (!empty($this->_notes['branch'])
            && $this->_notes['name'] != 'Horde') {
            return strtr($this->_notes['branch'], array('Horde ' => 'H'));
        } else {
            return '';
        }
    }

    /**
     * Returns the release name.
     *
     * @return string The release name.
     */
    public function getName()
    {
        if (isset($this->_notes['name'])) {
            return $this->_notes['name'];
        } else {
            return $this->_component->getName();
        }
    }

    /**
     * Returns the specific mailing list that the release announcement for this
     * component should be sent to.
     *
     * @return string|null The mailing list.
     */
    public function getList()
    {
        if (isset($this->_notes['list'])) {
            return $this->_notes['list'];
        }
    }

    /**
     * Returns whether the release is a security release.
     *
     * @return boolean  A security release?
     */
    public function getSecurity()
    {
        return !empty($this->_notes['security']);
    }

    /**
     * Return the announcement text.
     *
     * @return string The text.
     */
    public function getAnnouncement()
    {
        if (isset($this->_notes['changes'])) {
            return $this->_notes['changes'];
        }
        return '';
    }

    /**
     * Does the current component come with release notes?
     *
     * @return boolean True if release notes are available.
     */
    public function hasNotes()
    {
        return !empty($this->_notes['changes']);
    }
}