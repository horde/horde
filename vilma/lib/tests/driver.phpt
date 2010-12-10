--TEST--
Vilma_Driver_sql::
--FILE--
<?php

echo "Load... ";

define('AUTH_HANDLER', false);
require_once dirname(__FILE__) . '/../../lib/Application.php';
$vilma = Horde_Registry::appInit('vilma');

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
    if (is_a($GLOBALS['unfiltered'], 'PEAR_Error')) {
        printf(_("ERROR(1): %s\n"), $GLOBALS['unfiltered']->getMessage());
        return;
    }

    $filtered_params = $conf['storage']['params'];
    $filtered_params['tables']['domainkey'] = '__FOO';

    $GLOBALS['filtered'] = Vilma_Driver::factory('sql', $filtered_params);
    if (is_a($GLOBALS['filtered'], 'PEAR_Error')) {
        printf(_("ERROR(2): %s\n"), $GLOBALS['filtered']->getMessage());
        return;
    }

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

    $res = $filtered->saveDomain($domain);
    if (is_a($res, 'PEAR_Error')) {
        var_dump($res);
        printf(_("ERROR(1): %s\n"), $res->getMessage());
        return;
    }

    $res = $filtered->getDomainByName('filtered.example.com');
    if (is_a($res, 'PEAR_Error')) {
        printf(_("ERROR(2): %s\n"), $res->getMessage());
        return;
    }

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
    $res = $unfiltered->saveDomain($domain);
    if (is_a($res, 'PEAR_Error')) {
        printf(_("ERROR(5): %s\n"), $res->getMessage());
        return;
    }

    $res = $unfiltered->getDomainByName('unfiltered.example.com');
    if (is_a($res, 'PEAR_Error')) {
        printf(_("ERROR(6): %s\n"), $res->getMessage());
        return;
    }

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
    if (is_a($domain, 'PEAR_Error')) {
        printf(_("ERROR(1): %s\n"), $domain->getMessage());
        return;
    }

    $res = $filtered->deleteDomain($domain['domain_id']);
    if (is_a($res, 'PEAR_Error')) {
        printf(_("ERROR(2): %s\n"), $res->getMessage());
        return;
    }

    $domain = $unfiltered->getDomainByName('unfiltered.example.com');
    if (is_a($domain, 'PEAR_Error')) {
        printf(_("ERROR(3): %s\n"), $domain->getMessage());
        return;
    }

    $res = $unfiltered->deleteDomain($domain['domain_id']);
    if (is_a($res, 'PEAR_Error')) {
        printf(_("ERROR(4): %s\n"), $res->getMessage());
        return;
    }

    echo "ok\n";
}

--EXPECT--
Load... ok
Checking construction... ok
Checking saveDomain()... ok
Checking deleteDomain()... ok
