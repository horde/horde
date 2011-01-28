<?php

$block_name = _("Threads");

/**
 * Agora Forum Thread Block Class
 *
 * This file provides an api to include an Agora forum's thread into any other
 * Horde app through the Horde_Blocks, by extending the Horde_Blocks class.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Block
 */
class Horde_Block_agora_threads extends Horde_Block
{
    /**
     * @var array
     */
    private $_threads = array();

    /**
     * @var string
     */
    protected $_app = 'agora';

    /**
     * @return array
     */
    protected function _params()
    {
        $forums = Agora_Messages::singleton();

        /* Get the list of forums to display. */
        $forum_id = array(
            'name' => _("Forum"),
            'type' => 'enum',
            'values' => $forums->getForums(0, false, 'forum_name', 0, !$GLOBALS['registry']->isAdmin()),
        );

        /* Display the last X number of threads. */
        $thread_display = array(
            'name' => _("Only display this many threads (0 to display all threads)"),
            'type' => 'int',
            'default' => 0,
            'values' => $GLOBALS['prefs']->getValue('threads_block_display'),
        );

        return array('forum_id' => $forum_id,
                     'thread_display' => $thread_display);
    }

    /**
     * @return string
     */
    protected function _title()
    {
        if (!isset($this->_params['forum_id'])) {
            return _("Threads");
        }

        if (empty($this->_threads)) {
            $this->_threads = &Agora_Messages::singleton('agora', $this->_params['forum_id']);
            if ($this->_threads instanceof PEAR_Error) {
                return _("Threads");
            }
        }

        $title = sprintf(_("Threads in \"%s\""), $this->_threads->_forum['forum_name']);
        $url = Horde::url('threads.php', true);
        if (!empty($scope)) {
            $url = Horde_Util::addParameter($url, 'scope', $scope);
        }

        return Horde::link(Agora::setAgoraId($this->_params['forum_id'], null, $url))
            . $title . '</a>';
    }

    /**
     * @return string
     * @throws Horde_Block_Exception
     */
    protected function _content()
    {
        if (!isset($this->_params['forum_id'])) {
            throw new Horde_Block_Exception(_("No forum selected"));
        }

        if (empty($this->_threads)) {
            $this->_threads = &Agora_Messages::singleton('agora', $this->_params['forum_id']);
            if ($this->_threads instanceof PEAR_Error) {
                throw new Horde_Block_Exception(_("Unable to fetch threads for selected forum."));
            }
        }

        /* Get the sorting. */
        $sort_by = Agora::getSortBy('threads');
        $sort_dir = Agora::getSortDir('threads');

        /* Get a list of threads and display only the most recent if
         * preference is set. */
        $threads_list = $this->_threads->getThreads(0, false, $sort_by, $sort_dir, false, Horde::selfUrl(), null, 0, !empty($this->_params['thread_display']) ? $this->_params['thread_display'] : null);

        /* Show a message if no available threads. Don't raise an error
         * as it is not an error to have no threads. */
        if (empty($threads_list)) {
            return _("No available threads.");
        }

        /* Set up the column headers. */
        $col_headers = array('message_subject' => _("Subject"), 'message_author' => _("Posted by"), 'message_timestamp' => _("Date"));
        $col_headers = Agora::formatColumnHeaders($col_headers, $sort_by, $sort_dir, 'threads');

        /* Set up the template tags. */
        $view = new Agora_View();
        $view->col_headers = $col_headers;
        $view->threads = $threads_list;

        return $view->render('block/threads.html.php');
    }

}
