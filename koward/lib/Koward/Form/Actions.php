<?php
/**
 * @package Koward
 */

/**
 * @package Koward
 */
class Koward_Form_Actions extends Horde_Form {

    /**
     * The link to the application driver.
     *
     * @var Koward_Koward
     */
    protected $koward;

    public function __construct(&$object)
    {
        $this->koward = &Koward::singleton();

        $this->object = &$object;

        parent::Horde_Form(Variables::getDefaultVariables());

        $this->setTitle(_("Object actions"));

        $class_name = get_class($this->object);
        foreach ($this->koward->objects as $name => $config) {
            if ($config['class'] == $class_name) {
                $this->type = $name;
                if (!empty($config['preferred'])) {
                    break;
                }
            }
        }

        $buttons = array();
        foreach ($this->object->getActions() as $action) {
            if (isset($this->koward->objects[$this->type]['actions'][$action])) {
                $buttons[] = $this->koward->objects[$this->type]['actions'][$action];
            }
        }

        if (!empty($buttons)) {
            $this->setButtons($buttons);
        }
    }

    function &execute()
    {
        require_once 'Horde/Util.php';

        $submit = Util::getFormData('submitbutton');
        if (!empty($submit)) {
            foreach ($this->koward->objects[$this->type]['actions'] as $action => $label) {
                if ($submit == $label) {
                    $this->object->$action();
                }
            }
        }
    }
}
