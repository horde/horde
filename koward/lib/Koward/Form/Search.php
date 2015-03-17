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

        parent::__construct($vars);

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

                $v = $this->addVariable($attribute['label'], 'object[' . $key . ']', $attribute['type'], $attribute['required'], null, $desc, $params);
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
                if ($value != '' && $value !== null) {
                    $search_criteria[] = array('field' => $key,
                                               'op'    => 'contains',
                                               'test' => $value);
                }
            }
            if (empty($search_criteria)) {
                throw new Koward_Exception('Provide at least a single search parameter!');
            }
            $search_criteria = array('AND' => $search_criteria);
            $criteria = array('AND' => array($search_criteria,
                                             $this->koward->search['criteria']));
            $params = array('scope' => 'sub',
                            'attributes' => array_merge(array('dn'), $attributes));
            if (!empty($this->koward->conf['koward']['search']['sizelimit'])) {
                $params['sizelimit'] = $this->koward->conf['koward']['search']['sizelimit'];
            }
            $server = &$this->koward->getServer();
            $result = $server->find($criteria, $params);
            if (!empty($server->lastSearch)
                && method_exists($server->lastSearch, 'sizeLimitExceeded')
                && $server->lastSearch->sizeLimitExceeded()) {
                $this->koward->notification->push(sprintf(_("More than the maximal allowed amount of %s elements were found. The list of results has been shortened."), $this->koward->conf['koward']['search']['sizelimit'], 'horde.message'));
            }
            return $result;
        }
    }
}
