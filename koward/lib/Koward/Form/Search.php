<?php
/**
 * @package Koward
 */

/**
 * @package Koward
 */
class Koward_Form_Search extends Horde_Form {

    /**
     * The link to the application driver.
     *
     * @var Koward_Koward
     */
    protected $koward;

    public function __construct(&$vars, &$object, $params = array())
    {
        $this->koward = &Koward::singleton();

        $this->object = &$object;

        parent::Horde_Form($vars);

        $type = false;

        $this->setButtons(_("Search"));
        $this->_addFields($this->koward->search);
    }


    /**
     * Sort fields for an object type
     */
    function _sortFields($a, $b)
    {
        if ($a['order'] == -1) {
            return 1;
        }
        if ($b['order'] == -1) {
            return -1;
        }
        if ($a['order'] == $b['order']) {
            return 0;
        }
        return ($a['order'] < $b['order']) ? -1 : 1;
    }

    /**
     * Set up the Horde_Form fields for the attributes of this object type.
     */
    function _addFields($config)
    {
        // Now run through and add the form variables.
        $tabs   = isset($config['tabs']) ? $config['tabs'] : array('' => $config['fields']);

        foreach ($tabs as $tab => $tab_fields) {
            if (!empty($tab)) {
                $this->setSection($tab, $tab);
            }
            foreach ($tab_fields as $key => $field) {
                if (!in_array($key, array_keys($config['fields']))) {
                    continue;
                }
                $attribute = $field;
                $params = isset($attribute['params']) ? $attribute['params'] : array();
                $desc = isset($attribute['desc']) ? $attribute['desc'] : null;

                $v = &$this->addVariable($attribute['label'], 'object[' . $key . ']', $attribute['type'], $attribute['required'], null, $desc, $params);
            }

            if (isset($attribute['default'])) {
                $v->setDefault($attribute['default']);
            }
        }
    }

    function &execute($attributes = array())
    {
        $this->getInfo($this->_vars, $info);
        if (isset($info['object'])) {
            $search_criteria = array();
            foreach ($info['object'] as $key => $value) {
                if (!empty($value)) {
                    $search_criteria[] = array('field' => $key,
                                               'op'    => 'contains',
                                               'test' => $value);
                }
            }
            $search_criteria = array('AND' => $search_criteria);
            $criteria = array('AND' => array($search_criteria,
                                             $this->koward->search['criteria']));
            $filter = $this->koward->getServer()->searchQuery($criteria);
            $params = array('scope' => 'sub',
                            'attributes' => array_merge(array('dn'), $attributes));
            return $this->koward->getServer()->search($filter, $params);
        }
    }
}
