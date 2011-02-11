<?php
/**
 * Show bookmarks.
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Joel Vandal <joel@scopserv.com>
 */
class Trean_Block_Bookmarks extends Horde_Core_Block
{
    /**
     */
    private $_folder = null;


    /**
     */
    public function getName()
    {
        return _("Bookmarks");
    }

    /**
     */
    protected  function _params()
    {
        require_once dirname(__FILE__) . '/../base.php';

        /* Get folders to display. */
        $folders = Trean::listFolders(Horde_Perms::READ);
        $default = null;
        if ($folders instanceof PEAR_Error) {
            $GLOBALS['notification']->push(sprintf(_("An error occured listing folders: %s"), $folders->getMessage()), 'horde.error');
        } else {
            foreach ($folders as $key => $folder) {
                if (is_null($default)) {
                    $default = $folder->getId();
                }
                $values[$folder->getId()] = $folder->get('name');
            }
        }

        return array(
            'folder' => array(
                'name' => _("Folder"),
                'type' => 'enum',
                'default' => $default,
                'values' => $values
            ),
            'bookmarks' => array(
                'name' => _("Sort by"),
                'type' => 'enum',
                'default' => 'title',
                'values' => array(
                    'title' => _("Title"),
                    'highest_rated' => _("Highest Rated"),
                    'most_clicked' => _("Most Clicked")
                )
            ),
            'rows' => array(
                'name' => _("Display Rows"),
                'type' => 'enum',
                'default' => '10',
                'values' => array(
                    '10' => _("10 rows"),
                    '15' => _("15 rows"),
                    '25' => _("25 rows")
                )
            ),
            'template' => array(
                'name' => _("Template"),
                'type' => 'enum',
                'default' => '1line',
                'values' => array(
                    'standard' => _("3 Line"),
                    '2line' => _("2 Line"),
                    '1line' => _("1 Line")
                )
            )
        );
    }

    /**
     */
    protected function _title()
    {
        global $registry;

        $folder = $this->_getFolder();
        if ($folder instanceof PEAR_Error) {
            $name = $registry->get('name');
        } else {
            $name = $folder->get('name');
            if (!$name) {
                $name = $this->getName();
            }
        }

        return Horde::url($registry->getInitialPage(), true)->link() . $name . '</a>';
    }

    /**
     */
    protected function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';
        require_once TREAN_TEMPLATES . '/star_rating_helper.php';

        $template = TREAN_TEMPLATES . '/block/' . $this->_params['template'] . '.inc';

        $folder = $this->_getFolder();
        if ($folder instanceof PEAR_Error) {
            return $folder;
        }

        $sortby = 'title';
        $sortdir = 0;
        switch ($this->_params['bookmarks']) {
        case 'highest_rated':
            $sortby = 'rating';
            $sortdir = 1;
            break;

        case 'most_clicked':
            $sortby = 'clicks';
            $sortdir = 1;
            break;
        }

        $html = '';
        $bookmarks = $folder->listBookmarks($sortby, $sortdir, 0, $this->_params['rows']);
        foreach ($bookmarks as $bookmark) {
            ob_start();
            require $template;
            $html .= '<div class="linedRow">' . ob_get_clean() . '</div>';
        }

        if (!$bookmarks) {
            return '<p><em>' . _("No bookmarks to display") . '</em></p>';
        }

        return $html;
    }

    /**
     */
    private function _getFolder()
    {
        require_once dirname(__FILE__) . '/../base.php';

        if ($this->_folder == null) {
            $this->_folder = $GLOBALS['trean_shares']->getFolder($this->_params['folder']);
        }

        return $this->_folder;
    }

}
