<?php
/**
 * Base for PHPUnit scenarios.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Share
 */

/**
 * Require our basic test case definition
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Base for PHPUnit scenarios.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Share
 */
class Horde_Kolab_Server_Integration_Scenario extends PHPUnit_Extensions_Story_TestCase
{
    /** The mock environment */
    const ENVIRONMENT_MOCK = 'mock';

    /** The real server environment */
    const ENVIRONMENT_REAL = 'real';

    /**
     * The environments we provide to the test.
     *
     * @var array
     */
    protected $_environments;

    /**
     * Uid of added objects. Should be removed on tearDown.
     *
     * @var array
     */
    public $added;

    /**
     * Handle a "given" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runGiven(&$world, $action, $arguments)
    {
        switch($action) {
        case 'several injectors':
            foreach ($this->getEnvironments() as $environment) {
                $this->prepareInjector($environment);
            }
            break;
        case 'several Kolab servers':
        case 'the test environments':
            $this->initializeEnvironments();
            break;
        case 'an empty Kolab server':
            $world['server'] = $this->prepareKolabServer(self::ENVIRONMENT_MOCK);
            break;
        case 'a basic Kolab server':
            $world['server'] = $this->prepareBasicKolabServer($world);
            break;
        default:
            return $this->notImplemented($action);
        }
    }

    /**
     * Handle a "when" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runWhen(&$world, $action, $arguments)
    {
        switch($action) {
        case 'adding a Kolab server object':
            $world['result']['add'] = $this->addToServers($arguments[0]);
            break;
        case 'adding an invalid Kolab server object':
            try {
                $world['result']['add'] = $this->addToServers($arguments[0]);
            } catch (Horde_Kolab_Server_Exception $e) {
                $world['result']['add'] = $e;
            }
            break;
        case 'adding an object list':
            foreach ($arguments[0] as $object) {
                try {
                    $world['result']['add'][] = $this->addToServers($object);
                } catch (Horde_Kolab_Server_Exception $e) {
                    $world['result']['add'] = $e;
                    return;
                }
            }
            $world['result']['add'] = true;
            break;
        case 'adding a distribution list':
            $world['result']['add'] = $this->addToServers($this->provideDistributionList());
            break;
        case 'listing all users':
            $world['list'] = $this->listObjectsOnServer('Horde_Kolab_Server_Object_Kolab_User');
            break;
        case 'listing all groups':
            $world['list'] = $this->listObjectsOnServer('Horde_Kolab_Server_Object_Kolabgroupofnames');
            break;
        case 'listing all objects of type':
            $world['list'] = $this->listObjectsOnServer($arguments[0]);
            break;
        case 'retrieving a hash list with all objects of type':
            $world['list'] = array();
            foreach ($this->world['injector'] as $injector) {
                $server = $injector->getInstance('Horde_Kolab_Server');
                $world['list'][] = $server->listHash($arguments[0]);
            }
            break;
        default:
            return $this->notImplemented($action);
        }
    }

    /**
     * Handle a "then" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runThen(&$world, $action, $arguments)
    {
        switch($action) {
        case 'the result should be an object of type':
            if (!isset($world['result'])) {
                $this->fail('Did not receive a result!');
            }
            $this->assertRecursiveType($world['result'], $arguments[0]);
            break;
        case 'the result indicates success.':
            if (!isset($world['result'])) {
                $this->fail('Did not receive a result!');
            }
            $this->assertNoError($world['result']);
            break;
        case 'the result should indicate an error with':
            if (!isset($world['result'])) {
                $this->fail('Did not receive a result!');
            }
            foreach ($world['result'] as $result) {
                if ($result instanceOf Horde_Kolab_Server_Exception) {
                    $this->assertEquals($arguments[0], $result->getMessage());
                } else {
                    $this->assertEquals($arguments[0], 'Action succeeded without an error.');
                }
            }
            break;
        case 'the list has a number of entries equal to':
            if ($world['list'] instanceOf Horde_Kolab_Server_Exception) {
                $this->assertEquals('', $world['list']->getMessage());
            } else {
                $this->assertEquals($arguments[0], count($world['list']));
            }
            break;
        case 'the list is an empty array':
            if ($world['list'] instanceOf Horde_Kolab_Server_Exception) {
                $this->assertEquals('', $world['list']->getMessage());
            } else {
                $this->assertEquals(array(array()), $world['list']);
            }
            break;
        case 'the list is an empty array':
            if ($world['list'] instanceOf Horde_Kolab_Server_Exception) {
                $this->assertEquals('', $world['list']->getMessage());
            } else {
                $this->assertEquals(array(), $world['list']);
            }
            break;
        case 'the provided list and the result list match with regard to these attributes':
            if ($world['list'] instanceOf Horde_Kolab_Server_Exception) {
                $this->assertEquals('', $world['list']->getMessage());
            } else {
                $provided_vals = array();
                foreach ($arguments[2] as $provided_element) {
                    if (isset($provided_element[$arguments[0]])) {
                        $provided_vals[] = $provided_element[$arguments[0]];
                    } else {
                        $this->fail(sprintf('The provided element %s does have no value for %s.',
                                            print_r($provided_element, true),
                                            print_r($arguments[0])));
                    }
                }
                $result_vals = array();
                foreach ($world['list'] as $result_set) {
                    foreach ($result_set as $result_element) {
                        if (isset($result_element[$arguments[1]])) {
                            $result_vals[] = $result_element[$arguments[1]];
                        } else {
                            $this->fail(sprintf('The result element %s does have no value for %s.',
                                                print_r($result_element, true),
                                                print_r($arguments[1])));
                        }
                    }
                    $this->assertEquals(array(),
                                        array_diff($provided_vals, $result_vals));
                }
            }
            break;
        case 'each element in the result list has an attribute':
            if ($world['list'] instanceOf Horde_Kolab_Server_Exception) {
                $this->assertEquals('', $world['list']->getMessage());
            } else {
                $result_vals = array();
                foreach ($world['list'] as $result_set) {
                    foreach ($result_set as $result_element) {
                        if (!isset($result_element[$arguments[0]])) {
                            $this->fail(sprintf('The result element %s does have no value for %s.',
                                                print_r($result_element, true),
                                                print_r($arguments[0], true)));
                        }
                    }
                }
            }
            break;
        case 'each element in the result list has an attribute set to a given value':
            if ($world['list'] instanceOf Horde_Kolab_Server_Exception) {
                $this->assertEquals('', $world['list']->getMessage());
            } else {
                $result_vals = array();
                foreach ($world['list'] as $result_set) {
                    foreach ($result_set as $result_element) {
                        if (!isset($result_element[$arguments[0]])) {
                            $this->fail(sprintf('The result element %s does have no value for %s.',
                                                print_r($result_element, true),
                                                print_r($arguments[0], true)));
                        }
                        if ($result_element[$arguments[0]] != $arguments[1]) {
                            $this->fail(sprintf('The result element %s has an unexpected value %s for %s.',
                                                print_r($result_element, true),
                                                print_r($result_element[$arguments[0]], true),
                                                print_r($arguments[0], true)));
                        }
                    }
                }
            }
            break;
        case 'the login was successful':
            $this->assertNoError($world['login']);
            $this->assertTrue($world['login']);
            break;
        case 'the list contains a number of elements equal to':
            $this->assertEquals($arguments[0], count($world['list']));
            break;
        default:
            return $this->notImplemented($action);
        }
    }

    /**
     * Identify the environments we want to run our tests in.
     *
     * @return array The selected environments.
     */
    public function getEnvironments()
    {
        if (empty($this->_environments)) {
            /** The mock environment provides our basic test scenario */
            $this->_environments = array(self::ENVIRONMENT_MOCK);
            $testing = getenv('KOLAB_TEST');
            if (!empty($testing)) {
                $this->_environments[] = array(self::ENVIRONMENT_REAL);
            }
        }
        return $this->_environments;
    }

