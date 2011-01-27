<?php
/**
 * The Turba_View_List:: class provides an interface for objects that
 * visualize Turba_List objects.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jon Parise <jon@csh.rit.edu>
 * @package Turba
 */
class Turba_View_List implements Countable
{
    /**
     * The Turba_List object that we are visualizing.
     *
     * @var Turba_List
     */
    public $list;

    /**
     * Show/hide "mark" column in the display.
     *
     * @var boolean
     */
    public $showMark = false;

    /**
     * Show/hide "edit" column in the display.
     *
     * @var boolean
     */
    public $showEdit = false;

    /**
     * Show/hide "vcard" column in the display.
     *
     * @var boolean
     */
    public $showVcard = false;

    /**
     * Show/hide "group" column in the display.
     *
     * @var boolean
     */
    public $showGroup = false;

    /**
     * Show/hide "sort" column in the display.
     *
     * @var boolean
     */
    public $showSort = false;

    /**
     * Type of list.
     *
     * @var string
     */
    public $type;

    /**
     * The HTML renderer.
     *
     * @var Horde_Core_Ui_VarRenderer_Html
     */
    public $renderer;

    /**
     * A Horde_Variables object.
     *
     * @var Horde_Variables
     */
    public $vars;

    /**
     * A list of Horde_Form_Variable objects.
     *
     * @var array
     */
    public $variables = array();

    /**
     * A dummy form object.
     *
     * @var Horde_Form
     */
    public $form = null;

    /**
     * Which columns to render
     *
     * @var array
     */
    public $columns;

    /**
     * Constructs a new Turba_View_List object.
     *
     * @param Turba_List $list  List of contacts to display.
     * @param array $controls   Which icons to display
     * @param array $columns    The list of columns to display
     *
     * @return Turba_View_List
     */
    public function __construct($list, array $controls = null, array $columns = null)
    {
        if ($controls === null) {
            $controls = array('Mark' => true,
                              'Edit' => true,
                              'Vcard' => true,
                              'Group' => true,
                              'Sort' => true);
        }
        $this->columns = $columns;
        $this->list = $list;
        $this->setControls($controls);
        $this->renderer = Horde_Core_Ui_VarRenderer::factory('Html');
        $this->vars = new Horde_Variables();
    }

    /**
     * Set which controls are shown by the display templates.
     *
     * @param array $controls
     */
    public function setControls(array $controls)
    {
        foreach ($controls as $control => $show) {
            $key = 'show' . $control;
            $this->$key = (bool)$show;
        }
    }

