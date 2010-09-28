<?php

require_once WICKED_BASE . '/lib/Page/StandardPage.php';

/**
 * Wicked BackLinks class.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Tyler Colbert <tyler@colberts.us>
 * @package Wicked
 */
class BackLinks extends Wicked_Page {

    /**
     * Display modes supported by this page.
     *
     * @var array
     */
    var $supportedModes = array(
        WICKED_MODE_DISPLAY => true);

    /**
     * The page that we're displaying backlinks to.
     *
     * @var string
     */
    var $_referrer = null;

    function BackLinks($referrer)
    {
        $this->_referrer = $referrer;
    }

    /**
     * Render this page in display or block mode.
     *
     * @return mixed  Returns contents or PEAR_Error.
     */
    function displayContents($isBlock)
    {
        global $wicked, $notification;

        $summaries = $wicked->getBackLinks($this->_referrer);
        if (is_a($summaries, 'PEAR_Error')) {
            $notification->push('Error retrieving summaries: ' .
                                $summaries->getMessage(), 'horde.error');
            return $summaries;
        }

        Horde::addScriptFile('tables.js', 'horde', true);

        ob_start();
        require WICKED_TEMPLATES . '/pagelist/header.inc';
        foreach ($summaries as $page) {
            if (!empty($page['page_history'])) {
                $page = new StdHistoryPage($page);
            } else {
                $page = new StandardPage($page);
            }
            require WICKED_TEMPLATES . '/pagelist/summary.inc';
        }
        require WICKED_TEMPLATES . '/pagelist/footer.inc';

        return ob_get_clean();
    }

    function pageName()
    {
        return 'BackLinks';
    }

    function pageTitle()
    {
        return sprintf(_("Backlinks: %s"), $this->referrer());
    }

    function referrer()
    {
        return $this->_referrer;
    }

}
