<?php
/**
 * Components_Helper_Template:: converts a template into a target file.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Helper_Template:: converts a template into a target file.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Helper_Template
{
    /**
     * Source template.
     *
     * @var string
     */
    protected $_source;

    /**
     * Target file.
     *
     * @var string
     */
    protected $_target;

    /**
     * Constructor.
     *
     * @param string $source     The source location.
     * @param string $target     The target location.
     */
    public function __construct($source, $target)
    {
        $this->_source = $source;
        $this->_target = $target;
    }

    /**
     * Rewrite the template from the source to the target location.
     *
     * @param array  $parameters The template parameters.
     *
     * @return NULL
     */
    public function write(array $parameters = array())
    {
        throw new Horde_Component_Exception('Overwrite in the extending class!');
    }

    /**
     * A factory for the specific template type.
     */
    static public function factory($source, $target)
    {
        $sh = fopen($source, 'r');
        $lead = fread($sh, 5);
        fclose($sh);
        if ($lead == '<?php') {
            return new Components_Helper_Template_Php($source, $target);
        } else {
            return new Components_Helper_Template_Printf($source, $target);
        }
    }
}