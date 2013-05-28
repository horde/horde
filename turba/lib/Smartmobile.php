<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Turba
 */

/**
 * Base class for smartmobile view pages.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Turba
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
        $this->view->addHelper('Horde_Core_View_Helper_Image');
        $this->view->addHelper('Text');

        $this->_initPages();
        $this->_addBaseVars();

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

                    try {
                        $contacts = $driver->search(array(), null, 'AND', array('__key', 'name'));
                        $contacts->reset();
                    } catch (Turba_Exception $e) {
                        continue;
                    }
                    $url = new Horde_Core_Smartmobile_Url();
                    $url->add('source', $key);
                    $url->setAnchor('entry');
                    $tmp = array();

                    while ($contact = $contacts->next()) {
                        $name = Turba::formatName($contact);
                        $tmp[] = array(
                            'group' => $contact->isGroup(),
                            'name' => strlen($name) ? $name : ('[' . _("No Name") . ']'),
                            'url' => strval($url->add('key', $contact->getValue('__key')))
                        );
                    }

                    $this->view->list[$val['title']] = $tmp;
                }
            }
        }
    }

    /**
     * Add base javascript variables to the page.
     */
    protected function _addBaseVars()
    {
        global $page_output;

        $code = array(
            /* Gettext strings. */
            'text' => array(
                'browse' => _("Browse"),
                'group' => _("Group")
            )
        );

        $page_output->addInlineJsVars(array(
            'var Turba' => $code
        ), array('top' => true));
    }

}
