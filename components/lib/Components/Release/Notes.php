<?php
/**
 * Copyright 2011-2016 Horde LLC (http://www.horde.org/)
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

/**
 * This class deals with the information associated to a release.
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
        $this->_setReleaseNotes();
    }

    /**
     * Populates the release information for the current component.
     */
    protected function _setReleaseNotes()
    {
        if (!($file = $this->_component->getReleaseNotesPath())) {
            return;
        }
        if (basename($file) == 'release.yml') {
            $version = Components_Helper_Version::parsePearVersion(
                $this->_component->getVersion()
            );
            $description = Horde_String::lower($version->description);
            if (strpos($description, 'release') === false) {
                $description .= ' release';
            }
            $infofile = dirname($file) . '/horde.yml';
            try {
                $info = Horde_Yaml::loadFile($infofile);
            } catch (Horde_Yaml_Exception $e) {
                throw new Components_Exception($e);
            }
            $this->_notes['name'] = $info['name'];
            if (isset($info['list'])) {
                $this->_notes['list'] = $info['list'];
            }
            try {
                $release = Horde_Yaml::loadFile($file);
            } catch (Horde_Yaml_Exception $e) {
                throw new Components_Exception($e);
            }
            if (isset($release['branch'])) {
                $this->_notes['branch'] = $release['branch'];
            }
            $this->_notes['security'] = $release['security'];
            if (!is_array(reset($release['changes']))) {
                $release['changes'] = array($release['changes']);
            }
            $currentSection = null;
            $changes = '';
            foreach ($release['changes'] as $section => $sectionChanges) {
                if ($section != $currentSection) {
                    $changes .= "\n\n" . $section . ':';
                    $currentSection = $section;
                }
                foreach ($sectionChanges as $change) {
                    $changes .= "\n    * " . $change;
                }
            }
            $this->_notes['changes'] = sprintf(
                'The Horde Team is pleased to announce the %s%s of the %s version %s.

%s

For upgrading instructions, please see
http://www.horde.org/apps/%s/docs/UPGRADING

For detailed installation and configuration instructions, please see
http://www.horde.org/apps/%s/docs/INSTALL
%s
The major changes compared to the %s version %s are:%s',
                $version->subversion
                    ? NumberFormatter::create('en_US', NumberFormatter::ORDINAL)
                        ->format($version->subversion) . ' '
                    : '',
                $description,
                $info['full'],
                $version->version,
                $info['description'],
                $info['id'],
                $info['id'],
                $release['additional']
                    ? "\n" . implode("\n\n", $release['additional']) . "\n"
                    : '',
                $info['name'],
                $this->_component->getPreviousVersion(),
                $changes
            );
        } else {
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
        if (!empty($this->_notes['branch']) &&
            $this->_notes['name'] != 'Horde') {
            return strtr($this->_notes['branch'], array('Horde ' => 'H'));
        }
        return '';
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
        }
        return $this->_component->getName();
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