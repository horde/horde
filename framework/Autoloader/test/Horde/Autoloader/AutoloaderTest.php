<?php
class Horde_Autoloader_AutoloaderTest extends PHPUnit_Framework_TestCase
{
    private $_autoloader;

    public function setUp()
    {
        $this->_autoloader = new Horde_Autoloader_TestHarness();
    }

    public function testInitialStateShouldYeildNoMatches()
    {
        $this->assertNull($this->_autoloader->mapToPath('The_Class_Name'));
    }

    public function testInitialStateShouldNotLoadAnyFiles()
    {
        $this->assertFalse($this->_autoloader->loadClass('The_Class_Name'));
    }

    public function testShouldNotMapClassIfMapperDoesNotReturnAPath()
    {
        $this->_autoloader->addClassPathMapper($this->_getUnsuccessfulMapperMock());
        $this->assertNull($this->_autoloader->mapToPath('The_Class_Name'));
    }

    public function testShouldLoadPathMapperDoesNotReturnAPath()
    {
        $this->_autoloader->addClassPathMapper($this->_getUnsuccessfulMapperMock());
        $this->assertFalse($this->_autoloader->loadClass('The_Class_Name'));
    }

    public function testShouldMapClassIfAMapperReturnsAPath()
    {
        // trick the autoloader into thinking the returned path exists
        $this->_autoloader->setFileExistsResponse(true);

        $this->_autoloader->addClassPathMapper($this->_getSuccessfulMapperMock());

        $this->assertEquals('The/Class/Name.php', $this->_autoloader->mapToPath('The_Class_Name'));
    }

    public function testShouldNotMapClassIfAMapperReturnsAPathThatDoesNotExist()
    {
        // trick the autoloader into thinking the returned path does not exist
        $this->_autoloader->setFileExistsResponse(false);

        $this->_autoloader->addClassPathMapper($this->_getSuccessfulMapperMock());

        $this->assertNull($this->_autoloader->mapToPath('The_Class_Name'));
    }

    public function testShouldLoadFileIfMapperReturnsAValidPath()
    {
        // trick the autoloader into thinking the returned path exists and was included
        $this->_autoloader->setFileExistsResponse(true);
        $this->_autoloader->setIncludeResponse(true);

        $this->_autoloader->addClassPathMapper($this->_getSuccessfulMapperMock());

        $this->assertTrue($this->_autoloader->loadClass('The_Class_Name'));
    }

    public function testShouldLoadFileIfMapperReturnsAValidPathButIncludingItFails()
    {
        // trick the autoloader into thinking the returned path exists and was included
        $this->_autoloader->setFileExistsResponse(true);
        $this->_autoloader->setIncludeResponse(false);

        $this->_autoloader->addClassPathMapper($this->_getSuccessfulMapperMock());

        $this->assertFalse($this->_autoloader->loadClass('The_Class_Name'));
    }

    private function _getSuccessfulMapperMock()
    {
        $mapper = $this->getMock('Horde_Autoloader_ClassPathMapper', array('mapToPath'));
        $mapper->expects($this->once())
            ->method('mapToPath')
            ->with($this->equalTo('The_Class_Name'))
            ->will($this->returnValue('The/Class/Name.php'));

        return $mapper;
    }

    private function _getUnsuccessfulMapperMock()
    {
        $mapper = $this->getMock('Horde_Autoloader_ClassPathMapper', array('mapToPath'));
        $mapper->expects($this->once())
            ->method('mapToPath')
            ->with($this->equalTo('The_Class_Name'))
            ->will($this->returnValue(null));

        return $mapper;
    }
}

class Horde_Autoloader_TestHarness extends Horde_Autoloader
{
    private $_includeResponse;
    private $_fileExistsResponse;

    public function setIncludeResponse($bool)
    {
        $this->_includeResponse = $bool;
    }

    public function setFileExistsResponse($bool)
    {
        $this->_fileExistsResponse = $bool;
    }

    protected function _include($path)
    {
        return $this->_includeResponse;
    }

    protected function _fileExists($path)
    {
        return $this->_fileExistsResponse;
    }
}