    /**
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @TODO: these should be injected when we refactor to Horde_View
     * @global $prefs
     * @global $session
     * @global $default_source
     * @global $copymove_source_options
     */
    public function display()
    {
        global $prefs, $session, $default_source, $copymove_source_options;

        $driver = $GLOBALS['injector']
            ->getInstance('Turba_Injector_Factory_Driver')
            ->create($default_source);
        $hasDelete = $driver->hasPermission(Horde_Perms::DELETE);
        $hasEdit = $driver->hasPermission(Horde_Perms::EDIT);
        $hasExport = ($GLOBALS['conf']['menu']['import_export'] && !empty($GLOBALS['cfgSources'][$default_source]['export']));

        list($addToList, $addToListSources) = $this->getAddSources();
        $viewurl = Horde::url('browse.php')->add(array(
            'key' => Horde_Util::getFormData('key'),
            'url' => Horde::selfUrl(true, false, true)
        ));
        if ($this->type == 'search') {
            $page = Horde_Util::getFormData('page', 0);
            $numitem = count($this);
            $maxpage = $prefs->getValue('maxpage');
            $perpage = $prefs->getValue('perpage');
            $min = $page * $perpage;
            while ($min > $numitem) {
                $page--;
                $min = $page * $perpage;
            }
            $max = $min + $perpage;
            $start = ($page * $perpage) + 1;
            $end = min($numitem, $start + $perpage - 1);
            $listHtml = $this->getPage($numDisplayed, $min, $max);
            $crit = array();
            if ($session->get('turba', 'search_mode') == 'advanced') {
                $map = $driver->getCriteria();
                foreach ($map as $key => $value) {
                    if ($key != '__key') {
                        $val = Horde_Util::getFormData($key);
                        if (!empty($val)) {
                            $crit[$key] = $val;
                        }
                    }
                }
            }
            $params = array_merge($crit, array(
                'criteria' => Horde_Util::getFormData('criteria'),
                'val' => Horde_Util::getFormData('val'),
                'source' => Horde_Util::getFormData('source', $default_source)
            ));
            $viewurl = Horde::url('search.php')->add($params);
            $vars = Horde_Variables::getDefaultVariables();
            $pager = new Horde_Core_Ui_Pager('page', $vars,
                                        array('num' => $numitem,
                                              'url' => $viewurl,
                                              'page_limit' => $maxpage,
                                              'perpage' => $perpage));
            $pagerHeader = 'numPager.inc';
        } else {
            $page = Horde_Util::getFormData('page', '*');
            if (!preg_match('/^[A-Za-z*]$/', $page)) {
                $page = '*';
            }
            if (count($this) > $prefs->getValue('perpage')) {
                $page = Horde_Util::getFormData('page', 'A');
                if (!preg_match('/^[A-Za-z*]$/', $page)) {
                    $page = 'A';
                }
            }
            $listHtml = $this->getAlpha($numDisplayed, $page);
            $pagerHeader = 'alphaPager.inc';
        }

        if ($numDisplayed) {
            require TURBA_TEMPLATES . '/browse/actions.inc';
            require TURBA_TEMPLATES . '/list/' . $pagerHeader;
            echo $listHtml;
        } else {
            require TURBA_TEMPLATES . '/list/' . $pagerHeader;
            echo '<p><em>' . _("No matching contacts") . '</em></p>';
        }
    }

    /**
     * Renders the list contents into an HTML view.
     *
     * @param integer $numDisplayed  Ouptut parameter - the number of rows
     *                               rendered.
     * @param integer $min           Minimum number of rows to display.
     * @param integer $max           Maximum number of rows to display.
     *
     * @return string  HTML to echo.
     */
    public function getPage(&$numDisplayed, $min = 0, $max = null)
    {
        if (is_null($max)) {
            $max = count($this);
        }
        return $this->_get($numDisplayed,
                           new Turba_View_List_PageFilter($min, $max));
    }

    /**
     * Renders the list contents that match $alpha into an HTML view.
     *
     * @param integer $numDisplayed  This will be set to the number of contacts
     *                               in the view.
     * @param string $alpha The letter to display.
     *
     * @return string HTML of the list.
     */
    public function getAlpha(&$numDisplayed, $alpha)
    {
        return $this->_get($numDisplayed,
                           new Turba_View_List_AlphaFilter($alpha));
    }

    /**
     * Retrieves a column's name
     *
     * @param integer $i  The zero-basd index of the column
     *
     * @return string
     */
    public function getColumnName($i)
    {
        return Turba::getColumnName($i, $this->columns);
    }

    /**
     * @param integer $i  The zero-based index of the column
     */
    public function getSortInfoForColumn($i)
    {
        $sortorder = Turba::getPreferredSortOrder();
        $column_name = $this->getColumnName($i);
        $i = 0;
        foreach ($sortorder as $sortfield) {
            if ($column_name == $sortfield['field']) {
                return array_merge($sortfield, array('rank' => $i));
            }
            $i++;
        }
        return null;
    }

    /**
     *
     * @param integer $i
     * @param string $title
     *
     * @return string
     */
    public function getColumnSortImage($i, $title = null)
    {
        if (is_null($title)) {
            $title = _("Sort Direction");
        }
        $sortdir = $this->getColumnSortDirection($i);
        if ($this->isPrimarySortColumn($i)) {
            return Horde::img($sortdir ? 'za.png' : 'az.png', $title);
        } else {
            return Horde::img($sortdir ? 'za_secondary.png' : 'az_secondary.png', _("Sort Direction"));
        }
    }

