<?php
/**
 * Base for PHPUnit scenarios.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Base for PHPUnit scenarios.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Test_FreeBusy //extends Horde_Kolab_Test_Storage
{

    /**
     * Prepare the configuration.
     *
     * @return NULL
     */
    public function prepareConfiguration()
    {
        $fh = fopen(HORDE_BASE . '/config/conf.php', 'w');
        $data = $this->getConfiguration();
        $data .= <<<EOD
\$conf['kolab']['ldap']['phpdn'] = null;
\$conf['fb']['use_acls'] = true;
EOD;
        fwrite($fh, "<?php\n" . $data);
        fclose($fh);
    }

    /**
     * Tear down testing
     */
    public function tearDown()
    {
        if (file_exists('/tmp/aclcache.db')) {
            unlink('/tmp/aclcache.db');
        }
        if (file_exists('/tmp/xaclcache.db')) {
            unlink('/tmp/xaclcache.db');
        }
        if (file_exists('/tmp/example^org')) {
            $this->unlinkDir('/tmp/example^org');
        }
    }

    function unlinkDir($dir)
    {
        if(!$dh = @opendir($dir)) {
            return;
        }
        while (false !== ($obj = readdir($dh))) {
            if($obj == '.' || $obj == '..') {
                continue;
            }
            if (!@unlink($dir . '/' . $obj)) {
                $this->unlinkDir($dir . '/' . $obj);
            }
        }
        closedir($dh);
        @rmdir($dir);

        return;
    }

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
        default:
            return parent::runGiven($world, $action, $arguments);
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
        case 'adding an event to a folder':
            $world['result']['add_event'][] = $this->addEvent($arguments[0],
                                                              $arguments[1]);
            break;
        case 'triggering the folder':
            include_once 'Horde/Kolab/FreeBusy.php';

            $_GET['folder']   = $arguments[0];
            $_GET['extended'] = '1';

            $fb = new Horde_Kolab_FreeBusy();

            $world['result']['trigger'] = $fb->trigger();

            break;
        case 'fetching the free/busy information for':
            include_once 'Horde/Kolab/FreeBusy.php';

            $_GET['uid']   = $arguments[0];
            $_GET['extended'] = '1';

            $fb = new Horde_Kolab_FreeBusy();

            $world['result']['fetch'] = $fb->fetch();

            break;
        default:
            return parent::runWhen($world, $action, $arguments);
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
        case 'the fetch result should contain a free/busy time with summary':
            $this->assertTrue($this->freeBusyContainsSummary($world['result']['fetch']->_data['fb']->findComponent('vfreebusy'),
                                                             $arguments[0]));
            break;
        case 'the fetch result should not contain a free/busy time with summary':
            $this->assertFalse($this->freeBusyContainsSummary($world['result']['fetch']->_data['fb']->findComponent('vfreebusy'),
                                                             $arguments[0]));
            break;
        default:
            return parent::runThen($world, $action, $arguments);
        }
    }

    public function freeBusyContainsSummary($vfb, $summary)
    {
        $params = $vfb->getExtraParams();
        $present = false;
        foreach ($params as $event) {
            if (isset($event['X-SUMMARY'])
                && base64_decode($event['X-SUMMARY']) == $summary) {
                $present = true;
            }
        }
        return $present;
    }

    /**
     * Add an event.
     *
     * @return NULL
     */
    public function addEvent($event, $folder)
    {
        include_once 'Horde/Kolab/Storage.php';

        $folder = Kolab_Storage::getShare($folder, 'event');
        $this->assertNoError($folder);
        $data   = Kolab_Storage::getData($folder, 'event', 1);
        $this->assertNoError($data);
        /* Add the event */
        $result = $data->save($event);
        $this->assertNoError($result);
        return $result;
    }

}
