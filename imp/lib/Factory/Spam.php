<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * A Horde_Injector based IMP_Spam factory.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Factory_Spam extends Horde_Core_Factory_Base
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return a IMP_Spam instance.
     *
     * @param integer $action  Either IMP_Spam::SPAM or IMP_Spam::INNOCENT.
     *
     * @return IMP_Spam  The spam instance.
     * @throws IMP_Exception
     */
    public function create($action)
    {
        if (!isset($this->_instances[$action])) {
            switch ($action) {
            case IMP_Spam::INNOCENT:
                $config = $this->_injector->getInstance('IMP_Factory_Imap')->create()->config->innocent_params;
                break;

            case IMP_Spam::SPAM:
                $config = $this->_injector->getInstance('IMP_Factory_Imap')->create()->config->spam_params;
                break;
            }

            $drivers = (!empty($config['drivers']) && is_array($config['drivers']))
                ? $config['drivers']
                : array();

            if (!empty($config['program'])) {
                $drivers[] = new IMP_Spam_Program(
                    $this->_expand($config['program'], true)
                );
            }

            if (!empty($config['email'])) {
                $drivers[] = new IMP_Spam_Email(
                    $this->_expand($config['email']),
                    empty($config['email_format']) ? 'digest' : $config['email_format']
                );
            }

            if (!empty($config['null'])) {
                $drivers[] = new IMP_Spam_Null($config['null']);
            }

            $this->_instances[$action] = new IMP_Spam($action, $drivers);
        }

        return $this->_instances[$action];
    }

    /**
     * Expand placeholders in 'email' and 'program' options.
     *
     * @param string $str      The option.
     * @param boolean $escape  Shell escape the replacements?
     *
     * @return string  The expanded option.
     */
    private function _expand($str, $escape = false)
    {
        global $registry;

        $replace = array(
            '%u' => $registry->getAuth(),
            '%l' => $registry->getAuth('bare'),
            '%d' => $registry->getAuth('domain')
        );

        return str_replace(
            array_keys($replace),
            $escape ? array_map('escapeshellarg', array_values($replace)) : array_values($replace),
            $str
        );
    }

}