    /**
     * Specifically set the environments we wish to support.
     *
     * @param array $environments The selected environments.
     *
     * @return NULL
     */
    public function setEnvironments($environments)
    {
        $this->_environments = $environments;
    }

    /**
     * Initialize the environments.
     *
     * @param string $environment The name of the environment.
     *
     * @return NULL
     */
    public function initializeEnvironments()
    {
        foreach ($this->getEnvironments() as $environment) {
            $this->initializeEnvironment($environment);
        }
    }

    /**
     * Prepare an injector for the given environment.
     *
     * @param string $environment The name of the environment.
     *
     * @return NULL
     */
    public function prepareInjector($environment)
    {
        if (!isset($this->world['injector'][$environment])) {
            $this->world['injector'][$environment] = new Horde_Injector(new Horde_Injector_TopLevel());
        }
    }

    /**
     * Prepare the log handler for the given environment.
     *
     * @param string $environment The name of the environment.
     *
     * @return NULL
     */
    public function prepareLogger($environment)
    {
        $logger  = new Horde_Log_Logger();
        $handler = new Horde_Log_Handler_Mock();
        $logger->addHandler($handler);

        $this->world['injector'][$environment]->setInstance('Horde_Log_Logger',
                                                            $logger);
    }

    /**
     * Prepare the server configuration for the given environment.
     *
     * @param string $environment The name of the environment.
     *
     * @return NULL
     */
    public function prepareKolabServerConfiguration($environment)
    {
        switch ($environment) {
        case self::ENVIRONMENT_MOCK:
            /** Prepare a Kolab test server */
            $config = new stdClass;
            $config->driver = 'test';
            $config->params = array(
                'basedn'   => 'dc=example,dc=org',
                'hashtype' => 'plain'
            );
            $this->world['injector'][$environment]->setInstance('Horde_Kolab_Server_Config', $config);
            break;
        default:
            throw new Horde_Exception('Not implemented!');
        }
    }

