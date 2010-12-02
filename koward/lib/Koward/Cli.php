<?php
/**
 * Request helper for command line calls.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Koward
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Koward
 */

/**
 * A base for the Koward command line requests.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Koward
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Koward
 */
class Koward_Cli extends Horde_Controller_Request_Base
{
    /**
     * Command line arguments
     */
    protected $_argv;

    /**
     * Command arguments
     */
    protected $_cmd_argv;

   /**
     */
    public function __construct($options = array())
    {
        global $registry, $conf;

        parent::__construct($options);

        $options = array(
            new Horde_Argv_Option('-b', '--base', array('type' => 'string')),
            new Horde_Argv_Option('-u', '--user', array('type' => 'string')),
            new Horde_Argv_Option('-p', '--pass', array('type' => 'string')),
        );
        $parser = new Horde_Argv_Parser(
            array(
                'allowUnknownArgs' => true,
                'optionList' => $options,
                'addHelpOption' => false,
            )
        );
        list($this->_argv, $args) = $parser->parseArgs();
        if (!count($args)) {
            throw new Koward_Exception('unknown command: ' . implode(' ', $args));
        }

        /**
         * FIXME: Workaround to fix the path so that the command line call
         * really only needs the route.
         */
        $this->_path = $registry->get('webroot', 'koward') . '/' . $args[0];

        try {
            $registry->pushApp('koward', false);
        } catch (Horde_Exception $e) {
            if ($e->getCode() == 'permission_denied') {
                echo 'Perission denied!';
                exit;
            }
        }

        $this->_cmd_argv = array();

        /* Authenticate the user if possible. */
        if ($this->_argv->user) {
            $auth = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create();
            if (!$auth->authenticate($this->_argv->user,
                                     array('password' => $this->_argv->pass))) {
                throw new InvalidArgumentException('Failed to log in!');
            }
        }

        try {
            $registry->pushApp('koward',
                               empty($this->auth_handler)
                               || $this->auth_handler != $this->params[':action']);
        } catch (Horde_Exception $e) {
            if ($e->getCode() == 'permission_denied') {
                $this->urlFor(array('controller' => 'index', 'action' => 'login'))
                    ->redirect();
            }
        }

        /**
         * A rough command line handler that allows us to map CLI requests into
         * the web view of the system.
         */
        switch ($args[0]) {
        case 'object/add':
            $this->_cmd_argv['formname'] = 'koward_form_object';

            /** Has the object type been set? */
            if ($this->_argv->type && is_array($this->_argv->type)
                && count($this->_argv->type) == 1) {
                $type = $this->_argv->type[0];

                /**
                 * FIXME: Editing on the command line does not work if we don't
                 * specify the full set of form attributes. Yet another reason
                 * for not using the Form.
                 */
                if ($this->_argv->id && is_array($this->_argv->id)
                    && count($this->_argv->id) == 1) {
                    $this->_cmd_argv['id'] = $this->_argv->id[0];
                } else {
                    $this->_cmd_argv['id'] = $this->_argv->id;
                }

                /**
                 * Fake the selected type for the form handler and short circuit the
                 * type selection machinery.
                 */
                $this->_cmd_argv['__old_type'] = $type;

                /**
                 * Fake the form token. Hm, it does not really make much sense
                 * to use the standard form mechanisms via CLI. Think of some
                 * alternatives here.
                 */
                $token = $GLOBALS['injector']->getInstance('Horde_Token')->get('cli');
                $this->_cmd_argv['koward_form_object_formToken'] = $token;

                /**
                 * FIXME: Allow retrieving the form fields without specifying $vars.
                 */
                $object = null;
                $form = new Koward_Form_Object(Horde_Variables::getDefaultVariables(), $object);

                $fields = array_keys($form->getTypeFields($type));

                /**
                 * Now that we know the type of object that should be edited we
                 * can restrict the amount of options we allow.
                 */
                $options = array(
                    new Horde_Argv_Option('-b', '--base', array('type' => 'string')),
                    new Horde_Argv_Option('-u', '--user', array('type' => 'string')),
                    new Horde_Argv_Option('-p', '--pass', array('type' => 'string')),
                    new Horde_Argv_Option('-t', '--type', array('type' => 'string')),
                    new Horde_Argv_Option('-i', '--id', array('type' => 'string')),
                );
                foreach ($fields as $field) {
                    $options[] = new Horde_Argv_Option(null, '--' . $field,
                                                       array('type' => 'string'));
                }
                $parser = new Horde_Argv_Parser(
                    array(
                        'allowUnknownArgs' => false,
                        'optionList' => $options,
                        'addHelpOption' => true,
                    )
                );
                list($cmd_argv, $cmd) = $parser->parseArgs();
                foreach ($cmd_argv as $field => $value) {
                    if ($field == 'userPassword') {
                        /**
                         * FIXME: Obvious hack and probably another reason why
                         * mixing forms and CLI does not make that much
                         * sense.
                         */
                        $this->_cmd_argv['object']['userPassword']['original'] = $value;
                        $this->_cmd_argv['object']['userPassword']['confirm'] = $value;
                    } else if (in_array($field, $fields) && $value !== null) {
                        $this->_cmd_argv['object'][$field] = $value;
                    }
                }
            }
            break;
        case 'object/delete':
            if ($this->_argv->id && is_array($this->_argv->id)
                && count($this->_argv->id) == 1) {
                $this->_cmd_argv['id'] = $this->_argv->id[0];
            } else {
                $this->_cmd_argv['id'] = $this->_argv->id;
            }

            /**
             * Provide a token for immediate deletion.
             */
            $this->_cmd_argv['token'] = $GLOBALS['injector']->getInstance('Horde_Token')->get('object.delete');

            break;
        }
    }

    public function getUri()
    {
        return $this->getPath();
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function getArguments()
    {
        return $this->_argv;
    }

    /**
     * Get all command line parameters.
     * some wacky loops to make sure that nested values in one
     * param list don't overwrite other nested values
     *
     * @return  array
     */
    public function getParameters()
    {
        $allParams = array();
        $paramArrays = array($this->_pathParams, $this->_argv, $this->_cmd_argv);

        foreach ($paramArrays as $params) {
            foreach ((array)$params as $key => $value) {
                if (!is_array($value) || !isset($allParams[$key])) {
                    $allParams[$key] = $value;
                } else {
                    $allParams[$key] = array_merge($allParams[$key], $value);
                }
            }
        }
        return $allParams;
    }

}
