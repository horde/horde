<?php
/**
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @package Horde_Rdo
 */

/**
 * The Horde_Form_Helper:: class provides autogeneration extension to
 * Horde_Form used for generating create and update data with various
 * backends.
 *
 * @author  Duck <duck@obala.net>
 * @since   Horde 4.0
 * @package Horde_Rdo
 */
class Horde_Rdo_Form_Helper extends Horde_Form
{
    /**
     * The primary key(s) of the resource.
     *
     * @var string
     */
    protected $primaryKey;

    /**
     * Rdo_Mapper object that we display.
     *
     * @var Horde_Rdo_Mapper $mapper
     */
    protected $mapper;

    /**
     * Extends Horde_Form for general purposes and prepare initial form.
     */
    public function __construct($vars, $title = '', $name = null, $params = null)
    {
        parent::__construct($vars, $title, $name);

        if (is_array($params)) {
            $this->mapper = $params['mapper'];
            unset($params['mapper']);
        } else {
            $this->mapper = $params;
            $params = array();
        }

        if ($vars->exists('action')) {
            $this->addHidden('', 'action', 'text', true);
        }
        if ($vars->exists('table')) {
            $this->addHidden('', 'table', 'text', true);
        }
        if ($vars->exists('what2process')) {
            $this->addHidden('', 'what2process', 'text', true);
        }

        /* Determinate action */
        if ($vars->get('submitbutton') == _("Advanced search")) {
            $vars->set('action', 'search_active');
            $this->_submitted = false;
        } elseif ($vars->get('submitbutton') == _("Basic search")) {
            $vars->set('action', 'search');
            $this->_submitted = false;
        } elseif (isset($params['action'])) {
            $vars->set('action', $params['action']);
        }

        $i = 0;
        $this->getPrimaryKey();
        $allFields = $this->getFields($vars->get('action'));

        /* Determine what to display */
        if ($vars->get('action') == 'search_active') {
            if ($vars->get('fields')) {
                $fields = explode('|', $vars->get('fields'));
            } else {
                $fields = $this->primaryKey;
            }
            if ($vars->get('horde_helper_add')) {
                $fields[] = $vars->get('horde_helper_add');
                $vars->set('horde_helper_add', null);
            }
        } else {
            $fields = $allFields;
        }

        /* Loop to add fields */
        foreach ($fields as $key) {
            if ($key == 'created' || $key == 'updated') {
                continue;
            }

            $params = $this->formMeta($vars->get('action'), $key);
            if (is_object($params)) {
                return $params;
            }

            $this->_currentSection = $params['section'];

            switch ($vars->get('action')) {
            case 'create':
                if ($params['readonly']) {
                    continue 2;
                }
                break;

            case 'update':
                if (in_array($key, $this->primaryKey)) {
                    $this->addHidden('', $key, $params['type'], true);
                    $params['readonly'] = true;
                }
                break;

            case 'search':
            case 'search_active':
                $key .= '_' . $i++;
                $params['readonly'] = false;
                $params['required'] = false;
                if (!$params['hidden']) {
                    $this->addCase($params['humanName'], $key);
                }
                break;
            }

            if ($params['hidden']) {
                $this->addHidden('', $key, $params['type'], $params['required']);
                continue;
            }

            $v = $this->addVariable($params['humanName'], $key, $params['type'],
                                    $params['required'], $params['readonly'],
                                    $params['description'], $params['params']);

            if (!empty($params['help'])) {
                $v->setHelp($params['help']);
            }
            if (!empty($params['default'])) {
                $v->setDefault($params['default']);
            }
        }

        switch ($vars->get('action')) {
        case 'search':
            $this->_submit = array(_("Search"), _("Advanced search"));
            break;

        case 'search_active':
            require_once 'Horde/Array.php';
            $params = array(Horde_Array::valuesToKeys($allFields), true);
            $this->addVariable(_("Add"), 'horde_helper_add', 'enum', false, false, null, $params);
            $this->_submit = array(_("Search"), _("Add"), _("Basic search"));
            $this->addHidden('', 'fields', 'text', true);
            $vars->set('fields', implode('|', $fields));
            break;

        case 'create':
            $this->_submit = _("Create");
            break;

        case 'update':
            $this->_submit = _("Update");
            $this->_reset = _("Reset");
        }
    }

