<?php
/**
 * Provides a list of Agora forums in a block item.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @author  Jan Schneider <jan@horde.org>
 */
class Agora_Block_Forums extends Horde_Block
{
    /**
     */
    public function getName()
    {
        return _("Forums");
    }

    protected function _title()
    {
        return Horde::url('forums.php', true)->link() . $this->getName() . '</a>';
    }

    /**
     */
    protected function _params()
    {
        return array(
            /* Display the last X number of threads. */
            'forum_display' => array(
                $name = _("Only display this many forums (0 to display all forums)"),
                $type = 'int',
                $default = 0,
                $values = $GLOBALS['prefs']->getValue('forums_block_display')
            )
        );
    }

    /**
     */
    protected function _content()
    {
        global $registry;

        /* Set up the forums object. */
        $forums = array(Agora_Messages::singleton());
        if ($GLOBALS['registry']->isAdmin()) {
            foreach ($registry->listApps(array('hidden', 'notoolbar', 'active')) as $scope) {
                if ($registry->hasMethod('hasComments', $scope) &&
                    $registry->callByPackage($scope, 'hasComments') === true) {
                    $forums[] = &Agora_Messages::singleton($scope);
                }
            }
        }

        /* Get the sorting. */
        $sort_by = Agora::getSortBy('forums');
        $sort_dir = Agora::getSortDir('forums');

        /* Get the list of forums. */
        $forums_list = array();
        foreach ($forums as $forum) {
            $scope_forums = $forum->getForums(0, true, $sort_by, $sort_dir, true);
            if ($scope_forums instanceof PEAR_Error) {
                return $scope_forums->getMessage();
            }
            $forums_list = array_merge($forums_list, $scope_forums);
        }

        /* Show a message if no available forums. Don't raise an error
         * as it is not an error to have no forums. */
        if (empty($forums_list)) {
            return _("There are no forums.");
        }

        /* Display only the most recent threads if preference set. */
        if (!empty($this->_params['forum_display'])) {
            $forums_list = array_slice($forums_list, 0, $this->_params['forum_display']);
        }

        /* Set up the column headers. */
        $col_headers = array('forum_name' => _("Forum"), 'message_count' => _("Posts"), 'message_subject' => _("Last Post"), 'message_author' => _("Posted by"), 'message_timestamp' => _("Date"));
        $col_headers = Agora::formatColumnHeaders($col_headers, $sort_by, $sort_dir, 'forums');

        /* Set up the template tags. */
        $view = new Agora_View();
        $view->col_headers = $col_headers;
        $view->forums_list = $forums_list;

        return $view->render('block/forums.html.php');
    }

}
