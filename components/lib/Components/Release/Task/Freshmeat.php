<?php
/**
 * Components_Release_Task_Freshmeat:: adds the new release to freshmeat.net.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Release_Task_Freshmeat:: adds the new release to freshmeat.net.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Release_Task_Freshmeat
extends Components_Release_Task_Base
{
    /**
     * Validate the preconditions required for this release task.
     *
     * @param array $options Additional options.
     *
     * @return array An empty array if all preconditions are met and a list of
     *               error messages otherwise.
     */
    public function validate($options)
    {
        $errors = array();
        if (!$this->getNotes()->hasFreshmeat()) {
            $errors[] = 'No freshmeat.net information available. The new version will not be added there!';
        }
        if (empty($options['fm_token'])) {
            $errors[] = 'The "fm_token" option has no value. Who is updating freshmeat.net?';
        }
        if (!class_exists('Horde_Release_Freshmeat')) {
            $errors[] = 'The Horde_Release package is missing (specifically the class Horde_Release_Freshmeat)!';
        }
        return $errors;
    }

    /**
     * Return the handler for bugs.horde.org.
     *
     * @param array $options Additional options.
     *
     * @return NULL
     */
    public function _getFreshmeat($options)
    {
        if (!isset($options['fm_token'])) {
            throw new Components_Exception('Missing credentials!');
        }
        return new Horde_Release_Freshmeat(
            $options['fm_token'],
            $this->getNotes()->getFmProject()
        );
    }

    /**
     * Run the task.
     *
     * @param array &$options Additional options.
     *
     * @return NULL
     */
    public function run(&$options)
    {
        if (!$this->getNotes()->hasFreshmeat()) {
            $this->getOutput()->warn(
                'No freshmeat.net information available. The new version will not be added there!'
            );
            return;
        }

        $version = Components_Helper_Version::pearToHordeWithBranch(
            $this->getComponent()->getVersion(),
            $this->getNotes()->getBranch()
        );

        $publish_data = array(
            'version' => $version,
            'changelog' => $this->getNotes()->getFmChanges(),
            'tag_list' => $this->getNotes()->getFocusList()
        );

        $link_data = array();
        $cl = $this->getComponent()->getChangelog(
            new Components_Helper_ChangeLog($this->getOutput())
        );
        if ($cl !== '') {
            $link_data[] = array(
                'label' => 'Changelog',
                'location' => $cl
            );
        }

        if (!$this->getTasks()->pretend()) {
            $fm = $this->_getFreshmeat($options);
            $fm->publish($publish_data);
            $fm->updateLinks($link_data);
        } else {
            $info = 'FRESHMEAT

Release data
------------

';
            foreach ($publish_data as $key => $value) {
                if (is_array($value)) {
                    $string_value = join(',', $value);
                } else {
                    $string_value = (string) $value;
                }
                $info .= $key . ': ' . $string_value . "\n";
            }
            $info .= '
Links
-----

';
            foreach ($link_data as $key => $value) {
                $info .= $key . ': ' . $value['label'] . ' => ' . $value['location'] . "\n";
            }

            $this->getOutput()->info($info);
        }
    }
}