    /**
     * Add a comparison operator selection
     */
    protected function addCase($humanName, $key)
    {
        $this->addVariable($humanName, 'cases_' . $key, 'enum', false, false, 'cases',
                           array(array('=' => '=', '>' => '>', '<' => '<',
                                       '>=' => '>=', '<=' => '<=', '<>' => '<>',
                                       'LIKE' => 'LIKE', 'NOT LIKE' => 'NOT LIKE',
                                       'IS' => 'IS', 'IS NOT' => 'IS NOT')));
    }

    /**
     * Map from columns to Horde_Form types and attributes:
     * Create values like Horde_Form::addVariable parameters
     *   - humanName
     *   - type
     *   - required
     *   - readonly
     *   - description
     *   - params
     */
    protected function formMeta($action, $column, $key = false)
    {
        static $map;

        if ($map === null) {
            $map = $this->formMetaData($action);
            if ($map instanceof PEAR_Error) {
                return $map;
            }
            if (isset($map['__sections']) && $this->_vars->action != 'search_active') {
                foreach ($map['__sections'] as $section => $value) {
                    $this->setSection($section, $value['desc'], $value['image'], $value['expanded']);
                }
                unset($map['__sections']);
            }

            foreach ($this->getFields($action) as $id) {
                if (!isset($map[$id])) {
                    $map[$id] = array();
                }
                if (!isset($map[$id]['hidden'])) {
                    $map[$id]['hidden'] = false;
                }
                if (!isset($map[$id]['humanName'])) {
                    $map[$id]['humanName'] = $id;
                }
                if (!isset($map[$id]['section'])) {
                    $map[$id]['section'] = '__base';
                }
                if (!isset($map[$id]['readonly'])) {
                    $map[$id]['readonly'] = false;
                }
                if (!isset($map[$id]['params'])) {
                    $map[$id]['params'] = array();
                }
                if (!isset($map[$id]['description'])) {
                    $map[$id]['description'] = '';
                }
                if (!isset($map[$id]['required'])) {
                    $map[$id]['required'] = in_array($id, $this->primaryKey);
                }
                if ($action == 'update' && in_array($id, $this->primaryKey)) {
                    $map[$id]['readonly'] = true;
                }

                if (!isset($map[$id]['type'])) {
                    $map[$id]['type'] = 'text';
                } else {

                    /* trim aditonal parameters like decimal(10,2)*/
                    if (strpos($map[$id]['type'], '(')) {
                        $map[$id]['type'] = substr($map[$id]['type'], 0, strpos($map[$id]['type'], '('));
                    }

                    switch ($map[$id]['type']) {
                    case 'date':
                        $map[$id]['type'] = 'monthdayyear';
                        $map[$id]['params'] = array('', '', true, '%Y-%m-%d');
                        break;

                    case 'number':
                    case 'decimal':
                    case 'real':
                        $map[$id]['type'] = 'number';
                        break;

                    case 'text':
                        $map[$id]['type'] = 'longtext';
                        break;

                    default:
                        if (!class_exists('Horde_Form_Type_' . $map[$id]['type'], false)) {
                            $map[$id]['type'] = 'text';
                        }
                        break;
                    }
                }
            }
        }


        if ($key) {
            return $map[$column][$key];
        } else {
            return $map[$column];
        }
    }

    /**
     * Return the form values of primary keys.
     */
    public function getSelected()
    {
        static $params;

        if (is_null($params)) {
            foreach ($this->primaryKey as $key) {
                if ($this->_vars->$key) {
                    $params[$key] = $this->_vars->$key;
                }
            }
        }

        return $params;
    }

