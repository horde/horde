<?php
/**
 * Copyright 2003-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @author   Jan Schneider <jan@horde.org>
 * @author   Tyler Colbert <tyler@colberts.us>
 * @package  Wicked
 */

/**
 * List similar pages.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @author   Jan Schneider <jan@horde.org>
 * @author   Tyler Colbert <tyler@colberts.us>
 * @package  Wicked
 */
class Wicked_Page_LikePages extends Wicked_Page
{
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
        global $injector, $page_output, $wicked;

        $page_output->addScriptFile('tables.js', 'horde');

        $view = $injector->createInstance('Horde_View');
        $content = $view->render('pagelist/header');

        $summaries = $wicked->getLikePages($this->referrer());
        foreach ($summaries as $page) {
            if (!empty($page['page_history'])) {
                $page = new Wicked_Page_StandardHistoryPage($page);
            } else {
                $page = new Wicked_Page_StandardPage($page);
            }
            $content .= $view->renderPartial(
                'pagelist/page',
                array('object' => $page->toView())
            );
        }

        return $content . $view->render('pagelist/footer');
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
