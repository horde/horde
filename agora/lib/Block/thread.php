<?php

$block_name = _("Single Thread");

/**
 * Agora Forum Thread Block Class
 *
 * This file provides an api to include an Agora forum's thread into any other
 * Horde app through the Horde_Blocks, by extending the Horde_Blocks class.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @package Horde_Block
 */
class Horde_Block_agora_thread extends Horde_Block
{
    /**
     *
     * @var string
     */
    protected $_app = 'agora';

    /**
     *
     * @return array
     */
    protected function _params()
    {
        $forumOb = &Agora_Messages::singleton();
        $forums_list = $forumOb->getForums(0, true, 'forum_name', 0, true);

        $threads = array('name'   => _("Thread"),
                         'type'   => 'mlenum',
                         'values' => array());

        foreach ($forums_list as $forum_id => $forum) {
            $threadsOb = &Agora_Messages::singleton('agora', $forum_id);
            $threads_list = $threadsOb->getThreads();
            foreach ($threads_list as $thread_id => $thread) {
                if (!isset($threads['default'])) {
                    $threads['default'] = $forum_id . '.' . $thread['message_id'];
                }
                $threads['values'][$forum['indent'] . $forum['forum_name']][$forum_id . '.' . $thread['message_id']] = $thread['message_subject'];
            }
        }

        return array('thread_id' => $threads);
    }

    /**
     *
     * @return string
     */
    function _title()
    {
        return _("Single Thread");
    }

    /**
     *
     * @return string
     * @throws Horde_Block_Exception
     */
    function _content()
    {
        /* Return empty if we don't have a thread set. */
        if (empty($this->_params['thread_id'])) {
            return '';
        }

        /* Set up the message object. */
        list($forum_id, $message_id) = explode('.', $this->_params['thread_id']);
        $messages = &Agora_Messages::singleton('agora', $forum_id);

        /* Check if valid thread, otherwise show forum list. */
        if ($messages instanceof PEAR_Error || empty($messages)) {
            throw new Horde_Block_Exception(_("Unable to fetch selected thread."));
        }

        /* Get the sorting. */
        $sort_by = Agora::getSortBy('threads');
        $sort_dir = Agora::getSortDir('threads');
        $view_bodies = $GLOBALS['prefs']->getValue('thread_view_bodies');

        /* Get the message array and the sorted thread list. */
        $threads_list = $messages->getThreads($messages->getThreadRoot($message_id), true, $sort_by, $sort_dir, $view_bodies, Horde::selfUrl());

        /* Set up the column headers. */
        $col_headers = array(array('message_thread' => _("Thread"), 'message_subject' => _("Subject")), 'message_author' => _("Posted by"), 'message_timestamp' => _("Date"));
        $col_headers = Agora::formatColumnHeaders($col_headers, $sort_by, $sort_dir, 'threads');

        /* Set up the template tags. */
        $view = new Agora_View();
        $view->col_headers = $col_headers;
        $view->threads_list = $threads_list;
        $view->threads_list_header = _("Thread List");
        $view->thread_view_bodies = $view_bodies;

        return $view->render('block/thread.html.php');
    }

}
