<?php
/**
 * Base class for smartmobile view pages.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */
class Turba_Smartmobile
{
    /**
     * @var Horde_Variables
     */
    public $vars;

    /**
     * @var Horde_View
     */
    public $view;

    /**
     */
    public function __construct(Horde_Variables $vars)
    {
        global $notification, $page_output;

        $this->vars = $vars;

        $this->view = new Horde_View(array(
            'templatePath' => TURBA_TEMPLATES . '/smartmobile'
        ));
        $this->view->addHelper('Horde_Core_Smartmobile_View_Helper');
        $this->view->addHelper('Text');

        $this->_initPages();

        $page_output->addScriptFile('smartmobile.js');

        $notification->notify(array('listeners' => 'status'));
    }

    /**
     */
    public function render()
    {
        echo $this->view->render('browse');
        echo $this->view->render('entry');
    }

    /**
     */
    protected function _initPages()
    {
        global $injector;

        $this->view->list = array();
        if ($GLOBALS['browse_source_count']) {
            foreach (Turba::getAddressBooks() as $key => $val) {
                if (!empty($val['browse'])) {
                    try {
                        $driver = $injector->getInstance('Turba_Factory_Driver')->create($key);
                    } catch (Turba_Exception $e) {
                        continue;
                    }

                    $contacts = $driver->search(array(), null, 'AND', array('__key', 'name'));
                    $contacts->reset();

                    $url = new Horde_Core_Smartmobile_Url();
                    $url->add('source', $key);
                    $url->setAnchor('entry');
                    $tmp = array();

                    while ($contact = $contacts->next()) {
                        $name = Turba::formatName($contact);
                        $tmp[] = array(
                            'name' => strlen($name) ? $name : ('[' . _("No Name") . ']'),
                            'url' => strval($url->add('key', $contact->getValue('__key')))
                        );
                    }

                    $this->view->list[$val['title']] = $tmp;
                }
            }
        }
    }

}