    /**
     * Prepare the server for the given environment.
     *
     * @param string $environment The name of the environment.
     *
     * @return NULL
     */
    public function prepareKolabServer($environment)
    {
        $this->world['injector'][$environment]->bindFactory('Horde_Kolab_Server_Structure',
                                                            'Horde_Kolab_Server_Factory',
                                                            'getStructure');
        $this->world['injector'][$environment]->bindFactory('Horde_Kolab_Server',
                                                            'Horde_Kolab_Server_Factory',
                                                            'getServer');
    }

    /**
     * Get a server from a specific environment.
     *
     * @param string $environment The name of the environment.
     *
     * @return Horde_Kolab_Server The server.
     */
    public function getKolabServer($environment)
    {
        return $this->world['injector'][$environment]->getInstance('Horde_Kolab_Server');
    }

    /**
     * Initialize the given environment.
     *
     * @param string $environment The name of the environment.
     *
     * @return NULL
     */
    public function initializeEnvironment($environment)
    {
        $this->prepareInjector($environment);
        $this->prepareLogger($environment);
        $this->prepareKolabServerConfiguration($environment);
        $this->prepareKolabServer($environment);
    }

    /**
     * Shortcut to get a Kolab mock server.
     *
     * @return Horde_Kolab_Server The server.
     */
    public function getKolabMockServer()
    {
        $this->initializeEnvironment(self::ENVIRONMENT_MOCK);
        return $this->getKolabServer(self::ENVIRONMENT_MOCK);
    }

    /**
     * Retrieves the available servers. This assumes all environments have been
     * initialied.
     *
     * @return array The list of test servers.
     */
    public function getKolabServers()
    {
        $servers = array();
        foreach ($this->getEnvironments() as $environment) {
            $servers[] = $this->getKolabServer($environment);
        }
        return $servers;
    }

    /**
     * Add an object to a server and remember it for the tear down method.
     *
     * @param Horde_Kolab_Server $server The server to add the object to.
     * @param array              $object  The object data to store.
     *
     * @return Horde_Kolab_Server_Object The resulting object.
     */
    public function addToServer(Horde_Kolab_Server $server, array $object)
    {
        $object = $server->add($object);
        $this->added[] = array($server, $object->getUid());
        return $object;
    }

    /**
     * Add an object to the registered servers.
     *
     * @param array $object The object data to store.
     *
     * @return array An array of objects.
     */
    public function addToServers(array $object)
    {
        $result = array();
        foreach ($this->world['injector'] as $injector) {
            $server = $injector->getInstance('Horde_Kolab_Server');
            $result[] = $this->addToServer($server, $object);
        }
        return $result;
    }

