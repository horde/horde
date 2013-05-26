<?php
/**
 * The Ansel_View_List:: provides a view for handling lists of galleries.
 *
 * Copyright 2008-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @TODO: should extend Base, not Ansel
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_View_List extends Ansel_View_Ansel
{
    /**
     * The owner we are grouping by, if any.
     *
     * @var string
     */
    protected $_owner;

    /**
     *  @TODO
     *
     * @var boolean
     */
    protected $_special;

    /**
     * The current page number of the view.
     *
     * @var integer
     */
    protected $_page;

    /**
     * The Horde_View used to render this view.
     *
     * @var Horde_View
     */
    protected $_view;

    /**
     * Const'r
     *
     * @param array $params  Any parameters that the view might need.
     * <pre>
     *  In addition to the params taken by Ansel_View_Gallery, this view
     *  can also take:
     *
     *  groupby      -  Group the results (owner)
     *
     *  owner        -  The owner to group by
     *
     *  tags         -  Limit to galleries matching tags
     *
     *  gallery_ids  -  No fitering, just show these galleries
     *
     *  pager_url    -  The url for the pager to use see Ansel_Gallery for
     *                  more information on the url parameters.
     */
    public function __construct(array $params = array())
    {
        global $prefs, $notification, $registry;

        parent::__construct($params);

        $ansel_storage = $GLOBALS['injector']->getInstance('Ansel_Storage');

        // View
        $this->_view = $GLOBALS['injector']->createInstance('Horde_View');
        $this->_view->addTemplatePath(ANSEL_TEMPLATES . '/view');
        $this->_view->sortBy = !empty($this->_params['sort']) ?
           $this->_params['sort'] :
           'name';
        $this->_view->sortDir = isset($this->_params['sort_dir']) ?
           $this->_params['sort_dir'] :
           0;

        // Check for grouping.
        if (empty($this->_params['groupby'])) {
            $this->_view->groupby = Horde_Util::getFormData(
              'groupby',
              $prefs->getValue('groupby'));
        } else {
            $this->_view->groupby = $this->_params['groupby'];
        }
        $this->_view->gPerPage = $prefs->getValue('tilesperpage');

        // Listing a single user?
        if (empty($this->_params['owner'])) {
            $this->_owner = Horde_Util::getFormData('owner');
            $this->_owner = empty($this->_owner) ? null : $this->_owner;
        } else {
            $this->_owner = $this->_params['owner'];
        }

        // Special?
        $this->_special = Horde_Util::getFormData('special');
        if (!$this->_owner && !$this->_special && $this->_view->groupby != 'none' ) {
            Ansel::getUrlFor(
              'group',
              array('groupby' => $this->_view->groupby)
            )->redirect();
            exit;
        }

        // If we aren't supplied with a page number, default to page 0.
        if (isset($this->_params['page'])) {
            $this->_page = $this->_params['page'];
        } else {
            $this->_page = Horde_Util::getFormData('page', 0);
        }

        // If we are calling from the api, we can just pass a list of ids
        if (!empty($this->_params['api']) && is_array($this->_params['gallery_ids'])) {
            $this->_view->start = $this->_page * $this->_view->gPerPage;
            $this->_view->numGalleries = count($this->_params['gallery_ids']);
            if ($this->_view->numGalleries > $this->_view->start) {
                $getThese = array_slice(
                    $this->_params['gallery_ids'],
                    $this->_view->start,
                    $this->_view->gPerPage);
                $this->_view->galleryList = $ansel_storage->getGalleries($getThese);
            } else {
                $this->_view->galleryList = array();
            }
        } else {
            // Set list filter/title
            $filter = array();
            if (!is_null($this->_owner)) {
                $filter['owner'] = $this->_owner;
            }

            $this->_view->numGalleries = $ansel_storage->countGalleries(
                $registry->getAuth(),
                array('attributes' => $filter,
                      'all_levels' => false,
                      'tags' => !empty($params['tags']) ? $params['tags'] : null));

            if ($this->_view->numGalleries == 0 && empty($this->_params['api'])) {
                if ($this->_owner && $this->_owner == $registry->getAuth()) {

                    $notification->push(_("You have no photo galleries, add one!"), 'horde.message');
                    Horde::url('gallery.php')->add('actionID', 'add')->redirect();
                    exit;
                }
                $notification->push(_("There are no photo galleries available."), 'horde.message');
                $this->_view->galleryList = array();
            } else {
                $this->_view->galleryList = $ansel_storage->listGalleries(
                    array('attributes' => $filter,
                          'all_levels' => false,
                          'from' => $this->_page * $this->_view->gPerPage,
                          'count' => $this->_view->gPerPage,
                          'sort_by' => $this->_view->sortBy,
                          'direction' => $this->_view->sortDir,
                          'tags' => !empty($params['tags']) ? $params['tags'] : null));
            }
        }
    }

    /**
     * Get this view's title.
     *
     * @return string  The gallery's title.
     */
    public function getTitle()
    {
        if ($this->_owner) {
            if ($this->_owner == $GLOBALS['registry']->getAuth() &&
                empty($this->_params['api'])) {

                return  _("My Galleries");
            } elseif (!empty($GLOBALS['conf']['gallery']['customlabel'])) {
                $uprefs = $GLOBALS['injector']
                  ->getInstance('Horde_Core_Factory_Prefs')
                  ->create('ansel', array(
                      'cache' => false,
                      'owner' => $this->_owner));
                $fullname = $uprefs->getValue('grouptitle');
                if (!$fullname) {
                    $identity = $GLOBALS['injector']
                        ->getInstance('Horde_Core_Factory_Identity')
                        ->create($this->_owner);
                    $fullname = $identity->getValue('fullname');
                    if (!$fullname) {
                        $fullname = $this->_owner;
                    }
                    return sprintf(_("%s's Galleries"), $fullname);
                } else {
                    return $fullname;
                }
            } else {
                return sprintf(_("%s's Galleries"), $this->_owner);
            }
        } else {
            return _("Gallery List");
        }
    }

    /**
     * Return the HTML representing this view.
     *
     * @return string  The HTML.
     *
     */
    public function html()
    {
        global $conf, $prefs, $registry;

        $vars = Horde_Variables::getDefaultVariables();
        if (!empty($this->_params['page'])) {
            $vars->add('page', $this->_params['page']);
        }

        if (!empty($this->_params['pager_url'])) {
            $this->_pagerurl = $this->_params['pager_url'];
            $override = true;
        } else {
            $override = false;
            $this->_pagerurl = Ansel::getUrlFor(
              'view',
               array(
                 'owner' => $this->_owner,
                 'special' => $this->_special,
                 'groupby' => $this->_view->groupby,
                 'view' => 'List'));
        }
        $p_params = array('num' => $this->_view->numGalleries,
                          'url' => $this->_pagerurl,
                          'perpage' => $this->_view->gPerPage);

        if ($override) {
            $p_params['url_callback'] = null;
        }
        $this->_pager = new Horde_Core_Ui_Pager('page', $vars, $p_params);
        $preserve = array('sort_dir' => $this->_view->sortDir);
        if (!empty($this->_view->sortBy)) {
            $preserve['sort'] = $this->_view->sortBy;
        }
        $this->_pager->preserve($preserve);

        if ($this->_view->numGalleries) {
            $min = $this->_page * $this->_view->gPerPage;
            $max = $min + $this->_view->gPerPage;
            if ($max > $this->_view->numGalleries) {
                $max = $this->_view->numGalleries - $min;
            }
            $this->_view->start = $min + 1;
            $this->_view->end = min($this->_view->numGalleries, $min + $this->_view->gPerPage);

            if ($this->_owner) {
                $this->_view->refresh_link = Ansel::getUrlFor(
                  'view',
                   array(
                     'groupby' => $this->_view->groupby,
                     'owner' => $this->_owner,
                     'page' => $this->_page,
                     'view' => 'List'));
            } else {
                $this->_view->refresh_link = Ansel::getUrlFor(
                  'view',
                  array(
                    'view' => 'List',
                    'groupby' => $this->_view->groupby,
                    'page' => $this->_page));
            }

            // Get top-level / default gallery style.
            if (empty($this->_params['style'])) {
                $style = Ansel::getStyleDefinition($prefs->getValue('default_gallerystyle'));
            } else {
                $style = Ansel::getStyleDefinition($this->_params['style']);
            }

            // Final touches.
            if (empty($this->_params['api'])) {
                $this->_view->breadcrumbs = Ansel::getBreadcrumbs();
                $this->_view->groupbyUrl = strval(Ansel::getUrlFor('group', array('actionID' => 'groupby', 'groupby' => 'owner')));
            }
            $this->_view->pager = $this->_pager->render();
            $this->_view->style = $this->style;
            $this->_view->tilesperrow = $prefs->getValue('tilesperrow');
            $this->_view->cellwidth = round(100 / $this->_view->tilesperrow);
            $this->_view->params = $this->_params;

            $GLOBALS['page_output']->addScriptFile('views/common.js');
            return $this->_view->render('list');
        }

        return '&nbsp;';
    }

    public function viewType()
    {
        return 'List';
    }

    /**
     * noop
     *
     * @see ansel/lib/View/Ansel_View_Base#getGalleryCrumbData()
     */
    public function getGalleryCrumbData()
    {
        throw new Horde_Exception('Ansel_View_List::getGalleryCrumbData not implemented.');
    }

}
