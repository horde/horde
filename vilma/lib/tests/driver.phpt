--TEST--
Vilma_Driver_sql::
--FILE--
<?php

echo "Load... ";

define('AUTH_HANDLER', false);
require_once dirname(__FILE__) . '/../../lib/Application.php';
$vilma = Horde_Registry::appInit('vilma', array('authentication' => 'none'));

echo "ok\n";

checkConstruction();

/* Delete our test domains in case we bombed on an earlier trial. */
ob_start();
checkDeleteDomain();
ob_end_clean();

checkSaveDomain();
checkDeleteDomain();

function checkConstruction()
{
    global $conf;

    echo "Checking construction... ";

    $unfiltered_params = $conf['storage']['params'];
    if (isset($unfiltered_params['tables']['domainkey'])) {
        unset($unfiltered_params['tables']['domainkey']);
    }

    $GLOBALS['unfiltered'] = Vilma_Driver::factory('sql', $unfiltered_params);

    $filtered_params = $conf['storage']['params'];
    $filtered_params['tables']['domainkey'] = '__FOO';

    $GLOBALS['filtered'] = Vilma_Driver::factory('sql', $filtered_params);

    echo "ok\n";
}

function checkSaveDomain()
{
    global $filtered, $unfiltered;

    echo "Checking saveDomain()... ";

    $domain = array('domain_name'       => 'filtered.example.com',
                    'domain_transport'  => 'cyrus',
                    'domain_admin'      => 'test@filtered.example.com',
                    'domain_max_users'  => 15,
                    'domain_quota'      => 0);

    $filtered->saveDomain($domain);
    $res = $filtered->getDomainByName('filtered.example.com');

    if ($res['domain_name'] != 'filtered.example.com') {
        echo _("ERROR(3): got wrong domain.\n");
        return;
    }
    if ($res['domain_transport'] != $domain['domain_transport'] ||
        $res['domain_admin'] != $domain['domain_admin'] ||
        $res['domain_max_users'] != $domain['domain_max_users'] ||
        $res['domain_quota'] != $domain['domain_quota']) {
        echo _("ERROR(4): got some wrong info.\n");
        return;
    }

    $domain['domain_name'] = 'unfiltered.example.com';
    $unfiltered->saveDomain($domain);
    $res = $unfiltered->getDomainByName('unfiltered.example.com');

    if ($res['domain_name'] != 'unfiltered.example.com') {
        echo _("ERROR(7): got wrong domain.\n");
        return;
    }
    if ($res['domain_transport'] != $domain['domain_transport'] ||
        $res['domain_admin'] != $domain['domain_admin'] ||
        $res['domain_max_users'] != $domain['domain_max_users'] ||
        $res['domain_quota'] != $domain['domain_quota']) {
        echo _("ERROR(8): got some wrong info.\n");
        return;
    }

    echo "ok\n";
}

function checkDeleteDomain()
{
    global $filtered, $unfiltered;

    echo "Checking deleteDomain()... ";

    $domain = $filtered->getDomainByName('filtered.example.com');
    $filtered->deleteDomain($domain['domain_id']);

    $domain = $unfiltered->getDomainByName('unfiltered.example.com');
    $unfiltered->deleteDomain($domain['domain_id']);

    echo "ok\n";
}

--EXPECT--
Load... ok
Checking construction... ok
Checking saveDomain()... ok
Checking deleteDomain()... ok
