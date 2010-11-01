<?php
class Horde_Controller_FilterRunnerTest extends Horde_Test_Case
{
    public function testFilterRunnerDoesNotCallControllerWhenAPreFilterHandlesTheRequest()
    {
        $filter = $this->getMock('Horde_Controller_PreFilter', array('processRequest'));
        $filter->expects($this->once())
            ->method('processRequest')
            ->will($this->returnValue(Horde_Controller_PreFilter::REQUEST_HANDLED));

        $runner = new Horde_Controller_FilterRunner($this->_getControllerMockNeverCalled());
        $runner->addPreFilter($filter);
        $runner->processRequest($this->getMock('Horde_Controller_Request'), new Horde_Controller_Response());
    }

    public function testShouldUsePreFiltersInFirstInFirstOutOrder()
    {
        // The second filter should never be called because first filter returns
        // REQUEST_HANDLED, meaning it can handle the request.
        $preFilter1 = $this->getMock('Horde_Controller_PreFilter', array('processRequest'));
        $preFilter1->expects($this->once())
            ->method('processRequest')
            ->will($this->returnValue(Horde_Controller_PreFilter::REQUEST_HANDLED));

        $preFilter2 = $this->getMock('Horde_Controller_PreFilter', array('processRequest'));
        $preFilter2->expects($this->never())
            ->method('processRequest');

        $runner = new Horde_Controller_FilterRunner($this->_getControllerMockNeverCalled());
        $runner->addPreFilter($preFilter1);
        $runner->addPreFilter($preFilter2);
        $this->_runFilterRunner($runner);
    }

    public function testShouldUsePostFiltersInFirstInLastOutOrder()
    {
        // Both filters should be called because the first filter returns
        // REQUEST_HANDLED, meaning it can handle the request
        $postFilter1 = $this->getMock('Horde_Controller_PostFilter', array('processResponse'));
        $postFilter1->expects($this->once())
            ->method('processResponse')
            ->will($this->returnValue(Horde_Controller_PreFilter::REQUEST_HANDLED));

        $postFilter2 = $this->getMock('Horde_Controller_PostFilter', array('processResponse'));
        $postFilter2->expects($this->once())
            ->method('processResponse');


        $controller = $this->getMock('Horde_Controller', array('processRequest'));
        $controller->expects($this->once())
            ->method('processRequest');

        $runner = new Horde_Controller_FilterRunner($controller);
        $runner->addPostFilter($postFilter1);
        $runner->addPostFilter($postFilter2);
        $this->_runFilterRunner($runner);
    }

    private function _getControllerMockNeverCalled()
    {
        $controller = $this->getMock('Horde_Controller', array('processRequest'));
        $controller->expects($this->never())
            ->method('processRequest');
        return $controller;
    }

    private function _runFilterRunner(Horde_Controller_FilterRunner $runner)
    {
        $response = $this->getMock('Horde_Controller_Response', array('processRequest'));
        $response->expects($this->never())->method('processRequest');
        $runner->processRequest(new Horde_Controller_Request_Null(), $response);
    }
}
