<?php
/**
 * Basic Turba test case.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Turba
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/turba
 * @license    http://www.horde.org/licenses/apache Apache-like
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Autoload.php';

/**
 * Basic Turba test case.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category   Horde
 * @package    Turba
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/turba
 * @license    http://www.horde.org/licenses/apache Apache-like
 */
class Turba_TestCase extends PHPUnit_Framework_TestCase
{
    protected function getInjector()
    {
        return new Horde_Injector(new Horde_Injector_TopLevel());
    }

    static protected function createBasicTurbaSetup(Horde_Test_Setup $setup)
    {
        $setup->setup(
            array(
                '_PARAMS' => array(
                    'user' => 'test@example.com',
                    'app' => 'turba'
                ),
                'Horde_Prefs' => 'Prefs',
                'Horde_Perms' => 'Perms',
                'Horde_Group' => 'Group',
                'Horde_History' => 'History',
                'Horde_Registry' => 'Registry',
            )
        );
        $setup->makeGlobal(
            array(
                'prefs' => 'Horde_Prefs',
                'registry' => 'Horde_Registry',
                'injector' => 'Horde_Injector',
            )
        );
        $GLOBALS['session'] = new Horde_Session();
        $GLOBALS['conf']['prefs']['driver'] = 'Null';
    }

    static protected function tearDownBasicTurbaSetup()
    {
        unset(
            $GLOBALS['session'],
            $GLOBALS['prefs'],
            $GLOBALS['injector'],
            $GLOBALS['registry'],
            $GLOBALS['conf']
        );
    }

    static protected function createSqlPdoSqlite(Horde_Test_Setup $setup)
    {
        $setup->setup(
            array(
                'Horde_Db_Adapter' => array(
                    'factory' => 'Db',
                    'params' => array(
                        'migrations' => array(
                            'migrationsPath' => __DIR__ . '/../../migration',
                            'schemaTableName' => 'turba_test_schema'
                        )
                    )
                ),
            )
        );
    }

    static protected function createSqlShares(Horde_Test_Setup $setup)
    {
        $setup->getInjector()->setInstance(
            'Horde_Core_Factory_Db',
            new Horde_Test_Stub_Factory(
                $setup->getInjector()->getInstance('Horde_Db_Adapter')
            )
        );
        $setup->setup(
            array(
                'Horde_Share_Base' => 'Share',
            )
        );
        $GLOBALS['cfgSources']['test']['type'] = 'Sql';
        $GLOBALS['cfgSources']['test']['title'] = 'SQL';
        $GLOBALS['cfgSources']['test']['map'] = self::_getSqlMap();
    }

    static protected function createKolabShares(Horde_Test_Setup $setup)
    {
        $setup->setup(
            array(
                'Horde_Kolab_Storage' => array(
                    'factory' => 'KolabStorage',
                    'params' => array(
                        'imapuser' => 'test',
                    )
                ),
                'Horde_Share_Base' => array(
                    'factory' => 'Share',
                    'method' => 'Kolab',
                ),
            )
        );
        $setup->getInjector()->setInstance(
            'Horde_Core_Factory_Share',
            new Horde_Test_Stub_Factory(
                $setup->getInjector()->getInstance('Horde_Share_Base')
            )
        );
        $GLOBALS['cfgSources']['test']['type'] = 'Kolab';
        $GLOBALS['cfgSources']['test']['title'] = 'Kolab';
        $GLOBALS['cfgSources']['test']['map'] = self::_getKolabMap();
    }

    static protected function tearDownShares()
    {
        unset(
            $GLOBALS['cfgSources']
        );
    }

    static protected function getKolabDriver()
    {
        $setup = new Horde_Test_Setup();
        self::createBasicTurbaSetup($setup);
        return self::createKolabDriverWithShares();
    }

    static protected function createKolabDriverWithShares($setup)
    {
        self::createKolabShares($setup);
        list($share, $other_share) = self::_createDefaultShares();

        $GLOBALS['cfgSources'][$share->getName()]['type'] = 'Kolab';
        $GLOBALS['cfgSources'][$share->getName()]['title'] = $share->get('name');
        $GLOBALS['cfgSources'][$share->getName()]['map'] = self::_getKolabMap();
        return $GLOBALS['injector']->getInstance('Turba_Factory_Driver')
            ->create($share->getName());
    }

    static protected function createSqlDriverWithShares($setup)
    {
        self::createSqlShares($setup);
        list($share, $other_share) = self::_createDefaultShares();

        $GLOBALS['cfgSources'][$share->getName()]['type'] = 'Sql';
        $GLOBALS['cfgSources'][$share->getName()]['title'] = $share->get('name');
        $GLOBALS['cfgSources'][$share->getName()]['map'] = self::_getSqlMap();
        $GLOBALS['cfgSources'][$share->getName()]['params']['table'] = 'turba_objects';
        return $GLOBALS['injector']->getInstance('Turba_Factory_Driver')
            ->create($share->getName());
    }

    static protected function _createDefaultShares()
    {
        $share = self::_createShare(
            'Address book of Tester', 'test@example.com'
        );
        $other_share = self::_createShare(
            'Other address book of Tester', 'test@example.com'
        );
        return array($share, $other_share);
    }

    static private function _createShare($name, $owner)
    {
        $turba_shares = $injector->getInstance('Turba_Shares');
        $share = $turba_shares->newShare(
            $owner, strval(new Horde_Support_Randomid()), $name
        );
        $turba_shares->addShare($share);
        return $share;
    }

