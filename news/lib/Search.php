<?php
/**
 * News search form
 *
 * $Id: Search.php 1175 2009-01-19 15:17:06Z duck $
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package News
 */
class News_Search extends Horde_Form {

    /**
     * Creator
     */
    public function __construct($vars)
    {
        parent::__construct($vars, _("Search"), 'news_search');

        $this->_submit = _("Search");

        $this->addVariable(_("Search world"), 'word', 'text', false, false, false);

        $s = array(News::UNCONFIRMED => _("Unconfirmed"),
                   News::CONFIRMED => _("Confirmed"),
                   News::LOCKED => _("Locked"));
        $this->addVariable(_("Status"), 'status', 'enum', false, false, false, array($s, _("-- select --")));

        $allowed_cats = $GLOBALS['news_cat']->getAllowed(Horde_Perms::DELETE);
        $this->addVariable(_("Category"), 'category', 'enum', false, false, false, array($allowed_cats, _("-- select --")));

        $sources = $GLOBALS['news']->getSources();
        if (!empty($sources)) {
            $this->addVariable(_("Source"), 'source', 'enum', false, false, false, array($sources, _("-- select --")));
        }

        $this->addVariable(_("Order by"), 'sort_by', 'enum', false, false, false, array(array('n.publish' => _("Publish date"),
                                                                                              'n.id' => _("Id"),
                                                                                              'l.title' => _("Title"),
                                                                                              'n.comments' => _("Comments"),
                                                                                              'n.reads' => _("Reads"),
                                                                                              'n.attachemt' => _("Attachments"))));

        $this->addVariable(_("Sort order"), 'sort_dir', 'enum', false, false, false, array(array('DESC' => _("Descending"),
                                                                                                 'ASC' => _("Ascending"))));

        $this->addVariable(_("Publish"), 'publish', 'datetime', false, false, false, News::datetimeParams());
        $this->addVariable(_("Unpublish"), 'unpublish', 'datetime', false, false, false, News::datetimeParams());
        $this->addVariable(_("User"), 'user', 'text', false, false, false);

        if ($GLOBALS['registry']->isAdmin()) {
            $this->addVariable(_("Editor"), 'editor', 'text', false, false, false);
        }
    }

    /**
     * Get pager
     */
    static public function getPager($info, $count, $url)
    {
        $pager = new Horde_Core_Ui_Pager('news_page',
                                    Horde_Variables::getDefaultVariables(),
                                    array('num' => $count,
                                          'url' => $url,
                                          'page_count' => 10,
                                          'perpage' => $GLOBALS['prefs']->getValue('per_page')));

        foreach ($info as $key => $value) {
            if (substr($key, 0, 1) == '_') {
                continue;
            } elseif ($key == 'word') {
                $pager->preserve($key, substr($value, 1, -1));
            } else {
                $pager->preserve($key, $value);
            }
        }

        return $pager;
    }
    /**
     * Fetch the field values of the submitted form.
     *
     * @param Horde_Variables $vars  A Horde_Variables instance, optional since Horde 3.2.
     * @param array $info      Array to be filled with the submitted field
     *                         values.
     */
    function getInfo($vars, &$info)
    {
        $this->_getInfoFromVariables($this->getVariables(), $this->_vars, $info);
    }

    /**
     * Fetch the field values from a given array of variables.
     *
     * @access private
     *
     * @param array  $variables  An array of Horde_Form_Variable objects to
     *                           fetch from.
     * @param object $vars       The Horde_Variables object.
     * @param array  $info       The array to be filled with the submitted
     *                           field values.
     */
    function _getInfoFromVariables($variables, &$vars, &$info)
    {
        foreach ($variables as $var) {
            $value = $var->getValue($vars);
            if (empty($value)) {
                continue;
            }

            if (Horde_Array::getArrayParts($var->getVarName(), $base, $keys)) {
                if (!isset($info[$base])) {
                    $info[$base] = array();
                }
                $pointer = &$info[$base];
                while (count($keys)) {
                    $key = array_shift($keys);
                    if (!isset($pointer[$key])) {
                        $pointer[$key] = array();
                    }
                    $pointer = &$pointer[$key];
                }
                $var->getInfo($vars, $pointer);
            } else {
                $var->getInfo($vars, $info[$var->getVarName()]);
            }

        }
    }

}
