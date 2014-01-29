<?php
/**
 * Copyright 2012-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */

/**
 * A Horde_Injector based spellchecker factory.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 * @since    2.1.0
 */
class Horde_Core_Factory_SpellChecker extends Horde_Core_Factory_Base
{
    /**
     * Returns the spellchecker instance.
     *
     * @param array $args    Configuration arguments to override the
     *                       defaults.
     * @param string $input  Input text.  If set, allows language detection
     *                       if not automatically set.
     *
     * @return Horde_SpellChecker  The spellchecker instance.
     * @throws Horde_Exception
     */
    public function create(array $args = array(), $input = null)
    {
        global $conf, $language;

        if (empty($conf['spell']['driver'])) {
            throw new Horde_Exception('No spellcheck driver configured.');
        }

        $args = array_merge(
            array('localDict' => array()),
            Horde::getDriverConfig('spell', null),
            $args
        );

        if (empty($args['locale'])) {
            if (!is_null($input)) {
                try {
                    $args['locale'] = $this->_injector->getInstance('Horde_Core_Factory_LanguageDetect')->getLanguageCode($input);
                } catch (Horde_Exception $e) {}
            }

            if (empty($args['locale']) && isset($language)) {
                $args['locale'] = $language;
            }
        }

        /* Add local dictionary words. */
        try {
            $sconf = new Horde_Registry_Loadconfig('horde', 'spelling.php', 'ignore_list');
            $args['localDict'] = array_merge(
                $args['localDict'],
                $sconf->config['ignore_list']
            );
        } catch (Horde_Exception $e) {}

        $classname  = 'Horde_SpellChecker_' . Horde_String::ucfirst(basename($conf['spell']['driver']));
        if (!class_exists($classname)) {
            throw new Horde_Exception('Spellcheck driver does not exist.');
        }

        return new $classname($args);
    }

}
