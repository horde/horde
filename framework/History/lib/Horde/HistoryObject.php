<?php
/**
 * Class for presenting Horde_History information.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package History
 */
class Horde_HistoryObject
{
    /**
     * TODO
     */
    public $uid;

    /**
     * TODO
     */
    public $data = array();

    /**
     * Constructor.
     *
     * TODO
     */
    public function __construct($uid, $data = array())
    {
        $this->uid = $uid;

        if (!$data) {
            return;
        }

        reset($data);
        while (list(,$row) = each($data)) {
            $history = array(
                'action' => $row['history_action'],
                'desc' => $row['history_desc'],
                'who' => $row['history_who'],
                'id' => $row['history_id'],
                'ts' => $row['history_ts']
            );

            if ($row['history_extra']) {
                $extra = @unserialize($row['history_extra']);
                if ($extra) {
                    $history = array_merge($history, $extra);
                }
            }
            $this->data[] = $history;
        }
    }

    public function getData()
    {
        return $this->data;
    }

}
