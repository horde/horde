<?php
/**
 * Show bookmarks.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
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
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        $this->_name = _("Bookmarks");
    }

    /**
     */
    protected  function _params()
    {
        return array(
            'bookmarks' => array(
                'name' => _("Sort by"),
                'type' => 'enum',
                'default' => 'title',
                'values' => array(
                    'title' => _("Title"),
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
        return Horde::url($registry->getInitialPage(), true)->link() . _("Bookmarks") . '</a>';
    }

    /**
     */
    protected function _content()
    {
        require_once __DIR__ . '/../base.php';

        $template = TREAN_TEMPLATES . '/block/' . $this->_params['template'] . '.inc';

        $sortby = 'title';
        $sortdir = 0;
        switch ($this->_params['bookmarks']) {
        case 'most_clicked':
            $sortby = 'clicks';
            $sortdir = 1;
            break;
        }

        $html = '';
        $bookmarks = $GLOBALS['trean_gateway']->listBookmarks($sortby, $sortdir, 0, $this->_params['rows']);
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
}
