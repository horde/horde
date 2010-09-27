<?php
/**
 * The Horde_Image_Effect parent class defines a general API for
 * ways to apply effects to Horde_Image objects.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Image
 */
class Horde_Image_Effect
{
    /**
     * Effect parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * The bound Horde_Image object
     *
     * @var Horde_Image
     */
    protected $_image = null;

    protected $_logger;

    /**
     * Effect constructor.
     *
     * @param array $params  Any parameters for the effect. Parameters are
     *                       documented in each subclass.
     */
    public function __construct($params = array())
    {
        foreach ($params as $key => $val) {
            $this->_params[$key] = $val;
        }
    }

    /**
     * Bind this effect to a Horde_Image object.
     *
     * @param Horde_Image $image  The Horde_Image object
     *
     * @TODO: Can we get rid of the reference here? (Looks OK for GD, but need
     *        to test im/imagick also).
     *
     * @return void
     */
    public function setImageObject(&$image)
    {
        $this->_image = &$image;
    }

    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }

    public function factory($type, $driver, $params)
    {
        if (is_array($type)) {
            list($app, $type) = $type;
        }

        // First check for a driver specific effect, if we can't find one,
        // assume there is a vanilla effect object around.
        $class = 'Horde_Image_Effect_' . $driver . '_' . $type;
        $vclass = 'Horde_Image_Effect_' . $type;
        if (!class_exists($class) && !class_exists($vclass)) {
            if (!empty($app)) {
                $path = $GLOBALS['registry']->get('fileroot', $app) . '/lib/Image/Effect/' . $driver . '/' . $type . '.php';
            } else {
                $path = 'Horde/Image/Effect/' . $driver . '/' . $type . '.php';
            }

            @include_once $path;
            if (!class_exists($class)) {
                 if (!empty($app)) {
                    $path = $GLOBALS['registry']->get('fileroot', $app) . '/lib/Image/Effect/' . $type . '.php';
                } else {
                    $path = 'Horde/Image/Effect/' . $type . '.php';
                }
                $class = $vclass;
                @include_once $path;
            }
        }
        if (class_exists($class)) {
            $effect = new $class($params);
        } else {
            throw new Horde_Image_Exception(sprintf("Horde_Image_Effect %s for %s driver not found.", $type, $driver));
        }

        if (!empty($params['logger'])) {
            $effect->setLogger($params['logger']);
        }

        return $effect;
    }

}