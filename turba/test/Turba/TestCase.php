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

    protected function getKolabDriver()
    {
        $GLOBALS['injector'] = $this->getInjector();
        $kolab_factory = new Horde_Kolab_Storage_Factory(
            array(
                'driver' => 'mock',
                'queryset' => array('list' => array('queryset' => 'horde')),
                'params' => array(
                    'username' => 'test@example.com',
                    'host' => 'localhost',
                    'port' => 143,
                    'data' => array(
                        'user/test' => array(
                            'permissions' => array('anyone' => 'alrid')
                        )
                    )
                )
            )
        );
        $this->storage = $kolab_factory->create();
        $GLOBALS['injector']->setInstance('Horde_Kolab_Storage', $this->storage);
        $GLOBALS['turba_shares'] = new Horde_Share_Kolab(
            'turba', 'test@example.com', new Horde_Perms_Null(), new Horde_Group_Mock()
        );
        $GLOBALS['turba_shares']->setStorage($this->storage);
        $this->share = $GLOBALS['turba_shares']->newShare(
            'test@example.com',
            strval(new Horde_Support_Randomid()),
            "Addressbook of Tester"
        );
        $GLOBALS['turba_shares']->addShare($this->share);
        $this->other_share = $GLOBALS['turba_shares']->newShare(
            'test@example.com',
            strval(new Horde_Support_Randomid()),
            "Other addressbook of Tester"
        );
        $GLOBALS['turba_shares']->addShare($this->other_share);
        $GLOBALS['cfgSources'][$this->share->getName()]['type'] = 'Kolab';
        $GLOBALS['cfgSources'][$this->share->getName()]['title'] = $this->share->get('name');
        $GLOBALS['cfgSources'][$this->share->getName()]['map'] = $this->_getKolabMap();
        $factory = new Turba_Factory_Driver($GLOBALS['injector']);
        return $factory->create($this->share->getName());
    }

    private function _getKolabMap()
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