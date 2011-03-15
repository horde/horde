<?php
/**
 * Announce releases on the mailing lists.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Release
 * @author   Mike Hardy
 * @author   Jan Schneider <jan@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Release
 */

/**
 * Announce releases on the mailing lists.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Release
 * @author   Mike Hardy
 * @author   Jan Schneider <jan@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Release
 */
class Horde_Release_MailingList
{
    /**
     * Component name.
     *
     * @param string
     */
    private $_component;

    /**
     * Component release name.
     *
     * @param string
     */
    private $_name;

    /**
     * Branch name.
     *
     * @param string
     */
    private $_branch;

    /**
     * Message sender.
     *
     * @param string
     */
    private $_from;

    /**
     * The mailing list to send to.
     *
     * @param string
     */
    private $_list;

    /**
     * The version to be released.
     *
     * @param string
     */
    private $_version;

    /**
     * The version released previously.
     *
     * @param string
     */
    private $_old_version;

    /**
     * The list of focus tags.
     *
     * @param array
     */
    private $_tag_list;

    /**
     * Constructor.
     *
     * @param string $component   The component name.
     * @param string $name        The component release name.
     * @param string $branch      The component branch (H3, H4, ...).
     * @param string $from        The mail address of the person sending the
     *                            announcement.
     * @param string $list        The mailing list the announcement should be
     *                            sent to.
     * @param string $version     The version to be released.
     * @param string $old_version The version released before this one.
     * @param array  $tag_list    Release focus.
     */
    public function __construct(
        $component, $name, $branch, $from, $list, $version, $old_version, $tag_list
    ) {
        $this->_component = $component;
        $this->_name = $name;
        $this->_branch = $branch;
        $this->_from = $from;
        $this->_list = $list;
        $this->_version = $version;
        $this->_old_version = $old_version;
        $this->_tag_list = $tag_list;
    }

    /**
     * Retrieve the message headers for the announcement mail.
     *
     * @return array A set of message headers.
     */
    public function getHeaders()
    {
        $ml = (!empty($this->_list)) ? $this->_list : $this->_component;
        if (substr($ml, 0, 6) == 'horde-') {
            $ml = 'horde';
        }

        $to = "announce@lists.horde.org, vendor@lists.horde.org, $ml@lists.horde.org";
        if (!$this->_isLatest()) {
            $to .= ', i18n@lists.horde.org';
        }

        $subject = $this->_name;
        if (!empty($this->_branch)) {
            $subject .= ' ' . $this->_branch . ' (' . $this->_version . ')';
        } else {
            $subject .= ' ' . $this->_version;
        }
        if ($this->_isLatest()) {
            $subject .= ' (final)';
        }
        if (in_array(Horde_Release::FOCUS_MAJORSECURITY, $this->_tag_list)) {
            $subject = '[SECURITY] ' . $subject;
        }

        return array(
            'From' => $this->_from,
            'To' => $to,
            'Subject' => $subject
        );
    }

    private function _isLatest()
    {
        if (preg_match('/([.\d]+)\-(.*)/', $this->_version, $matches)
            && !preg_match('/^pl\d/', $matches[2])) {
            return false;
        }
        return true;
    }
}