    /**
     * Fill a Kolab Server with test users.
     *
     * @param Horde_Kolab_Server $server The server to fill.
     *
     * @return NULL
     */
    public function addBasicUsersToServer($server)
    {
        $result = $this->addToServer($server, $this->provideBasicUserOne());
        $this->assertNoError($result);
        $result = $this->addToServer($server, $this->provideBasicUserTwo());
        $this->assertNoError($result);
        $result = $this->addToServer($server, $this->provideBasicAddress());
        $this->assertNoError($result);
        $result = $this->addToServer($server, $this->provideBasicAdmin());
        $this->assertNoError($result);
        $result = $this->addToServer($server, $this->provideBasicDomainMaintainer());
        $this->assertNoError($result);
        $result = $this->addToServer($server, $this->provideGroupWithoutMembers());
        $this->assertNoError($result);
        $result = $this->addToServer($server, $this->provideBasicGroupOne());
        $this->assertNoError($result);
        $result = $this->addToServer($server, $this->provideBasicMaintainer());
        $this->assertNoError($result);
        $result = $this->addToServer($server, $this->provideBasicSharedFolder());
        $this->assertNoError($result);
    }

    /**
     * List objects on the registered servers.
     *
     * @param array $type The type of objects to list.
     *
     * @return array An array of objects.
     */
    public function listObjectsOnServer($type)
    {
        $result = array();
        foreach ($this->world['injector'] as $injector) {
            $server = $injector->getInstance('Horde_Kolab_Server');
            $objects = $server->listObjects($type);
            $result[] = $objects;
        }
        return $result;
    }

    /**
     * Return a test user.
     *
     * @return array The test user.
     */
    public function provideBasicUserOne()
    {
        return array('givenName' => 'Gunnar',
                      'sn' => 'Wrobel',
                      'type' => 'Horde_Kolab_Server_Object_Kolab_User',
                      'mail' => 'wrobel@example.org',
                      'uid' => 'wrobel',
                      'userPassword' => 'none',
                      'kolabHomeServer' => 'home.example.org',
                      'kolabImapServer' => 'imap.example.org',
                      'kolabFreeBusyServer' => 'https://fb.example.org/freebusy',
                      'kolabInvitationPolicy' => array('ACT_REJECT_IF_CONFLICTS'),
                      'alias' => array('gunnar@example.org',
                                       'g.wrobel@example.org'),
                );
    }

    /**
     * Return a test user.
     *
     * @return array The test user.
     */
    public function provideBasicUserTwo()
    {
        return array('givenName' => 'Test',
                     'sn' => 'Test',
                     'type' => 'Horde_Kolab_Server_Object_Kolab_User',
                     'mail' => 'test@example.org',
                     'uid' => 'test',
                     'userPassword' => 'test',
                     'kolabHomeServer' => 'home.example.org',
                     'kolabImapServer' => 'home.example.org',
                     'kolabFreeBusyServer' => 'https://fb.example.org/freebusy',
                     'alias' => array('t.test@example.org'),
                     'kolabDelegate' => 'wrobel@example.org',);
    }

    /**
     * Return a test address.
     *
     * @return array The test address.
     */
    public function provideBasicAddress()
    {
        return array('type' => 'Horde_Kolab_Server_Object_Kolab_Address',
                     Horde_Kolab_Server_Object_Kolab_Administrator::ATTRIBUTE_GIVENNAME    => 'Test',
                     'Sn'           => 'Address',
                     Horde_Kolab_Server_Object_Kolab_Administrator::ATTRIBUTE_MAIL         => 'address@example.org',
        );
    }

    /**
     * Return a test administrator.
     *
     * @return array The test administrator.
     */
    public function provideBasicAdmin()
    {
        return array('type' => 'Horde_Kolab_Server_Object_Kolab_Administrator',
                     Horde_Kolab_Server_Object_Kolab_Administrator::ATTRIBUTE_GIVENNAME    => 'The',
                     'Sn'           => 'Administrator',
                     Horde_Kolab_Server_Object_Kolab_Administrator::ATTRIBUTE_SID          => 'admin',
                     'Userpassword' => 'none',
        );
    }

