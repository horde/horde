<?php
/**
 * @package Koward
 */

// @TODO Clean up
require_once dirname(__FILE__) . '/ApplicationController.php';

/**
 * @package Koward
 */
class CheckController extends Koward_ApplicationController
{

    protected function _initializeApplication()
    {
        parent::_initializeApplication();

        $this->suite = Koward_Test_AllTests::suite();
    }

    public function show()
    {
        $this->list = array();

        $this->list[0] = Horde::link(
            $this->urlFor(array('controller' => 'check',
                                'action' => 'run',
                                'id' => 'all')),
            _("All tests")) . _("All tests") . '</a>';

        $this->list[1] = '';

        for ($i = 0; $i < $this->suite->count(); $i++) {
            $class_name = $this->suite->testAt($i)->getName();
            $this->list[$i + 2] = Horde::link(
                $this->urlFor(array('controller' => 'check',
                                    'action' => 'run',
                                    'id' => $i + 1)),
                $class_name) . $class_name . '</a>';
        }
    }

    public function run()
    {
        
        if ($this->params['id'] == 'all') {
            $this->test = $this->suite;
        } else {
            $id = (int) $this->params['id'];
            if (!empty($id)) {
                $this->test = $this->suite->testAt($id - 1);
            } else {
                $this->test = null;
                $this->koward->notification->push(_("You selected no test!"));
            }
        }
    }
}