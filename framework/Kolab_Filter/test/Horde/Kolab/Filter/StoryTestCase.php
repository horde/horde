<?php
/**
 * Base for scenario based testing of this package.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Filter
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Filter
 */

/**
 * Base for scenario based testing of this package.
 *
 * Copyright 2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Filter
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Filter
 */
class Horde_Kolab_Filter_StoryTestCase
extends PHPUnit_Extensions_Story_TestCase
{
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
        case 'an incoming message on host':
            $world['hostname'] = $arguments[0];
            $world['type'] = 'Incoming';
            break;
        case 'the SMTP sender address is':
            $world['sender'] = $arguments[0];
            break;
        case 'the SMTP recipient address is':
            $world['recipient'] = $arguments[0];
            break;
        case 'the client address is':
            $world['client'] = $arguments[0];
            break;
        case 'the hostname is':
            $world['hostname'] = $arguments[0];
            break;
        case 'the unmodified message content is':
            $world['infile'] = $arguments[0];
            $world['fp']     = fopen($world['infile'], 'r');
            break;
        case 'the modified message template is':
            $world['infile'] = $arguments[0];
            $world['fp']     = fopen($world['infile'], 'r');
            stream_filter_register(
                'addresses', 'Horde_Kolab_Filter_Helper_AddressFilter'
            );
            stream_filter_append(
                $world['fp'],
                'addresses',
                STREAM_FILTER_READ,
                array(
                    'recipient' => $world['recipient'],
                    'sender'    => $world['sender']
                )
            );
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
        case 'handling the message':
            global $conf;
            $conf['server']['mock'] = true;
            //@todo: Fix guid => dn here
            $conf['server']['data'] = array('dn=example' => array('dn' => 'dn=example', 'data' => array('mail' => array('me@example.org'), 'kolabHomeServer' => array('localhost'), 'objectClass' => array('kolabInetOrgPerson'), 'guid' => 'dn=example')));
            $_SERVER['argv'] = $this->_prepareArguments($world);
            $filter = new Horde_Kolab_Filter();
            ob_start();
            $result = $filter->main($world['type'], $world['fp'], 'echo');
            $world['output'] = ob_get_contents();
            ob_end_clean();
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
        case 'the result will be the same as the content in':
            $out = file_get_contents($arguments[0]);
            $this->_cleanAndCompareOutput($out, $world['output']);
            break;
        default:
            return $this->notImplemented($action);
        }
    }

    private function _prepareArguments(&$world)
    {
        $recipient = isset($world['recipient']) ? $world['recipient'] : '';
        $sender    = isset($world['sender']) ? $world['sender'] : '';
        $user      = isset($world['user']) ? $world['user'] : '';
        $hostname  = isset($world['hostname']) ? $world['hostname'] : '';
        $client    = isset($world['client']) ? $world['client'] : '';
        return array(
            $_SERVER['argv'][0],
            '--sender=' . $sender,
            '--recipient=' . $recipient,
            '--user=' . $user,
            '--host=' . $hostname,
            '--client=' . $client
        );

    }

    private function _cleanAndCompareOutput($received, $expected)
    {
        $replace = array(
            '/^Received:.*$/m' => '',
            '/^Date:.*$/m' => '',
            '/DTSTAMP:.*$/m' => '',
            '/^--+=.*$/m' => '----',
            '/^Message-ID.*$/m' => '----',
            '/boundary=.*$/m' => '----',
            '/\s/' => '',
        );
        foreach ($replace as $pattern => $replacement) {
            $received = preg_replace($pattern, $replacement, $received);
            $expected = preg_replace($pattern, $replacement, $expected);
        }

        $this->assertEquals($received, $expected);
    }
}