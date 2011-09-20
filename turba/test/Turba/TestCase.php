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
 * Basic Turba test case.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
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
class Turba_TestCase
extends PHPUnit_Framework_TestCase
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

        $GLOBALS['conf']['prefs']['driver'] = 'Null';
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
        $setup->makeGlobal(
            array(
                'turba_shares' => 'Horde_Share_Base',
            )
        );
        $setup->getInjector()->setInstance(
            'Horde_Core_Factory_Share',
            new Horde_Test_Stub_Factory(
                $setup->getInjector()->getInstance('Horde_Share_Base')
            )
        );
        $GLOBALS['conf']['storage']['driver'] = 'kolab';
        $GLOBALS['conf']['notepads']['driver'] = 'kolab';
    }

    protected function getKolabDriver()
    {
        $setup = new Horde_Test_Setup();
        self::createBasicTurbaSetup($setup);
        self::createKolabShares($setup);
        list($share, $other_share) = self::_createDefaultShares();

        $GLOBALS['cfgSources'][$share->getName()]['type'] = 'Kolab';
        $GLOBALS['cfgSources'][$share->getName()]['title'] = $share->get('name');
        $GLOBALS['cfgSources'][$share->getName()]['map'] = $this->_getKolabMap();
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
        $share = $GLOBALS['turba_shares']->newShare(
            $owner, strval(new Horde_Support_Randomid()), $name
        );
        $GLOBALS['turba_shares']->addShare($share);
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
}