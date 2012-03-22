<?php
/**
 * Attach the image unblock javascript code into a page.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Ajax_Imple_ImageUnblock extends Horde_Core_Ajax_Imple
{
    /**
     * Unblock DOM ID counter.
     *
     * @var integer
     */
    static protected $_unblockId = 0;

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     *   - id: (string) [OPTIONAL] The DOM ID to attach to.
     *   - mailbox: (IMP_Mailbox) The mailbox of the message.
     *   - uid: (string) The UID of the message.
     */
    public function __construct($params)
    {
        if (!isset($params['id'])) {
            $params['id'] = 'imp_imageunblock' . self::$_unblockId;
        }

        ++self::$_unblockId;

        parent::__construct($params);
    }

    /**
     * Attach the object to a javascript event.
     */
    public function attach()
    {
        $js_params = array(
            'mailbox' => $this->_params['mailbox']->form_to,
            'uid' => $this->_params['uid']
        );

        if (defined('SID')) {
            parse_str(SID, $sid);
            $js_params = array_merge($js_params, $sid);
        }

        $page_output = $GLOBALS['injector']->getInstance('Horde_PageOutput');

        if (self::$_unblockId == 1) {
            $page_output->addScriptFile('imageunblock.js');
            $page_output->addInlineJsVars(array(
                'IMPImageUnblock.uri' => strval($this->_getUrl('ImageUnblock', 'imp', array('sessionWrite' => 1)))
            ), array('onload' => true));
        }

        $page_output->addInlineJsVars(array(
            'IMPImageUnblock.handles[' . Horde_Serialize::serialize($this->getDomId(), Horde_Serialize::JSON) . ']' => $js_params
        ), array('onload' => true));
    }

    /**
     * Perform the given action.
     *
     * Variables required in form input:
     *   - mailbox
     *   - uid
     *
     * @param array $args  Not used.
     * @param array $post  Not used.
     *
     * @return object  An object with the following entries:
     *   - success: (integer) 1 on success, 0 on failure.
     */
    public function handle($args, $post)
    {
        global $injector, $notification;

        $result = 0;
        $vars = Horde_Variables::getDefaultVariables();

        try {
            $contents = $injector->getInstance('IMP_Factory_Contents')->create(new IMP_Indices(IMP_Mailbox::formFrom($vars->mailbox), $vars->uid));

            $imgview = new IMP_Ui_Imageview();
            $imgview->addSafeAddress(IMP::bareAddress($contents->getHeader()->getValue('from')));

            $result = 1;
        } catch (Exception $e) {
            $notification->push($e, 'horde.error');
        }

        return new Horde_Core_Ajax_Response($result, true);
    }

    /**
     * Generates a unique DOM ID.
     *
     * @return string  A unique DOM ID.
     */
    public function getDomId()
    {
        return $this->_params['id'];
    }

}