    /**
     * Return a test maintainer.
     *
     * @return array The test maintainer.
     */
    public function provideBasicMaintainer()
    {
        return array('type' => 'Horde_Kolab_Server_Object_Kolab_Maintainer',
                     Horde_Kolab_Server_Object_Kolab_Maintainer::ATTRIBUTE_GIVENNAME    => 'Main',
                     'Sn'           => 'Tainer',
                     Horde_Kolab_Server_Object_Kolab_Maintainer::ATTRIBUTE_SID          => 'maintainer',
                     'Userpassword' => 'none',
        );
    }

    /**
     * Return a test domain maintainer.
     *
     * @return array The test domain maintainer.
     */
    public function provideBasicDomainMaintainer()
    {
        return array('type' => 'Horde_Kolab_Server_Object_Kolab_Domainmaintainer',
                     Horde_Kolab_Server_Object_Kolab_Domainmaintainer::ATTRIBUTE_GIVENNAME    => 'Domain',
                     'Sn'           => 'Maintainer',
                     Horde_Kolab_Server_Object_Kolab_Domainmaintainer::ATTRIBUTE_SID          => 'domainmaintainer',
                     'Userpassword' => 'none',
                     Horde_Kolab_Server_Object_Kolab_Domainmaintainer::ATTRIBUTE_DOMAIN       => array('example.com'),

        );
    }

    /**
     * Return a test shared folder.
     *
     * @return array The test shared folder.
     */
    public function provideBasicSharedFolder()
    {
        return array('type' => 'Horde_Kolab_Server_Object_Kolabsharedfolder',
                     Horde_Kolab_Server_Object_Kolabsharedfolder::ATTRIBUTE_CN         => 'shared@example.org',
                     Horde_Kolab_Server_Object_Kolabsharedfolder::ATTRIBUTE_HOMESERVER => 'example.org',
        );
    }

    /**
     * Provide a set of valid groups.
     *
     * @return array The array of groups.
     */
    public function groupLists()
    {
        $groups = $this->validGroups();
        $result = array();
        foreach ($groups as $group) {
            $result[] = array($group);
        }
        return $result;
    }

    /**
     * Provide a set of valid groups.
     *
     * @return array The array of groups.
     */
    public function validGroups()
    {
        return array(
            array(
                $this->provideGroupWithoutMembers(),
            ),
            array(
                $this->provideBasicGroupOne(),
            ),
            array(
                $this->provideBasicGroupTwo(),
            ),
        );
    }

    /**
     * Return a test group.
     *
     * @return array The test group.
     */
    public function provideGroupWithoutMembers()
    {
        return array('type' => 'Horde_Kolab_Server_Object_Kolabgroupofnames',
                     Horde_Kolab_Server_Object_Kolab_Distlist::ATTRIBUTE_MAIL   => 'empty.group@example.org',
                     Horde_Kolab_Server_Object_Kolab_Distlist::ATTRIBUTE_MEMBER => array());
    }

    /**
     * Return a test group.
     *
     * @return array The test group.
     */
    public function provideBasicGroupOne()
    {
        return array('type' => 'Horde_Kolab_Server_Object_Kolabgroupofnames',
                     Horde_Kolab_Server_Object_Kolab_Distlist::ATTRIBUTE_MAIL   => 'group@example.org',
                     Horde_Kolab_Server_Object_Kolab_Distlist::ATTRIBUTE_MEMBER => array('cn=Test Test,dc=example,dc=org',
                                                                                         'cn=Gunnar Wrobel,dc=example,dc=org')
        );
    }

    /**
     * Return a test group.
     *
     * @return array The test group.
     */
    public function provideBasicGroupTwo()
    {
        return array('type' => 'Horde_Kolab_Server_Object_Kolabgroupofnames',
                     Horde_Kolab_Server_Object_Kolab_Distlist::ATTRIBUTE_MAIL   => 'group2@example.org',
                     Horde_Kolab_Server_Object_Kolab_Distlist::ATTRIBUTE_MEMBER => array('cn=Gunnar Wrobel,dc=example,dc=org')
        );
    }

    public function provideDistributionList()
    {
        return array('type' => 'Horde_Kolab_Server_Object_Kolab_Distlist',
                     Horde_Kolab_Server_Object_Kolab_Distlist::ATTRIBUTE_MAIL   => 'distlist@example.org',
                     Horde_Kolab_Server_Object_Kolab_Distlist::ATTRIBUTE_MEMBER => array('cn=Test Test,dc=example,dc=org',
                                                                                         'cn=Gunnar Wrobel,dc=example,dc=org')
        );
    }

