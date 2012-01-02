<?php
/**
 * Command line tool for pushing content to social networks.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Push
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/components/Horde_Push
 */

/**
 * Command line tool for pushing content to social networks.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Push
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/components/Horde_Push
 */
class Horde_Push_Cli
{
    /**
     * The main entry point for the application.
     *
     * @param array $parameters A list of named configuration parameters.
     */
    static public function main(array $parameters = array())
    {
        $parser = new Horde_Argv_Parser(
            array('usage' => '%prog [OPTIONS] [SOURCE://ID]')
        );
        $parser->addOptions(
            array(
                new Horde_Argv_Option(
                    '-c',
                    '--config',
                    array(
                        'action' => 'store',
                        'help'   => Horde_Push_Translation::t('Path to the configuration file.'),
                    )
                ),
                new Horde_Argv_Option(
                    '-S',
                    '--summary',
                    array(
                        'action' => 'store',
                        'help'   => Horde_Push_Translation::t('A summary replacing the value provided by the source.'),
                    )
                ),
                new Horde_Argv_Option(
                    '-R',
                    '--recipients',
                    array(
                        'action' => 'store',
                        'help'   => Horde_Push_Translation::t('A comma delimited list of recipients.'),
                    )
                ),
                new Horde_Argv_Option(
                    '-T',
                    '--tags',
                    array(
                        'action' => 'store',
                        'help'   => Horde_Push_Translation::t('A comma delimited list of tags.'),
                    )
                ),
                new Horde_Argv_Option(
                    '-L',
                    '--links',
                    array(
                        'action' => 'store',
                        'help'   => Horde_Push_Translation::t('A comma delimited list of links.'),
                    )
                ),
                new Horde_Argv_Option(
                    '-p',
                    '--pretend',
                    array(
                        'action' => 'store_true',
                        'help'   => Horde_Push_Translation::t('Do not push the content but display what would be done.'),
                    )
                ),
            )
        );
        list($options, $arguments) = $parser->parseArgs();

        global $conf;

        if (isset($options['config'])) {
            if (!file_exists($options['config'])) {
                throw new Horde_Push_Exception(
                    sprintf(
                        'The specified config file %s does not exist!',
                        $options['config']
                    )
                );
            }
            include $options['config'];
        } else {
            $conf = array('recipients' => array('mock'));
        }

        if (empty($arguments)) {
            $arguments = explode(' ', trim(file_get_contents('php://stdin')));
        }
        $push_factory = new Horde_Push_Factory_Push();
        $pushes = $push_factory->create($arguments, $options, $conf);
        $fail = false;
        foreach ($pushes as $push) {
            if (isset($options['summary'])) {
                $push->setSummary($options['summary']);
            }
            if (isset($options['tags'])) {
                foreach (explode(',', $options['tags']) as $tag) {
                    $push->addTag($tag);
                }
            }
            if (isset($options['links'])) {
                foreach (explode(',', $options['links']) as $reference) {
                    $push->addReference($reference);
                }
            }

            $recipient_factory = new Horde_Push_Factory_Recipients();
            $recipients = $recipient_factory->create($options, $conf);

            foreach ($recipients as $recipient) {
                $push->addRecipient($recipient);
            }

            $results = $push->push(array('pretend' => !empty($options['pretend'])));

            $cli = Horde_Cli::init();
            foreach ($results as $result) {
                if ($result instanceOf Exception) {
                    $cli->message($result->getMessage(), 'cli.error');
                    $fail = true;
                } else {
                    $cli->message((string)$result, 'cli.success');
                }
            }
        }
        if ($fail === true) {
            $status = 1;
        } else {
            $status = 0;
        }
        if (empty($parameters['no_exit'])) {
            exit($status);
        } else {
            return $status;
        }
    }
}