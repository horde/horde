<?php
/**
 * Collects filters and executes them around a controller
 *
 * @category Horde
 * @package  Horde_Controller
 * @author   Bob McKee <bob@bluestatedigital.com>
 * @author   James Pepin <james@bluestatedigital.com>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 */
class Horde_Controller_FilterRunner implements Horde_Controller_FilterCollection
{
    /**
     * @var Horde_Controller
     */
    protected $_controller;

    /**
     * @var array
     */
    protected $_preFilters = array();

    /**
     * @var array
     */
    protected $_postFilters = array();

    /**
     */
    public function __construct(Horde_Controller $controller)
    {
        $this->_controller = $controller;
    }

    /**
     * Append filter to prefilters array
     *
     * @param Horde_Controller_PreFilter $filter
     */
    public function addPreFilter(Horde_Controller_PreFilter $filter)
    {
        array_push($this->_preFilters, $filter);
    }

    /**
     * Prepend fitler to postfilters array
     *
     * @param Horde_Controller_PostFilter $filter
     */
    public function addPostFilter(Horde_Controller_PostFilter $filter)
    {
        array_unshift($this->_postFilters, $filter);
    }

    /**
     * Executes filters and controller method. Execution happens in the following order:
     *
     * - Run processRequest() on prefilters in first-in-first-out order
     * - Run processRequest() on controller
     * - Run processResponse() on postfilters in first-in-last-out order
     *
     * @param Horde_Controller_Request $request
     * @param Horde_Controller_Response $response
     *
     * @return Horde_Controller_Response
     */
    public function processRequest(Horde_Controller_Request $request, Horde_Controller_Response $response)
    {
        if ($this->_applyPreFilters($request, $response) !== Horde_Controller_PreFilter::REQUEST_HANDLED) {
            $this->_controller->processRequest($request, $response);
            $this->_applyPostFilters($request, $response);
        }
        return $response;
    }

    /**
     */
    protected function _applyPreFilters(Horde_Controller_Request $request, Horde_Controller_Response $response)
    {
        foreach ($this->_preFilters as $filter) {
            if ($filter->processRequest($request, $response, $this->_controller) === Horde_Controller_PreFilter::REQUEST_HANDLED) {
                return Horde_Controller_PreFilter::REQUEST_HANDLED;
            }
        }

        return Horde_Controller_PreFilter::REQUEST_CONTINUE;
    }

    /**
     */
    protected function _applyPostFilters(Horde_Controller_Request $request, Horde_Controller_Response $response)
    {
        foreach ($this->_postFilters as $filter) {
            $filter->processResponse($request, $response, $this->_controller);
        }
    }
}
