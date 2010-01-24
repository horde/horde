<?php
class Horde_Core_Controller_RequestMapper
{
    /**
     * @var Horde_Routes_Mapper $mapper
     */
    protected $_mapper;

    public function __construct(Horde_Routes_Mapper $mapper)
    {
        $this->_mapper = $mapper;
    }

    public function getRequestConfiguration(Horde_Injector $injector)
    {
        $request = $injector->getInstance('Horde_Controller_Request');
        $registry = $injector->getInstance('Horde_Registry');
        $settingsFinder = $injector->getInstance('Horde_Core_Controller_SettingsFinder');

        $config = $injector->createInstance('Horde_Core_Controller_RequestConfiguration');

        $uri = substr($request->getPath(), strlen($registry->get('webroot', 'horde')));
        $uri = trim($uri, '/');
        if (strpos($uri, '/') === false) {
            $app = $uri;
        } else {
            list($app,) = explode('/', $uri, 2);
        }

        // Check for route definitions.
        $fileroot = $registry->get('fileroot', $app);
        $routeFile = $fileroot . '/config/routes.php';
        if (!file_exists($routeFile)) {
            throw new Horde_Routes_Exception('Not routable: ' . $uri . '. ' . $routeFile . ' does not exist.');
        }

        // Push $app onto the registry
        $registry->pushApp($app);
        $config->setApplication($app);

        // Application routes are relative only to the application. Let the
        // mapper know where they start.
        $this->_mapper->prefix = $registry->get('webroot', $app);

        // Set the application controller directory
        $this->_mapper->directory = $registry->get('fileroot', $app) . '/app/controllers';

        // Load application routes.
        $mapper = $this->_mapper;
        include $routeFile;

        // Match
        // @TODO Cache routes
        $match = $this->_mapper->match($request->getPath());

        if (isset($match['controller'])) {
            $config->setControllerName(ucfirst($app) . '_' . ucfirst($match['controller']) . '_Controller');
            $config->setSettingsExporterName($settingsFinder->getSettingsExporterName($config->getControllerName()));
        } else {
            $config->setControllerName('Horde_Core_Controller_NotFound');
        }

        return $config;
    }
}
