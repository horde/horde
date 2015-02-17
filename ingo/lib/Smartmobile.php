<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * Base class for smartmobile view pages.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Smartmobile
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
            'templatePath' => INGO_TEMPLATES . '/smartmobile'
        ));
        $this->view->addHelper('Horde_Core_Smartmobile_View_Helper');
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
        echo $this->view->render('rules');
        echo $this->view->render('rule');
    }

    /**
     */
    protected function _initPages()
    {
        global $injector, $session;

        $this->view->list = array();

        $filters = Ingo_Storage_FilterIterator_Match::create(
            $injector->getInstance('Ingo_Factory_Storage')->create(),
            $session->get('ingo', 'script_categories')
        );

        foreach ($filters as $val) {
            // Skip disabled rules.
            if ($val->disable) {
                continue;
            }

            switch (get_class($val)) {
            case 'Ingo_Rule_System_Blacklist':
                $img = 'blacklist.png';
                break;

            case 'Ingo_Rule_System_Whitelist':
                $img = 'whitelist.png';
                break;

            case 'Ingo_Rule_System_Vacation':
                $img = 'vacation.png';
                break;

            case 'Ingo_Rule_System_Forward':
                $img = 'forward.png';
                break;

            case 'Ingo_Rule_System_Spam':
                $img = 'spam.png';
                break;

            default:
                $img = null;
                break;
            }

            $url = new Horde_Core_Smartmobile_Url();
            $url->add('uid', $val->uid);
            $url->setAnchor('rule');

            $this->view->list[] = array(
                'img' => is_null($img) ? null : Horde_Themes_Image::tag($img, array('attr' => array('class' => 'ui-li-icon'))),
                'name' => $val->name,
                'url' => $url
            );
        }
    }

    /**
     * Add base javascript variables to the page.
     */
    protected function _addBaseVars()
    {
        global $page_output;

        $code = array(
            'text' => array(
                'no_descrip' => _("No Description")
            )
        );

        $page_output->addInlineJsVars(array(
            'var Ingo' => $code
        ), array('top' => true));
    }

}
