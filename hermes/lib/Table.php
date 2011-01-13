<?php
/**
 * The Horde_UI_Table:: class displays and allows manipulation of tabular
 * data.
 *
 * Copyright 2001 Robert E. Coyle <robertecoyle@hotmail.com>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */
class Hermes_Table extends Horde_Core_Ui_Widget
{
    /**
     * Data loaded from the getTableMetaData API.
     *
     * @access private
     * @var array
     */
    private $_metaData = null;

    /**
     * @var array
     */
    protected $_formVars = array();

    /**
     *
     * @return array
     * @throws Hermes_Exception
     */
    public function getMetaData()
    {
        if (is_null($this->_metaData)) {
            list($app, $name) = explode('/', $this->_config['name']);
            $args = array($name, $this->_config['params']);
            $this->_metaData = $GLOBALS['registry']->callByPackage(
                $app, 'getTableMetaData', $args);


            // We need to make vars for the columns.
            foreach ($this->_metaData['sections'] as $secname => $section) {
                foreach ($section['columns'] as $col) {
                    $title = isset($col['title']) ? $col['title'] : '';
                    $typename = isset($col['type']) ? $col['type'] : 'text';
                    $params = isset($col['params']) ? $col['params'] : array();

                    // Column types which begin with % are pseudo-types handled
                    // directly.
                    if (substr($typename, 0, 1) != '%') {
                        // This type needs to be assigned by reference!
                        $type = &Horde_Form::getType($typename, $params);
                        $var = new Horde_Form_Variable($title, $col['name'],
                                                       $type, false, true, '');
                        $this->_formVars[$secname][$col['name']] = $var;
                    }
                }
            }
        }

        return $this->_metaData;
    }

    protected function _getData($range = null)
    {
        if (is_null($range)) {
            $range = array();
            foreach (array_keys($this->_metaData['sections']) as $secname) {
                $range[$secname] = array(
                    0,
                    $this->_metaData['sections'][$secname]['rows']);
            }
        }

        list($app, $name) = explode('/', $this->_config['name']);
        $args = array($name, $this->_config['params'], $range);
        return $GLOBALS['registry']->callByPackage($app, 'getTableData', $args);
    }

    /**
     * Count the number of columns in this table.
     *
     * Returns the largest column count of any section, taking into account
     * 'colspan' attributes.
     *
     * @return integer number of columns
     * @throws Hermes_Exception
     */
    public function getColumnCount()
    {
        $res = $this->getMetaData();
        $colcount = 0;
        foreach ($this->_metaData['sections'] as $section) {
            $sec_colcount = 0;
            foreach ($section['columns'] as $col) {
                if (isset($col['colspan'])) {
                    $sec_colcount += $col['colspan'];
                } else {
                    $sec_colcount++;
                }
            }
            if ($sec_colcount > $colcount) {
                $colcount = $sec_colcount;
            }
        }

        return $colcount;
    }

    /**
     * Render the table.
     */
    public function render($data = null)
    {
        global $notification;

        try {
            $result = $this->getMetaData();
        } catch (Hermes_Exception $e) {
            $notification->push($e->getMessage(), 'horde.error');
            return false;
        }

        $varRenderer = new Horde_Core_Ui_VarRenderer_Html();

        $html = '<h1 class="header">';

        // Table title.
        if (isset($this->_config['title'])) {
            $html .= $this->_config['title'];
        } else {
            $html .= _("Table");
        }

        // Hook for icons and things
        if (isset($this->_config['title_extra'])) {
            $html .= $this->_config['title_extra'];
        }

        $html .= '</h1>';

        /*
        //
        // Export icon.  We store the parameters in the session so that smart
        // users can't hack it (in Hermes, you could make it show other
        // people's time, for example).
        //
        $id = $this->_config['name'] . ':' . $this->_name;
        $_SESSION['horde']['tables'][$id] = $this->_config;
        $exportlink = Horde::url($GLOBALS['registry']->get('webroot', 'horde') .
                                 '/services/table/export.php');
        $exportlink = Horde_Util::addParameter($exportlink, array('id' => $id));

        $html .= ' &nbsp;' . Horde::link($exportlink, _("Export Data")) .
                 Horde::img('data.png', _("Export Data"), 'hspace="2"') .
                 '</a>';
        */

        // Column titles.
        $html .= '<table class="time striped" id="hermes_time" cellspacing="0"><thead><tr class="item">';
        foreach ($this->_metaData['sections']['data']['columns'] as $col) {
            $html .= '<th' . (isset($col['colspan']) ?
                              (' colspan="' . $col['colspan'] . '"') :
                              '') . '>' . $col['title'] . '</th>';
        }
        $html .= '</tr></thead>';

        // Display data.
        try {
            $data = $this->_getData();
        } catch (Hermes_Exception $e) {
            $notification->push($e, 'horde.error');
            $data = array();
        }

        foreach ($this->_metaData['sections'] as $secname => $section) {
            if (empty($data[$secname])) {
                continue;
            }

            /* Open the table section, either a tbody or the tfoot. */
            $html .= ($secname == 'footer') ? '<tfoot>' : '<tbody>';

            /* This Horde_Variables object is populated for each table row
             * so that we can use the Horde_Core_Ui_VarRenderer. */
            $vars = new Horde_Variables();
            $form = null;
            foreach ($data[$secname] as $row) {
                $html .= '<tr>';
                foreach ($row as $key => $value) {
                    $vars->set($key, $value);
                }
                foreach ($section['columns'] as $col) {
                    $value = null;
                    if (isset($row[$col['name']])) {
                        $value = $row[$col['name']];
                    }
                    $align = '';
                    if (isset($col['align'])) {
                        $align = ' align="' . htmlspecialchars($col['align']) . '"';
                    }
                    $colspan = '';
                    if (isset($col['colspan'])) {
                        $colspan = ' colspan="' .
                                   htmlspecialchars($col['colspan']) . '"';
                    }
                    $html .= "<td$align$colspan";
                    if (!empty($col['nobr'])) {
                        $html .= ' class="nowrap"';
                    }
                    $html .= '>';
                    // XXX: Should probably be done at the <tr> with a class.
                    if (!empty($row['strong'])) {
                        $html .= '<strong>';
                    }
                    if (isset($col['type']) && substr($col['type'], 0, 1) == '%') {
                        switch ($col['type']) {
                        case '%html':
                            if (!empty($row[$col['name']])) {
                                $html .= $row[$col['name']];
                            }
                            break;
                        }
                    } else {
                        $html .= $varRenderer->render($form, $this->_formVars[$secname][$col['name']], $vars);
                    }
                    if (!empty($row['strong'])) {
                        $html .= '</strong>';
                    }
                    $html .= '</td>';
                }
                $html .= '</tr>';
            }

            /* Close the table section. */
            $html .= ($secname == 'footer') ? '</tfoot>' : '</tbody>';
        }

        Horde::addScriptFile('stripe.js', 'horde', true);
        return $html . '</table>';
    }

}
