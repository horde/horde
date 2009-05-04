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

    public function __construct($buttons)
    {
        $this->koward = &Koward::singleton();

        parent::Horde_Form(Variables::getDefaultVariables());

        $this->setTitle(_("Object actions"));

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