    public function provideInvalidUserWithoutPassword()
    {
        return array('givenName' => 'Test',
                     'sn' => 'Test',
                     'type' => 'Horde_Kolab_Server_Object_Kolab_User',
                     'mail' => 'test@example.org');
    }

    public function provideInvalidUserWithoutGivenName()
    {
        return array('sn' => 'Test',
                     'userPassword' => 'none',
                     'type' => 'Horde_Kolab_Server_Object_Kolab_User',
                     'mail' => 'test@example.org');
    }

    public function provideInvalidUserWithoutLastName()
    {
        return array('givenName' => 'Test',
                     'userPassword' => 'none',
                     'type' => 'Horde_Kolab_Server_Object_Kolab_User',
                     'mail' => 'test@example.org');
    }

    public function provideInvalidUserWithoutMail()
    {
        return array('givenName' => 'Test',
                     'sn' => 'Test',
                     'userPassword' => 'none',
                     'type' => 'Horde_Kolab_Server_Object_Kolab_User');
    }

    public function provideInvalidUsers()
    {
        return array(
            array(
                $this->provideInvalidUserWithoutPassword(),
                'The value for "userPassword" is missing!'
            ),
            array(
                $this->provideInvalidUserWithoutGivenName(),
                'Either the last name or the given name is missing!'
            ),
            array(
                $this->provideInvalidUserWithoutLastName(),
                'Either the last name or the given name is missing!'
            ),
            array(
                $this->provideInvalidUserWithoutMail(),
                'The value for "mail" is missing!'
            ),
        );
    }

    /** @todo: Prefix the stuff below with provide...() */

    public function validUsers()
    {
        return array(
            array(
                $this->provideBasicUserOne(),
            ),
            array(
                $this->provideBasicUserTwo(),
            ),
        );
    }

    public function validAddresses()
    {
        return array(
            array(
                $this->provideBasicAddress(),
            ),
        );
    }

    public function validAdmins()
    {
        return array(
            array(
                $this->provideBasicAdmin(),
            ),
        );
    }

    public function validMaintainers()
    {
        return array(
            array(
                $this->provideBasicMaintainer(),
            )
        );
    }

    public function validDomainMaintainers()
    {
        return array(
            array(
                $this->provideBasicDomainMaintainer(),
            )
        );
    }

    public function validSharedFolders()
    {
        return array(
            array('cn' => 'Shared',
                  'type' => 'Horde_Kolab_Server_Object_Kolabsharedfolder'
            ),
        );
    }


    public function userLists()
    {
        return array(
        );
    }

    public function userListByLetter()
    {
        return array(
        );
    }

    public function userListByAttribute()
    {
        return array(
        );
    }

    public function userAdd()
    {
        return array(
        );
    }

    public function invalidMails()
    {
        return array(
        );
    }

    public function largeList()
    {
        return array(
        );
    }

    protected function fetchByCn($server, $cn)
    {
        $cn_result = $server->uidForCn($cn);
        $this->assertNoError($cn_result);

        $object = $server->fetch($cn_result);
        $this->assertNoError($object);

        return $object;
    }

    /**
     * Ensure that the variable contains no Horde_Kolab_Server_Exception and
     * fail if it does.
     *
     * @param mixed $var The variable to check.
     *
     * @return NULL.
     */
    public function assertNoError($var)
    {
        if (is_array($var)) {
            foreach ($var as $element) {
                $this->assertNoError($element);
            }
        } elseif ($var instanceOf Exception) {
            $this->assertEquals('', $var->getMessage());
        } else if ($var instanceOf PEAR_Error) {
            $this->assertEquals('', $var->getMessage());
        }
    }

    /**
     * Ensure that the variable contains a Horde_Kolab_Server_Exception and fail
     * if it does not. Optionally compare the error message with the provided
     * message and fail if both do not match.
     *
     * @param mixed  $var The variable to check.
     * @param string $msg The expected error message.
     *
     * @return NULL.
     */
    public function assertError($var, $msg = null)
    {
        if (!$var instanceOf PEAR_Error) {
            $this->assertType('Horde_Kolab_Server_Exception', $var);
            if (isset($msg)) {
                $this->assertEquals($msg, $var->getMessage());
            }
        } else {
            if (isset($msg)) {
                $this->assertEquals($msg, $var->getMessage());
            }
        }
    }

