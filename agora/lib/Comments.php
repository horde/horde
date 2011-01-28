<?php
/**
 * Comments display script
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Marko Djukic <marko@oblo.com>
 * @author Duck <duck@obala.net>
 */
class Agora_ViewComments {

    /**
     * Returns all threads of a forum in a threaded view.
     *
     * @param string  $forum_name     The unique name for the forum.
     * @param boolean $bodies         Whether to include message bodies in the view.
     * @param string  $scope          The application that the specified forum belongs to.
     * @param string  $base_url       An alternate link where edit/delete/reply links
     *                                point to.
     * @param string  $template_file  Template file to use.
     *
     * @return string  The HTML code of the thread view.
     */
    static public function render($forum_name, $scope = 'agora', $base_url = null, $template_file = false)
    {
        $forums = &Agora_Messages::singleton($scope);
        $forum_id = $forums->getForumId($forum_name);
        if ($forum_id === null) {
            return '';
        }

        $messages = &Agora_Messages::singleton($scope, $forum_id);
        if ($messages instanceof PEAR_Error) {
            return $messages->getMessage();
        }

        if (($view_bodies = Horde_Util::getPost('bodies')) !== null) {
            $GLOBALS['prefs']->setValue('comments_view_bodies', $view_bodies);
        } else {
            $view_bodies = $GLOBALS['prefs']->getValue('comments_view_bodies');
        }

        if ($messages->_forum['message_count'] == 0) {
            return '';
        }

        $sort_by = Agora::getSortBy('comments');
        $sort_dir = Agora::getSortDir('comments');
        $html = '<div class="header">' . _("Comments") . ' (' . $messages->_forum['message_count'] . ')' . '&nbsp;&nbsp;';
        if (!$GLOBALS['prefs']->isLocked('comments_view_bodies')) {
            $rss = Horde_Util::addParameter(Horde::url('rss/threads.php', true, -1), array('scope' => $scope, 'forum_id' => $forum_id));
            $html .= '<span style="font-size: 0.8em;">';
            $html .= '<form action=' . urldecode($base_url) . ' method="post" name="sorter" style="display: inline;">';
            $html .= _("View") . ' <select name="bodies" onchange="document.sorter.submit()" >';
            $html .= '<option value="2">' . _("Flat") .'</option>';
            $html .= '<option value="1" ' . ($view_bodies == 1 ? 'selected="selected"' : '') . '>' . _("Thread") .'</option>';
            $html .= '</select>';

            if ($view_bodies != '1') {
                $html .= ' ' . _("Sort by") . ' ';
                $html .= '<select name="comments_sortby" onchange="document.sorter.submit()" >';
                $html .= '<option value="message_timestamp" ' . ($sort_by == 'message_timestamp' ? 'selected="selected"' : '') . '>' . _("Date") .'</option>';
                $html .= '<option value="message_author" ' . ($sort_by == 'message_author' ? 'selected="selected"' : '') . '>' . _("Author") .'</option>';
                $html .= '<option value="message_subject" ' . ($sort_by == 'message_subject' ? 'selected="selected"' : '') . '>' . _("Subject") .'</option>';
                $html .= '</select>';
                $html .= ' ' . _("Sort direction") . ' ';
                $html .= '<select name="comments_sortdir" onchange="document.sorter.submit()" >';
                $html .= '<option value="0">' . _("Ascending") .'</option>';
                $html .= '<option value="1" ' . ($sort_dir == 1 ? 'selected="selected"' : '') . '>' . _("Descending") .'</option>';
                $html .= '</select>';
            }
            $html .= '<link rel="alternate" title="' . _("Threads") . '" href="' . $rss . '" type="application/rss+xml" />';
            $html .= ' <a href="' . $rss . '" />RSS</a> ';
            $html .= '</form></span>';
        }
        $html .= '</div>';

        $col_headers = array(
            'message_thread' => _("Subject"),
            'message_thread_class_plain' => 'msgThreadPlain',
            'message_author' => _("Posted by"),
            'message_author_class_plain' => 'msgAuthorPlain',
            'message_timestamp' => _("Date"),
            'message_timestamp_class_plain' => 'msgTimestampPlain'
        );

        if ($view_bodies == 1) {
            $threads = $messages->getThreads(0, true, 'message_thread', 0, true, '', $base_url);
            $html .= $messages->getThreadsUI($threads, $col_headers, true, $template_file);
        } else {
            $thread_page = Horde_Util::getFormData('comments_page', 0);
            $thread_per_page = $GLOBALS['prefs']->getValue('comments_per_page');
            $thread_start = $thread_page * $thread_per_page;

            if (empty($template_file)) {
                $template_file = '/messages/flat.html.php';
            }

            if ($messages->_forum['message_count'] > $thread_per_page && $view_bodies == 2) {
                $vars = new Horde_Variables(array('comments_page' => $thread_page));
                $pager_ob = new Horde_Core_Ui_Pager('comments_page', $vars,
                                                array('num' => $messages->_forum['message_count'],
                                                      'url' => $base_url,
                                                      'perpage' => $thread_per_page));

                $pager_html = $pager_ob->render();
            } else {
                $pager_html = '';
            }

            $threads_list = $messages->getThreads(0, true, $sort_by, $sort_dir, 1, '', $base_url, $thread_start, $thread_per_page);
            if ($threads_list instanceof PEAR_Error) {
                $html .= $threads_list->getDebugInfo();
            } else {
                $html .= $pager_html
                    . $messages->getThreadsUI($threads_list, $col_headers, true, $template_file)
                    . $pager_html;
            }
        }

        return $html;
    }

}
