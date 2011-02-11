<?php
/**
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @package    Horde_View
 * @subpackage Helper
 */

/**
 * View helper for displaying Horde block objects
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @package    Horde_View
 * @subpackage Helper
 */
class Horde_View_Helper_Block extends Horde_View_Helper_Base
{
    /**
     * Blocks that have already been fetched.
     *
     * @var array
     */
    protected $_blockCache = array();

    /**
     * Return the title of the specified block.
     *
     * @param string $block  The name of the block to get the title for.
     * @param mixed $arg1    (optional) The first argument to the Block
     *                       constructor.
     *
     * @return string  The requested Block's title.
     *
     * @throws Horde_View_Exception, InvalidArgumentException
     */
    public function blockTitle()
    {
        list($block, $params) = $this->_args(func_get_args());

        return $this->_block($block, $params)->getTitle();
    }

    /**
     * Return the content of the specified block.
     *
     * @param string $block  The name of the block to get the content for.
     * @param mixed $arg1    (optional) The first argument to the Block
     *                       constructor.
     *
     * @return string  The requested Block's content.
     *
     * @throws Horde_View_Exception, InvalidArgumentException
     */
    public function blockContent()
    {
        list($block, $params) = $this->_args(func_get_args());

        return $this->_block($block, $params)->getContent();
    }

    /**
     * Instantiate and cache Block objects
     *
     * @param string $block  The name of the block to fetch.
     * @param array $params  (option) Any arguments to the Block constructor.
     *
     * @return Horde_Core_Block  The requested Block object
     *
     * @throws Horde_View_Exception, InvalidArgumentException
     */
    protected function _block($block, $params)
    {
        $hash = sha1(serialize(array($block, $params)));

        if (!isset($this->_blockCache[$hash])) {
            try {
                $this->_blockCache[$hash] = $GLOBALS['injector']->getInstance('Horde_Core_Factory_BlockCollection')->create()->getBlock($block, $params);
            } catch (Exception $e) {
                throw new Horde_View_Exception($e);
            }
        }

        return $this->_blockCache[$hash];
    }

    /**
     * Parse any argument style for the Block-fetching functions
     *
     * @param array $args
     */
    protected function _args($args)
    {
        $argc = count($args);

        if ($argc == 1) {
            if (is_array($args[0])) {
                $args = $args[0];
                $argc = count($args);
            }
        }

        if ($argc < 2) {
            throw new InvalidArgumentException('You must provide at least an application name and a block name.');
        }
        $app = array_shift($args);
        $block = array_shift($args);

        return array($app, $block, $args);
    }

}
