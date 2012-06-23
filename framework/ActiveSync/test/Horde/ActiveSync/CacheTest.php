<?php
/**
 * Unit tests for the syncCache
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package ActiveSync
 */
class Horde_ActiveSync_CacheTest extends Horde_Test_Case
{
    protected $_fixture;
    protected $_state;

    public function setUp()
    {
        $this->_fixture = unserialize(
            'a:10:{s:18:"confirmed_synckeys";a:1:{s:39:"{4fd981c6-51b0-4274-a3ae-0a69c0a8015f}2";b:1;}s:17:"lasthbsyncstarted";b:0;s:17:"lastsyncendnormal";i:1339654598;s:9:"lastuntil";i:1339654598;s:9:"timestamp";i:1339654586;s:4:"wait";b:0;s:10:"hbinterval";b:0;s:7:"folders";a:33:{s:4:"test";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:4:"test";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:11:"spam_folder";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:4:"Spam";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:9:"sent-mail";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:9:"sent-mail";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:12:"bad messages";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:12:"bad messages";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:8:"Verendus";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:8:"Verendus";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:5:"Trash";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:5:"Trash";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:9:"Templates";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:9:"Templates";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:13:"Spam Training";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:13:"Spam Training";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:13:"SiliconMemory";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:13:"SiliconMemory";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:13:"Sent Messages";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:13:"Sent Messages";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:4:"Sent";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:4:"Sent";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:6:"Review";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:6:"Review";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:8:"Pharmacy";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:8:"Pharmacy";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:7:"Medical";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:7:"Medical";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:5:"INBOX";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:5:"Inbox";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:10:"Horde, LLC";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:10:"Horde, LLC";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:13:"Horde Website";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:13:"Horde Website";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:17:"Horde UI Redesign";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:17:"Horde UI Redesign";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:15:"Horde Sent Mail";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:15:"Horde Sent Mail";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:14:"Horde Security";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:14:"Horde Security";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:11:"Horde Lists";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:11:"Horde Lists";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:11:"Horde Ideas";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:11:"Horde Ideas";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:15:"Horde Hackathon";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:15:"Horde Hackathon";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:16:"Horde Consulting";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:16:"Horde Consulting";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:7:"Horde 5";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:7:"Horde 5";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:15:"General Archive";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:15:"General Archive";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:6:"Family";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:6:"Family";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:31:"Code Samples, Personal Web Work";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:31:"Code Samples, Personal Web Work";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:15:"Apple Dev Stuff";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:15:"Apple Dev Stuff";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:10:"ActiveSync";a:5:{s:8:"parentid";s:1:"0";s:11:"displayname";s:10:"ActiveSync";s:5:"class";s:5:"Email";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:7:"@Tasks@";a:5:{s:8:"parentid";i:0;s:11:"displayname";s:5:"Tasks";s:5:"class";s:5:"Tasks";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:10:"@Contacts@";a:5:{s:8:"parentid";i:0;s:11:"displayname";s:8:"Contacts";s:5:"class";s:8:"Contacts";s:4:"type";N;s:10:"filtertype";s:1:"0";}s:10:"@Calendar@";a:5:{s:8:"parentid";i:0;s:11:"displayname";s:8:"Calendar";s:5:"class";s:8:"Calendar";s:4:"type";N;s:10:"filtertype";s:1:"0";}}s:9:"hierarchy";s:39:"{4fd981ba-6e58-440c-8d6f-0a69c0a8015f}1";s:11:"collections";a:2:{s:10:"@Contacts@";a:10:{s:5:"class";s:8:"Contacts";s:10:"windowsize";s:2:"25";s:14:"deletesasmoves";N;s:10:"filtertype";N;s:10:"truncation";i:0;s:13:"rtftruncation";N;s:11:"mimesupport";i:0;s:14:"mimetruncation";N;s:8:"conflict";i:1;s:9:"bodyprefs";a:2:{s:6:"wanted";s:1:"1";i:1;a:2:{s:4:"type";s:1:"1";s:14:"truncationsize";s:5:"32768";}}}s:10:"@Calendar@";a:11:{s:5:"class";s:8:"Calendar";s:10:"windowsize";s:2:"25";s:14:"deletesasmoves";N;s:10:"filtertype";s:1:"6";s:10:"truncation";i:0;s:13:"rtftruncation";N;s:11:"mimesupport";i:0;s:14:"mimetruncation";N;s:8:"conflict";i:1;s:9:"bodyprefs";a:2:{s:6:"wanted";s:1:"1";i:1;a:2:{s:4:"type";s:1:"1";s:14:"truncationsize";s:5:"32768";}}s:7:"synckey";s:39:"{4fd981c6-51b0-4274-a3ae-0a69c0a8015f}2";}}}'
        );

        $this->_state = $this->getMockSkipConstructor('Horde_ActiveSync_State_Sql');
        $this->_state->expects($this->once())->method('getSyncCache')->will($this->returnValue($this->_fixture));
    }

    public function testPropertyAccess()
    {
        $cache = new Horde_ActiveSync_SyncCache($this->_state, 'devid', 'userone');
        $this->assertEquals('{4fd981ba-6e58-440c-8d6f-0a69c0a8015f}1', $cache->hierarchy);
        $this->assertEquals(true, $cache->confirmed_synckeys['{4fd981c6-51b0-4274-a3ae-0a69c0a8015f}2']);
    }

    public function testInvalidPropertyAccess()
    {
        $this->setExpectedException('InvalidArgumentException');
        $cache = new Horde_ActiveSync_SyncCache($this->_state, 'devid', 'userone');
        $cache->collections;
    }

    public function testReturnCollections()
    {
        $cache = new Horde_ActiveSync_SyncCache($this->_state, 'devid', 'userone');
        $collections = $cache->getCollections();
        $this->assertEquals('Calendar', $collections['@Calendar@']['class']);
    }

}