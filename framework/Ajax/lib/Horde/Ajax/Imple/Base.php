<?php
/**
 * Class to attach PHP actions to javascript elements.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Ajax
 */
abstract class Horde_Ajax_Imple_Base
{
    /**
     * Parameters needed by the subclasses.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Constructor.
     *
     * @param array $params  Any parameters needed by the class.
     */
    public function __construct($params)
    {
        $this->_params = $params;
    }

    /**
     * Attach the object to a javascript event.
     */
    abstract public function attach();

    /**
     * TODO
     *
     * @param array $args  TODO
     */
    abstract public function handle($args, $post);

    /**
     * TODO
     *
     * @param string $driver
     * @param string $app
     * @param array $params
     * @param boolean $full
     *
     * @return string
     */
    protected function _getUrl($driver, $app = 'horde', $params = array(),
                               $full = false)
    {
        $qstring = 'imple=' . $driver;

        if ($app != 'horde') {
            $qstring .= '/impleApp=' . $app;
        }

        foreach ($params as $key => $val) {
            $qstring .= '/' . $key . '=' . rawurlencode($val);
        }

        return Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/services/imple.php?' . $qstring, $full);
    }

    /**
     * Generate a random ID string.
     *
     * @return string  The random ID string.
     */
    protected function _randomid()
    {
        return 'imple_' . uniqid(mt_rand());
    }

}
