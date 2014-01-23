<?php
/**
 * AdminCli_Module_Config:: provides functionality for 
 * initializing, exploring and editing conf.php files
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Horde
 * @author   Ralf Lang <lang@b1-systems.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Horde
 */

/**
 * AdminCli_Module_Config:: provides functionality for 
 * initializing, exploring and editing conf.php files
 *
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Horde
 * @author   Ralf Lang <lang@b1-systems.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Horde
 */
class AdminCli_Module_Config extends AdminCli_Module_Base {

    public function getUsage()
    {
        return "config init\n
config set key:key:key value\n
config set key:key:key --default\n
config list-keys\n
        ";
    }

    public function getOptionGroupTitle()
    {
        return "Manage application config\n";
    }

    public function getOptionGroupDescription()
    {
        return "This module manages config.php\n";
    }

    public function getOptionGroupOptions()
    {
        return array(
            new Horde_Argv_Option(
                '',
                '--default',
                array (
                    'action' => 'store_true',
                    'help' => 'Set a value to default from xml'
                    )
            ),
            new Horde_Argv_Option(
                '',
                '--app',
                array (
                    'action' => 'store',
                    'default' => 'horde',
                    'help' => 'Defaults to horde'
                    )
            ),
        );
    }
    public function getBaseOptions()
    {
        return array();
    }

   /** 
    *   module entry point 
    *   decide which action to run if any
    */
    public function run($cli, $options, $arguments)
    {
        $this->_options = $options;
        $this->_cli = $cli;
        $this->_app = $options['app'];
        $action = (empty($arguments[1])) ? '' : $arguments[1];
        switch ($action) {
            case 'set':
                if (empty($arguments[2])) {
                    $cli->message('Set requires a key to set as an argument', 'cli.error');
                } elseif ($options['default'] && !isset($arguments[3])) {
                    $this->_set($arguments[2]);
                } elseif (isset($arguments[3])) {
                    $this->_set($arguments[2], $arguments[3]);
                } else {
                    $cli->message('You must either specify a value to set or --default', 'cli.error');
                }
            break;
            case 'init':
                unlink($GLOBALS['registry']->get('fileroot', $this->_app) . '/config/conf.php');
                $this->_set();
            break;
            case 'list-keys':
                $this->_renderConfigKeys($this->_getConfigKeys());
            break;
            default:
            $cli->message('No valid operation', 'cli.error');
            $cli->message($this->getUsage());
            break;
        }
    }

    /**
     * Retrieve the hardcoded config xml default for one item regardless of conf.php
     *
     * @param string $key  The key for the config value to retrieve the default
     * @return mixed       The config default - this should be string or boolean
     */
    protected function _default($key)
    {
        $config = new Horde_Config($this->_app);
        $xmlConfig = $config->readXMLConfig();
        $value = $xmlConfig;
        return $config->getXmlDefaultValue(split(':', $key));
    }


    /**
     * Retrieve the current config from xml template and 
     * - if exists - current conf.php and change one key's value
     *
     * @param string $key    The key for the config value to change
     * @param string $value  The value for the config setting to change
     */
    protected function _set($key = null, $value = null)
    {
        $config = $this->_getConfigKeys();
        if ($key) {
            if (!$this->_exists($key)) {
                $this->_renderConfigKeys($config);
                $this->_cli->message(sprintf('%s is not a valid config key in app %s', $key, $this->_app), 'cli.error');
            }
            if ($value == null) {
                $value = $this->_default($key);
            }
            $config->set(str_replace(':', '__', $key),$value);
        }
        $writer = new Horde_Config($this->_app);
        try {
            $writer->writePHPConfig($config);
        } catch (Horde_Error $e) {
            $this->_cli->message($e-getMessage(), 'cli.error');
        }
    }

    /**
     *  Check if a configitem in a:b:c form is a valid config key for conf.php
     *  @param string $key  The key in section1:section2:key form
     *  @return boolean  True if valid leave item
     */
    protected function _exists($key)
    {
        $keys = $this->_getConfigKeys();
        return $keys->exists(str_replace(':', '__', $key));
    }

    /**
     * Render a printable list of valid config keys
     * @param array $keyArray  an array of valid config entries
     */
    protected function _renderConfigKeys(Horde_Variables $keys) 
    {
        /* TODO: Sorting this by key would be pretty, but Horde_Variables won't do it */
        foreach ($keys as $key => $entry) {
            $this->_cli->writeln(str_replace('__', ':', $key));
        }
    }

    /**
     * Filter an array of valid config entries for the conf.php file
     * Results may vary based on previous config choices -- for example driver configs
     * @return array  array of valid config entries.
     */
    protected function _getConfigKeys()
    {
        $config = new Horde_Config($this->_app);
        $entries = new Horde_Variables();
        /* TODO: Refactor. This is ugly.
           Horde_Config_Form encapsulates code to populate a Horde_Variables object
           suitable for the config rendering in generatePHPConfig
        */
        $form = new Horde_Config_Form($entries, $this->_app, true);

        /* Add primitives which won't be written if false/empty. We want them for validation */
        $path = $GLOBALS['registry']->get('fileroot', $this->_app) . '/config';
        $dom = new DOMDocument();
        $dom->load($path . '/conf.xml');
        $xpath = new DOMXpath($dom);
        $queryString = "//configboolean";
        $nodes = $xpath->query($queryString);
        foreach($nodes as $node) {
            $nodeName = $node->attributes->getNamedItem('name')->value;
            $nodeValue = $node->textContent;
            while ($node->parentNode->nodeName != 'configuration') {
                $node = $node->parentNode;
                $nodeName = sprintf('%s__%s',
                    $node->attributes->getNamedItem('name')->value,
                    $nodeName
                );
            }
            if (!$entries->exists($nodeName)) {
                $entries->set($nodeName, false);
            }
        }

        $entries->remove('app');
        $entries->remove('__formOpenSection');
        return $entries;
    }
}