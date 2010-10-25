<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Image
{
    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector  The injector to use.
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Returns an appropriate Horde_Image object based on Horde's configuration.
     *
     * @param array $params  An array of image parameters. @see Horde_Image_Base
     * @return Horde_Image
     * @throws Horde_Exception
     */
    public function create(array $params = array())
    {
        global $conf;
        
        $driver = $conf['image']['driver'];
        $context = array(
            'tmpdir' => Horde::getTempdir(),
            'logger' => $this->_injector->getInstance('Horde_Log_Logger'));
        if ($driver == 'Im') {
            $context['convert'] = $conf['image']['convert'];
            $context['identify'] = $conf['image']['identify'];
        }
        // Use the default
        $class = 'Horde_Image_' . $driver;
        if (class_exists($class)) {
            return new $class($params, $context);
        } else {
            throw new Horde_Exception('Invalid Image driver specified: ' . $class . ' not found.');
        }
    }

}