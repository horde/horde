<?php
/**
 * Proved an API to include any single Agora forum thread into any other
 * Horde app through a block.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Marko Djukic <marko@oblo.com>
 */
class Agora_Block_Thread extends Horde_Core_Block
{
    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        $this->_name = _("Single Thread");
    }

    /**
     */
    protected function _params()
    {
        $forumOb = $GLOBALS['injector']->getInstance('Agora_Factory_Driver')->create();
        $forums_list = $forumOb->getForums(0, true, 'forum_name', 0, true);

        $threads = array(
            'name'   => _("Thread"),
            'type'   => 'mlenum',
            'values' => array()
        );

        foreach ($forums_list as $forum) {
            $threadsOb = $GLOBALS['injector']->getInstance('Agora_Factory_Driver')->create('agora', $forum['forum_id']);
            $threads_list = $threadsOb->getThreads();
            foreach ($threads_list as $thread) {
                if (!isset($threads['default'])) {
                    $threads['default'] = $forum['forum_id'] . '.' . $thread['message_id'];
                }
                $threads['values'][$forum['indent'] . $forum['forum_name']][$forum['forum_id'] . '.' . $thread['message_id']] = $thread['message_subject'];
            }
        }

        return array('thread_id' => $threads);
    }

    /**
     */
    protected function _content()
    {
        /* Return empty if we don't have a thread set. */
        if (empty($this->_params['thread_id'])) {
            return '';
        }

        /* Set up the message object. */
        list($forum_id, $message_id) = explode('.', $this->_params['thread_id']);
        $messages = $GLOBALS['injector']->getInstance('Agora_Factory_Driver')->create('agora', $forum_id);

        /* Check if valid thread, otherwise show forum list. */
        if ($messages instanceof PEAR_Error || empty($messages)) {
            throw new Horde_Exception(_("Unable to fetch selected thread."));
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

        return $view->render('block/thread');
    }

}