    static private function _getKolabMap()
    {
        return array(
            '__key' => 'uid',
            '__uid' => 'uid',
            '__type' => '__type',
            '__members' => '__members',
            /* Personal */
            'name' => array(
                'fields' => array('firstname', 'middlenames', 'lastname'),
                'format' => '%s %s %s',
                'parse' => array(
                    array(
                        'fields' => array(
                            'firstname', 'middlenames', 'lastname'
                        ),
                        'format' => '%s %s %s'
                    ),
                    array(
                        'fields' => array('lastname', 'firstname'),
                        'format' => '%s, %s'
                    ),
                    array(
                        'fields' => array('firstname', 'lastname'),
                        'format' => '%s %s'
                    ),
                )
            ),
            'firstname'         => 'given-name',
            'lastname'          => 'last-name',
            'middlenames'       => 'middle-names',
            'namePrefix'        => 'prefix',
            'nameSuffix'        => 'suffix',
            'initials'          => 'initials',
            'nickname'          => 'nick-name',
            'photo'             => 'photo',
            'phototype'         => 'phototype',
            'gender'            => 'gender',
            'birthday'          => 'birthday',
            'spouse'            => 'spouse-name',
            'anniversary'       => 'anniversary',
            'children'          => 'children',
            /* Location */
            'workStreet'        => 'addr-business-street',
            'workCity'          => 'addr-business-locality',
            'workProvince'      => 'addr-business-region',
            'workPostalCode'    => 'addr-business-postal-code',
            'workCountry'       => 'addr-business-country',
            'homeStreet'        => 'addr-home-street',
            'homeCity'          => 'addr-home-locality',
            'homeProvince'      => 'addr-home-region',
            'homePostalCode'    => 'addr-home-postal-code',
            'homeCountry'       => 'addr-home-country',
            /* Communications */
            'emails'            => 'emails',
            'homePhone'         => 'phone-home1',
            'workPhone'         => 'phone-business1',
            'cellPhone'         => 'phone-mobile',
            'fax'               => 'phone-businessfax',
            'instantMessenger'  => 'im-address',
            /* Organization */
            'title'             => 'job-title',
            'role'              => 'profession',
            'company'           => 'organization',
            'department'        => 'department',
            'office'            => 'office-location',
            'manager'           => 'manager-name',
            'assistant'         => 'assistant',
            /* Other */
            'category'          => 'categories',
            'notes'             => 'body',
            'website'           => 'web-page',
            'freebusyUrl'       => 'free-busy-url',
            'language'          => 'language',
            'latitude'          => 'latitude',
            'longitude'         => 'longitude',
            /* Invisible */
            'email'             => 'email',
            'pgpPublicKey'      => 'pgp-publickey',
        );
    }

    static private function _getSqlMap()
    {
        return array(
            '__key' => 'object_id',
            '__owner' => 'owner_id',
            '__type' => 'object_type',
            '__members' => 'object_members',
            '__uid' => 'object_uid',
            'firstname' => 'object_firstname',
            'lastname' => 'object_lastname',
            'middlenames' => 'object_middlenames',
            'namePrefix' => 'object_nameprefix',
            'nameSuffix' => 'object_namesuffix',
            'name' => array('fields' => array('namePrefix', 'firstname',
                                              'middlenames', 'lastname',
                                              'nameSuffix'),
                            'format' => '%s %s %s %s %s',
                            'parse' => array(
                                array('fields' => array('firstname', 'middlenames',
                                                        'lastname'),
                                      'format' => '%s %s %s'),
                                array('fields' => array('firstname', 'lastname'),
                                      'format' => '%s %s'))),
            // This is a shorter version of a "name" composite field which only
            // consists of the first name and last name.
            // 'name' => array('fields' => array('firstname', 'lastname'),
            //                 'format' => '%s %s'),
            'alias' => 'object_alias',
            'birthday' => 'object_bday',
            'anniversary' => 'object_anniversary',
            'spouse' => 'object_spouse',
            'photo' => 'object_photo',
            'phototype' => 'object_phototype',
            'homeStreet' => 'object_homestreet',
            'homePOBox' => 'object_homepob',
            'homeCity' => 'object_homecity',
            'homeProvince' => 'object_homeprovince',
            'homePostalCode' => 'object_homepostalcode',
            'homeCountry' => 'object_homecountry',
            'homeAddress' => array('fields' => array('homeStreet', 'homeCity',
                                                     'homeProvince',
                                                     'homePostalCode'),
                                   'format' => "%s \n %s, %s  %s"),
            'workStreet' => 'object_workstreet',
            'workPOBox' => 'object_workpob',
            'workCity' => 'object_workcity',
            'workProvince' => 'object_workprovince',
            'workPostalCode' => 'object_workpostalcode',
            'workCountry' => 'object_workcountry',
            'workAddress' => array('fields' => array('workStreet', 'workCity',
                                                     'workProvince',
                                                     'workPostalCode'),
                                   'format' => "%s \n %s, %s  %s"),
            'department' => 'object_department',
            'timezone' => 'object_tz',
            'email' => 'object_email',
            'homePhone' => 'object_homephone',
            'homeFax' => 'object_homefax',
            'workPhone' => 'object_workphone',
            'cellPhone' => 'object_cellphone',
            'assistPhone' => 'object_assistantphone',
            'fax' => 'object_fax',
            'pager' => 'object_pager',
            'title' => 'object_title',
            'role' => 'object_role',
            'company' => 'object_company',
            'logo' => 'object_logo',
            'logotype' => 'object_logotype',
            'category' => 'object_category',
            'notes' => 'object_notes',
            'website' => 'object_url',
            'freebusyUrl' => 'object_freebusyurl',
            'pgpPublicKey' => 'object_pgppublickey',
            'smimePublicKey' => 'object_smimepublickey',
            'imaddress' => 'object_imaddress',
            'imaddress2' => 'object_imaddress2',
            'imaddress3' => 'object_imaddress3'
        );
    }
}
