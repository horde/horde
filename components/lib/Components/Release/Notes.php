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
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
    private $notes = array();

    /**
     * The component that should be released
     *
     * @var Components_Component
     */
    private $_component;

    /**
     * The task output.
     *
     * @var Components_Output
     */
    private $_output;

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
        if ($notes = $component->getReleaseNotesPath()) {
            include $notes;
            if (isset($this->notes['fm']['changes']) &&
                strlen($this->notes['fm']['changes']) > 600) {
                $this->_output->warn(
                    'freecode release notes are longer than 600 characters!'
                );
            }
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
        if (!empty($this->notes['fm']['branch'])
            && $this->notes['name'] != 'Horde') {
            return strtr($this->notes['fm']['branch'], array('Horde ' => 'H'));
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
        if (isset($this->notes['name'])) {
            return $this->notes['name'];
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
        if (isset($this->notes['list'])) {
            return $this->notes['list'];
        }
    }

    /**
     * Return the list of release foci.
     *
     * @return array The main topics of the release.
     */
    public function getFocusList()
    {
        if (isset($this->notes['fm']['focus'])) {
            if (is_array($this->notes['fm']['focus'])) {
                return $this->notes['fm']['focus'];
            } else {
                return array($this->notes['fm']['focus']);
            }
        } else {
            return array();
        }
    }

    /**
     * Return the announcement text.
     *
     * @return string The text.
     */
    public function getAnnouncement()
    {
        if (isset($this->notes['ml']['changes'])) {
            return $this->notes['ml']['changes'];
        }
        return '';
    }

    /**
     * Return the freecode project name.
     *
     * @return string The project name.
     */
    public function getFmProject()
    {
        if (isset($this->notes['fm']['project'])) {
            return $this->notes['fm']['project'];
        }
        return '';
    }

    /**
     * Return the freecode change log.
     *
     * @return string The change log.
     */
    public function getFmChanges()
    {
        if (isset($this->notes['fm']['changes'])) {
            return $this->notes['fm']['changes'];
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
        return !empty($this->notes['ml']);
    }

    /**
     * Does the current component come with freecode information?
     *
     * @return boolean True if freecode information is available.
     */
    public function hasFreecode()
    {
        return !empty($this->notes['fm']);
    }
}