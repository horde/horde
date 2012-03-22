<?php

require_once __DIR__ . '/TestBase.php';

/**
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Turba
 * @subpackage UnitTests
 */
class Turba_ToDo_ViewBrowseTest extends Turba_TestBase {

    function setUp()
    {
        $this->markTestIncomplete('Convert to use Horde_Test.');
        parent::setUp();
        require_once TURBA_BASE . '/lib/View/Browse.php';
        $this->setUpDatabase();
        $this->setUpBrowseView();
    }

    function setUpBrowseView()
    {
        $vars = new Horde_Variables();
        $notification = $GLOBALS['notification'];
        $turbaConf = array();
        $turbaConf['menu']['import_export'] = true;
        $turbaConf['menu']['apps'] = array();
        $turbaConf['client']['addressbook'] = '_test_sql';
        $turbaConf['shares']['source'] = 'foo';
        $turbaConf['comments']['allow'] = true;
        $turbaConf['documents']['type'] = 'horde';
        include TURBA_BASE . '/config/attributes.php';

        $cfgSources = array('_test_sql' => $this->getDriverConfig());
        $this->_pageParams = array('vars' => $vars,
                                   'prefs' => $GLOBALS['prefs'],
                                   'notification' => $notification,
                                   'registry' => $GLOBALS['registry'],
                                   'browse_source_count' => 1,
                                   'browse_source_options' => "My Address Book",
                                   'copymoveSources' => array(),
                                   'addSources' => $cfgSources,
                                   'cfgSources' => $cfgSources,
                                   'attributes' => $attributes,
                                   'turba_shares' => false,
                                   'conf' => $turbaConf,
                                   'source' => '_test_sql',
                                   'browser' => $GLOBALS['browser']);

        // These are referenced explicitly from $GLOBALS, *sigh*
        $GLOBALS['browse_source_count'] = $this->_pageParams['browse_source_count'];
        $GLOBALS['addSources'] = $cfgSources;
        $GLOBALS['copymoveSources'] = array();
        $GLOBALS['cfgSources'] = $cfgSources;

        $this->setPref('addressbooks', json_encode(array('_test_sql')));
    }

    function getPage()
    {
        $this->_pageParams['registry']->pushApp('turba', array('check_perms' => false));
        $this->fakeAuth();
        $page = new Turba_View_Browse($this->_pageParams);

        Horde::startBuffer();
        $page->run();
        $this->_output = Horde::endBuffer();

        if ($push_result) {
            $this->_pageParams['registry']->popApp();
        }

        $this->assertNoUnwantedPattern('/<b>Warning/', $this->_output);
        $this->assertNoUnwantedPattern('/<b>Fatal error/i', $this->_output);

        return $this->_output;
    }

    function setPref($name, $value)
    {
        $prefs = $this->_pageParams['prefs'];
        $this->assertOk($prefs->setValue($name, $value));
        $this->assertEqual($value, $prefs->getValue($name));
    }

    function getPref($name)
    {
        return $this->_pageParams['prefs']->getValue($name);
    }

    function setVar($name, $value)
    {
        $vars = $this->_pageParams['vars'];
        $vars->set($name, $value);
    }

    function assertOutputContainsItems($items, $m = 'assertWantedPattern')
    {
        $fail = false;
        foreach ($items as $item) {
            $pattern = '!>' . preg_quote($item, '!') . '</a>!';
            if (!$this->$m($pattern, $this->_output)) {
                $fail = true;
            }
        }
        if ($fail) {
            print $this->_output;
        }
        return !$fail;
    }

    function assertOutputDoesNotContainItems($items)
    {
        return $this->assertOutputContainsItems($items,
                                                'assertNoUnwantedPattern');
    }

    function test_getting_page_shows_all_contacts_and_groups_from_test_addressbook()
    {
        $this->getPage();
        $this->assertOutputContainsItems(array_merge($this->_sortedByLastname, $this->_groups));
    }

    function test_getting_page_with_sort_parameters_updates_sort_preferences()
    {
        $this->setPref('sortorder', '');
        $this->setVar('sortby', '0');
        $this->setVar('sortdir', '1');
        $this->getPage();
        $this->assertEqual(serialize(array(array('field' => 'lastname', 'ascending' => false))),
                           $this->getPref('sortorder'));
    }

    function test_getting_page_with_show_equals_contacts_will_show_only_contacts()
    {
        $this->setVar('show', 'contacts');
        $this->getPage();
        $this->assertOutputContainsItems($this->_sortedByLastname);
        $this->assertOutputDoesNotContainItems($this->_groups);
    }

    function test_getting_page_with_show_equals_lists_will_show_only_groups()
    {
        $this->setVar('show', 'lists');
        $this->getPage();
        $this->assertOutputDoesNotContainItems($this->_sortedByLastname);
        $this->assertOutputContainsItems($this->_groups);
    }

    function test_browsing_list_shows_list_members_only()
    {
        $groupId = 'ggg';
        $this->setVar('key', $groupId);
        $this->getPage();

        $found = false;
        foreach ($this->_fixtures as $fixture) {
            if ($fixture['object_id'] == $groupId) {
                $found = true;
                $this->assertEqual('Group', $fixture['object_type']);
                $memberIds = unserialize($fixture['object_members']);
            }
        }
        $this->assertTrue($found);

        $inList = array();
        $notInList = array();
        foreach ($this->_fixtures as $fixture) {
            if ($fixture['object_type'] == 'Object') {
                if (in_array($fixture['object_id'], $memberIds)) {
                    $inList[] = $fixture['object_name'];
                } else {
                    $notInList[] = $fixture['object_name'];
                }
            }
        }

        $this->assertFalse(empty($inList));
        $this->assertOutputContainsItems($inList);
        $this->assertFalse(empty($notInList));
        $this->assertOutputDoesNotContainItems($notInList);
    }

}
