<?php
/**
 * Class to execute the controller request
 *
 * @category Horde
 * @package  Horde_Controller
 * @author   James Pepin <james@bluestatedigital.com>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 */
class Horde_Controller_Runner
{
    protected $_logger;

    public function __construct(Horde_Log_Logger $logger)
    {
        $this->_logger = $logger;
    }

    public function execute(Horde_Injector $injector, Horde_Controller_Request $request, Horde_Controller_RequestConfiguration $config)
    {
        $this->_logger->debug('RequestConfiguration in Horde_Controller_Runner: ' . print_r($config, true));

        $exporter = $injector->getInstance($config->getSettingsExporterName());
        $exporter->exportBindings($injector);

        $controller = $config->getControllerName();
        if (!$controller) {
            throw new Horde_Controller_Exception('No controller defined');
        }

        $implementationBinder = new Horde_Injector_Binder_Implementation($controller);
        $injector->addBinder('Horde_Controller', new Horde_Injector_Binder_AnnotatedSetters($implementationBinder));

        $filterRunner = $injector->createInstance('Horde_Controller_FilterRunner');
        $exporter->exportFilters($filterRunner, $injector);

        $response = $filterRunner->processRequest($request, $injector->createInstance('Horde_Controller_Response'));
        if ($response->internalRedirect()) {
            $this->_logger->debug('Internal redirect');
            return $this->execute($injector->createChildInjector(), $request, $response->getRedirectConfiguration());
        }

        $this->_logger->debug('Returning Horde_Controller_Response');
        return $response;
    }
}