    /**
     * Assert that creating a new object operation yields some predictable
     * attribute results.
     *
     * @param Horde_Kolab_Server         $server The server the object resides on.
     * @param array                      $store  The information to save.
     * @param array                      $fetch  The expected results.
     *
     * @return NULL.
     */
    protected function assertAdd(Horde_Kolab_Server $server,
                                 array $store, array $fetch)
    {
        $object = $server->add($store);
        $this->assertNoError($object);

        $this->added[] = array($server, $object->getUid());
        $object = $server->fetch($object->getUid());

        foreach ($fetch as $attribute => $expect) {
            $this->assertEquals($expect, $object->get($attribute));
        }
        return $object;
    }

    /**
     * Test simple attributes.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function assertSimpleAttributes(Horde_Kolab_Server_Object $object,
                                           Horde_Kolab_Server $server, array $list)
    {
        foreach ($list as $item) {
            $this->assertSimpleSequence($object, $server,
                                        $item,
                                        array($item, 'öäü/)(="§%$&§§$\'*', '', array('a', 'b'), '0'),
                                        true);
        }
    }

    /**
     * Test easy attributes.
     *
     * @dataProvider provideServers
     *
     * @return NULL
     */
    public function assertEasyAttributes(Horde_Kolab_Server_Object $object,
                                         Horde_Kolab_Server $server, array $list)
    {
        foreach ($list as $key => $items) {
            $this->assertSimpleSequence($object, $server,
                                        $key,
                                        $items,
                                        true);
        }
    }

    /**
     * Assert that a save() operation yields some predictable attribute results.
     *
     * @param Horde_Kolab_Server_Object $object    The object to work on.
     * @param Horde_Kolab_Server        $server    The server the object resides on.
     * @param string                    $attribute The attribute to work on.
     * @param array                     $sequence  The sequence of values to set and expect.
     *
     * @return NULL.
     */
    protected function assertSimpleSequence(Horde_Kolab_Server_Object $object,
                                            Horde_Kolab_Server $server,
                                            $attribute, array $sequence,
                                            $pop_arrays = false)
    {
        foreach ($sequence as $value) {
            $this->assertStoreFetch($object, $server,
                                    array($attribute => $value),
                                    array($attribute => $value),
                                    $pop_arrays);
        }
    }

    /**
     * Assert that a save() operation yields some predictable attribute results.
     *
     * @param Horde_Kolab_Server_Object  $object The object to work on.
     * @param Horde_Kolab_Server         $server The server the object resides on.
     * @param array                      $store  The information to save.
     * @param array                      $fetch  The expected results.
     *
     * @return NULL.
     */
    protected function assertStoreFetch(Horde_Kolab_Server_Object $object,
                                        Horde_Kolab_Server $server,
                                        array $store, array $fetch,
                                        $pop_arrays = false)
    {
        $result = $object->save($store);
        $this->assertNoError($result);

        $object = $server->fetch($object->getUid());

        foreach ($fetch as $attribute => $expect) {
            $actual = $object->get($attribute, false);
            if ($pop_arrays && is_array($actual) && count($actual) == 1) {
                $actual = array_pop($actual);
            }
            $this->assertEquals($expect,
                                $actual);
        }
    }

    public function assertRecursiveType($results, $type)
    {
        if (is_array($results)) {
            foreach ($results as $result) {
                $this->assertRecursiveType($result, $type);
            }
        } else {
            if ($results instanceOf Exception) {
                $this->assertEquals('', $results->getMessage());
            } else {
                $this->assertType($type, $results);
            }
        }
    }

    /**
     * Setup function.
     *
     * @return NULL.
     */
    protected function setUp()
    {
        $this->added = array();
        $this->markTestIncomplete('Needs to be fixed');
    }

    /**
     * Cleanup function.
     *
     * @return NULL.
     */
    protected function tearDown()
    {
        if (isset($this->added)) {
            $added = array_reverse($this->added);
            foreach ($added as $add) {
                $result = $add[0]->delete($add[1]);
                $this->assertNoError($result);
            }
        }
    }
}
