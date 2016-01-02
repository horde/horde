<?php
/**
 * Copyright 2007-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
/**
 * The Ansel_View_Results:: class wraps display of images/galleries from
 * multiple parent sources..
 *
 * Copyright 2007-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class Ansel_View_Results extends Ansel_View_Ansel
{
    /**
     * Instance of our tag search
     *
     * @var Ansel_TagBrowser
     */
    protected $_browser;

    /**
     * Gallery owner id
     *
     * @var string
     */
    protected $_owner;

    /**
     * The current page
     *
     * @var integer
     */
    protected $_page;

    /**
     * Number of resources per page.
     *
     * @var integer
     */
    protected $_perPage;

    /**
     * Contructor.
     *
     * Also handles any actions from the view.
     *
     * @return Ansel_View_Results
     */
    public function __construct(array $params = array())
    {
        global $prefs, $conf, $injector, $notification;

        $ansel_storage = $injector->getInstance('Ansel_Storage');
        $this->_owner = Horde_Util::getFormData('owner', '');
        $this->_browser = new Ansel_TagBrowser(
            $injector->getInstance('Ansel_Tagger'), null, $this->_owner);

        $this->_page = Horde_Util::getFormData('page', 0);
        $actionID = Horde_Util::getFormData('actionID', '');
        $image_id = Horde_Util::getFormData('image');
        $vars = Horde_Variables::getDefaultVariables();

        // Number perpage from prefs or config.
        $this->_perPage = min(
            $prefs->getValue('tilesperpage'),
            $conf['thumbnail']['perpage']);

        // Common image actions.
        if (Ansel_ActionHandler::imageActions($actionID)) {
            Ansel::getUrlFor('view', array('view' => 'Results'), true)->redirect();
            exit;
        }

        // Tag browsing actions.
        switch ($actionID) {
        case 'remove':
            $tag = Horde_Util::getFormData('tag');
            if (isset($tag)) {
                $this->_browser->removeTag($tag);
                $this->_browser->save();
            }
            break;

        case 'add':
        default:
            $tag = Horde_Util::getFormData('tag');
            if (isset($tag)) {
                $this->_browser->addTag($tag);
                $this->_browser->save();
            }
            break;
        }

        // Check for empty tag search and redirect if empty
        if ($this->_browser->tagCount() < 1) {
            Horde::url('browse.php', true)->redirect();
            exit;
        }
    }

    /**
     * Return the title for this view.
     *
     * @return string The title for this view.
     */
    public function getTitle()
    {
        return (!empty($this->_owner))
                ? sprintf(_("Searching %s's photos tagged: "), $this->_owner)
                : _("Searching all photos tagged: ");
    }

    /**
     * Get the HTML representing this view.
     *
     * @return string  The HTML
     */
    public function html()
    {
        global $conf, $prefs;

        $view = $GLOBALS['injector']->getInstance('Horde_View');
        $view->addTemplatePath(ANSEL_TEMPLATES . '/view');
        $view->perPage = $this->_perPage;

        // Ansel Storage
        $ansel_storage = $GLOBALS['injector']->getInstance('Ansel_Storage');

        // Get the slice of galleries/images to view on this page.
        try {
            $view->results = $this->_browser->getSlice($this->_page, $this->_perPage);
        } catch (Ansel_Exception $e) {
            Horde::log($e->getMessage(), 'ERR');
            return _("An error has occured retrieving the image. Details have been logged.");
        }
        $view->total = $this->_browser->count();
        $view->total = $view->total['galleries'] + $view->total['images'];

        // The number of resources to display on this page.
        $view->numimages = count($view->results);
        $view->tilesperrow = $prefs->getValue('tilesperrow');
        $view->cellwidth = round(100 / $view->tilesperrow);

        // Get any related tags to display.
        if ($conf['tags']['relatedtags']) {
            $view->rtags = $this->_browser->getRelatedTags();
            $view->taglinks = Ansel::getTagLinks($view->rtags, 'add');
        }
        $vars = Horde_Variables::getDefaultVariables();
        $option_move = $option_copy = $ansel_storage->countGalleries(
            $GLOBALS['registry']->getAuth(),
            array('perm' => Horde_Perms::EDIT));

        $this->_pagestart = ($this->_page * $this->_perPage) + 1;
        $this->_pageend = min(
            $this->_pagestart + $view->numimages - 1,
            $this->_pagestart + $this->_perPage - 1);

        $view->pageStart = $this->_pageStart;
        $view->pageEnd = $this->_pageEnd;
        $view->owner = $this->_owner;
        $view->tagTrail = $this->_browser->getTagTrail();
        $view->title = $this->getTitle();
        $view->params = $this->_params;

        $view->style = Ansel::getStyleDefinition(
            $GLOBALS['prefs']->getValue('default_gallerystyle'));

        $viewurl = Horde::url('view.php')->add(array(
            'view' => 'Results',
            'actionID' => 'add'));
        $view->pager = new Horde_Core_Ui_Pager(
            'page',
            $vars,
            array(
                'num' => $view->total,
                'url' => $viewurl,
                'perpage' => $this->_perPage)
        );
        $GLOBALS['page_output']->addScriptFile('views/common.js');
        $GLOBALS['page_output']->addScriptFile('views/gallery.js');

        return $view->render('results');
    }

    public function viewType()
    {
        return 'Results';
    }

    public function getGalleryCrumbData()
    {
        return array();
    }

}