    /**
     * Retrieves a natural language description of the sort order
     *
     * @return string
     */
    public function getSortOrderDescription()
    {
        $description = array();
        $sortorder = Turba::getPreferredSortOrder();
        foreach ($sortorder as $elt) {
            $field = $elt['field'];
            if ($field == 'lastname') {
                $field = 'name';
            }
            $description[] = $GLOBALS['attributes'][$field]['label'];
        }

        return join(', ', $description);
    }

    /**
     * @param integer $i  The zero-based index of the column
     */
    public function getColumnSortDirection($i)
    {
        $result = $this->getSortInfoForColumn($i);
        if (is_null($result)) {
            return null;
        }

        return $result['ascending'] ? 0 : 1;
    }

    /**
     * Determines whether we are sorting on the specified column
     *
     * @param integer $i  The zero-based column index
     *
     * @return boolean
     */
    public function isSortColumn($i)
    {
        return !is_null($this->getSortInfoForColumn($i));
    }

    /**
     * Determines whether this is the first column to sort by
     *
     * @param integer $i  The zero-based column index
     *
     * @return boolean
     */
    public function isPrimarySortColumn($i)
    {
        $result = $this->getSortInfoForColumn($i);
        if (is_null($result)) {
            return false;
        }

        return ($result['rank'] == 0);
    }

    /**
     *
     * @param integer $numDisplayed
     * @param object $filter         A Turba_View_List filter object
     *
     * @return string
     */
    protected function _get(&$numDisplayed, $filter)
    {
        ob_start();
        $width = floor(90 / (count($this->columns) + 1));
        @list($own_source, $own_id) = explode(';', $GLOBALS['prefs']->getValue('own_contact'));

        include TURBA_TEMPLATES . '/browse/column_headers.inc';

        $numDisplayed = 0;
        $this->list->reset();
        while ($ob = $this->list->next()) {
            if ($filter->skip($ob)) {
                continue;
            }

            include TURBA_TEMPLATES . '/browse/row.inc';
            $numDisplayed++;
        }

        include TURBA_TEMPLATES . '/browse/column_footers.inc';
        return ob_get_clean();
    }

    public function getAddSources()
    {
        global $addSources;

        // Create list of lists for Add to.
        $addToList = array();
        $addToListSources = array();
        foreach ($addSources as $src => $srcConfig) {
            if (!empty($srcConfig['map']['__type'])) {
                $addToListSources[] = array('key' => '',
                                            'name' => '&nbsp;&nbsp;' . htmlspecialchars($srcConfig['title']),
                                            'source' => htmlspecialchars($src));

                $srcDriver = $GLOBALS['injector']->getInstance('Turba_Injector_Factory_Driver')->create($src);
                try {
                    $listList = $srcDriver->search(
                        array('__type' => 'Group'),
                        array(
                            array(
                                'field' => 'name',
                                'ascending' => true
                            )
                        ),
                        'AND',
                        array('name')
                    );

                    $listList->reset();
                    $currentList = Horde_Util::getFormData('key');
                    while ($listObject = $listList->next()) {
                        if ($listObject->getValue('__key') != $currentList) {
                            $addToList[] = array(
                                'name' => htmlspecialchars($listObject->getValue('name')),
                                'source' => htmlspecialchars($src),
                                'key' => htmlspecialchars($listObject->getValue('__key'))
                            );
                        }
                    }
                } catch (Turba_Exception $e) {
                    $GLOBALS['notification']->push($e, 'horde.error');
                }
            }
        }
        if ($addToListSources) {
            if ($addToList) {
                array_unshift($addToList, '- - - - - - - - -');
            }
            $addToList = array_merge(array(_("Create a new Contact List in:")), $addToListSources, $addToList);
            $addToListSources = null;
        }

        return array($addToList, $addToListSources);
    }

    /* Countable methods. */

    /**
     * Returns the number of Turba_Objects that are in the list. Use this to
     * hide internal implementation details from client objects.
     *
     * @return integer  The number of objects in the list.
     */
    public function count()
    {
        return $this->list->count();
    }

}


