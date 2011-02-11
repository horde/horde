<?php
/**
 * Show the highest-rated bookmarks.
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 */
class Trean_Block_Highestrated extends Horde_Core_Block
{
    /**
     */
    public function getName()
    {
        return _("Highest-rated Bookmarks");
    }

    /**
     */
    protected function _params()
    {
        return array(
            'rows' => array(
                'name' => _("Number of bookmarks to show"),
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
        return Horde::url($GLOBALS['registry']->getInitialPage(), true)->link() . $this->getName() . '</a>';
    }

    /**
     */
    protected function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';
        require_once TREAN_TEMPLATES . '/star_rating_helper.php';

        $template = TREAN_TEMPLATES . '/block/' . $this->_params['template'] . '.inc';

        $html = '';
        $bookmarks = $GLOBALS['trean_shares']->sortBookmarks('rating', 1, 0, $this->_params['rows']);
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
