<?php
/**
 * Horde Signup Form.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Marko Djukic <marko@oblo.com>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Core
 */
class Horde_Core_Auth_Signup_Form extends Horde_Form
{
    /**
     * @var boolean
     */
    protected $_useFormToken = true;

    /**
     * Constructor
     *
     * @var params Horde_Variables  TODO
     */
    public function __construct(&$vars)
    {
        parent::__construct($vars, sprintf(Horde_Core_Translation::t("%s Sign Up"), $GLOBALS['registry']->get('name')));

        $this->setButtons(Horde_Core_Translation::t("Sign up"), true);

        $this->addHidden('', 'url', 'text', false);

        /* Use hooks get any extra fields required in signing up. */
        try {
            $extra = Horde::callHook('signup_getextra');
        } catch (Horde_Exception_HookNotSet $e) {}

        if (!empty($extra)) {
            if (!isset($extra['user_name'])) {
                $this->addVariable(Horde_Core_Translation::t("Choose a username"), 'user_name', 'text', true);
            }
            if (!isset($extra['password'])) {
                $this->addVariable(Horde_Core_Translation::t("Choose a password"), 'password', 'passwordconfirm', true, false, Horde_Core_Translation::t("type the password twice to confirm"));
            }
            foreach ($extra as $field_name => $field) {
                $readonly = isset($field['readonly']) ? $field['readonly'] : null;
                $desc = isset($field['desc']) ? $field['desc'] : null;
                $required = isset($field['required']) ? $field['required'] : false;
                $field_params = isset($field['params']) ? $field['params'] : array();

                $this->addVariable($field['label'], 'extra[' . $field_name . ']',
                                   $field['type'], $required, $readonly,
                                   $desc, $field_params);
            }
        } else {
            $this->addVariable(Horde_Core_Translation::t("Choose a username"), 'user_name', 'text', true);
            $this->addVariable(Horde_Core_Translation::t("Choose a password"), 'password', 'passwordconfirm', true, false, Horde_Core_Translation::t("type the password twice to confirm"));
        }
    }

    /**
     * Fetch the field values of the submitted form.
     *
     * @param Variables $vars  A Variables instance (Needed?).
     * @param array $info      Array to be filled with the submitted field
     *                         values.
     */
    public function getInfo($vars, &$info)
    {
        parent::getInfo($vars, $info);

        if (!isset($info['user_name']) && isset($info['extra']['user_name'])) {
            $info['user_name'] = $info['extra']['user_name'];
        }

        if (!isset($info['password']) && isset($info['extra']['password'])) {
            $info['password'] = $info['extra']['password'];
        }
    }

}
