<?php
/**
 * Wicked LikePages class.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Tyler Colbert <tyler@colberts.us>
 * @package Wicked
 */
class Wicked_Page_LikePages extends Wicked_Page {

    /**
     * Display modes supported by this page.
     *
     * @var array
     */
    public $supportedModes = array(
        Wicked::MODE_DISPLAY => true);

    /**
     * The page that we're displaying similar pages to.
     *
     * @var string
     */
    protected $_referrer = null;

    public function __construct($referrer)
    {
        $this->_referrer = $referrer;
    }

    /**
     * Renders this page in display or block mode.
     *
     * @return string  The contents.
     * @throws Wicked_Exception
     */
    public function displayContents($isBlock)
    {
        $referrer = $this->referrer();
        $summaries = $GLOBALS['wicked']->getLikePages($referrer);
        Horde::addScriptFile('tables.js', 'horde', true);
        ob_start();
        require WICKED_TEMPLATES . '/pagelist/header.inc';
        foreach ($summaries as $page) {
            if (!empty($page['page_history'])) {
                $page = new Wicked_Page_StandardHistoryPage($page);
            } else {
                $page = new Wicked_Page_StandardPage($page);
            }
            require WICKED_TEMPLATES . '/pagelist/summary.inc';
        }
        require WICKED_TEMPLATES . '/pagelist/footer.inc';
        return ob_get_clean();
    }

    public function pageName()
    {
        return 'LikePages';
    }

    public function pageTitle()
    {
        return sprintf(_("Similar Pages: %s"), $this->referrer());
    }

    public function referrer()
    {
        return $this->_referrer;
    }

}
