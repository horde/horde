<?php
/**
 * Show bookmarks tagged with a specified set of tags.
 *
 * Copyright 2007-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 */
class Trean_Block_Tagsearch extends Horde_Core_Block
{
    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);
        $this->_name = _("Bookmarks tagged with \"%s\"");
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
            ),
            'tags' => array(
                'name' => _("Tags"),
                'type' => 'text')
        );
    }

    /**
     */
    protected function _title()
    {
        return Horde::url($GLOBALS['registry']->getInitialPage(), true)->link() . sprintf($this->getName(), $this->_params['tags']) . '</a>';
    }

    /**
     */
    protected function _content()
    {
        global $trean_gateway, $registry, $injector, $prefs;

        $template = TREAN_TEMPLATES . '/block/' . $this->_params['template'] . '.inc';

        $html = '';
        $tagger = $injector->getInstance('Trean_Tagger');
        try {
            $ids = $tagger->search(explode(',', $this->_params['tags']), array('user' => array($registry->getAuth())));
        } catch (Trean_Exception $e) {
            $ids = array();
        }

        $bookmarks = $trean_gateway->getBookmarks($ids, array('sortby' => $prefs->getValue('sortby'), 'sortdir' => $prefs->getValue('sortdir')));
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
