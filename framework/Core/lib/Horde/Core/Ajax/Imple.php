<?php
/**
 * Class to attach PHP actions to javascript elements.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
abstract class Horde_Core_Ajax_Imple
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
     * @return Horde_Url
     */
    protected function _getUrl($driver, $app = 'horde', $params = array(),
                               $full = false)
    {
        $qstring = $driver;

        if ($app != 'horde') {
            $qstring .= '/impleApp=' . $app;
        }

        foreach ($params as $key => $val) {
            $qstring .= '/' . $key . '=' . rawurlencode($val);
        }

        return Horde::url(Horde::getServiceLink('imple')->url, $full, array('noajax' => true))->setRaw(true)->add('imple', $qstring);
    }

    /**
     * Generate a random ID string.
     *
     * @return string  The random ID string.
     */
    protected function _randomid()
    {
        return strval(new Horde_Support_Randomid());
    }

}