    function getRenderer($params = array())
    {
        if ($this->_vars->action == 'search' || $this->_vars->action == 'search_active') {
            $renderer = new Horde_Form_Renderer_Form_Helper($params);
        } else {
            $renderer = new Horde_Form_Renderer($params);
        }

        return $renderer;
    }

    /**
     * Fetch the field values of the submitted form.
     *
     * @param Variables $vars  The Variables object.
     * @param array $info      Array to be filled with the submitted field
     *                         values.
     */
    function getInfo($vars, &$info)
    {
        if (!$this->isSubmitted()) {
            $info = array();
            return;
        }

        parent::getInfo($vars, $info);

        /* Add test cases and filter not existing filters */
        if ($this->_vars->action == 'search' || $this->_vars->action == 'search_active') {
            $fields = $this->getFields('search');
            foreach ($info as $key => $value) {
                $name = substr($key, 0, strrpos($key, '_'));
                if (empty($value) || !in_array($name, $fields)) {
                    if (substr($key, 0, 6) != 'cases_') {
                        unset($info[$key],
                              $info['cases_' . $key]);
                    }
                    continue;
                }
                switch ($info['cases_' . $key]) {
                    case 'IS':
                    case 'IS NOT':
                        if (!defined($value)) {
                            unset($info[$key],
                                  $info['cases_' . $key]);
                            continue 2;
                        }
                        $value = constant($value);
                    break;

                    case 'LIKE':
                    case 'NOT LIKE':
                        if (strpos($value, '%') === false) {
                            $value = "%$value%";
                        }
                    break;
                }

                $info[$key] = array('field' => $name,
                                    'test' => $info['cases_' . $key],
                                    'value' => $value);
                unset($info['cases_' . $key]);
            }
        }
    }

    /**
     * Return the selected object or false if none.
     */
    public function getSelected()
    {
        return $this->mapper->find(Horde_Rdo::FIND_FIRST,
                                   parent::getSelected());
    }

    /**
     * Return the form meta data
     */
    public function formMetaData($action)
    {
        if (method_exists($this->mapper, 'formMeta')) {
            return $this->mapper->formMeta($action);
        } else {
            return $this->mapper->model->getFields();
        }
    }

    /**
     * Array of field name.
     */
    protected function getFields($action)
    {
        static $fields;

        if ($fields !== null) {
            return $fields;
        }

        if (method_exists($this->mapper, 'formFields')) {
            $fields = $this->mapper->formFields($action);
        } else {
            $fields = $this->mapper->model->listFields();
        }

        return $fields;
    }

    /**
     * Get primary key.
     */
    protected function getPrimaryKey()
    {
        if ($this->primaryKey !== null) {
            return $this->primaryKey;
        }

        if (method_exists($this->mapper, 'getPrimaryKey')) {
            $this->primaryKey = $this->mapper->getPrimaryKey();
        } else {
            $this->primaryKey = array($this->mapper->model->key);
        }
    }

    /**
     * Delect selected record.
     */
    public function delete()
    {
        try {
            $this->getSelected()->delete();
        } catch (Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }
    }

    /**
     * Create a new record.
     */
    public function create($params)
    {
        foreach ($params as $key => $value) {
            $meta = $this->formMeta('update', $key);
            if ($meta['type'] == 'set') {
                $params[$key] = implode('|', $value);
            }
        }

        try {
            $this->mapper->create($params);
        } catch (Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }
    }

    /**
     * Update selected record.
     */
    public function update($params)
    {
        foreach ($params as $key => $value) {
            $meta = $this->formMeta('update', $key);
            if ($meta['type'] == 'set' && is_array($value)) {
                $params[$key] = implode('|', $value);
            }
        }

        $selected = $this->getSelected();
        foreach ($params as $key => $value) {
            $selected->$key = $value;
        }

        try {
            $selected->save();
        } catch (Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }
    }

}
