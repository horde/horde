<?php
/**
 * A view for regenerating the Kolab Free/Busy cache.
 *
 * Copyright 2009 Klarälvdalens Datakonsult AB
 *
 * @author  Gunnar Wrobel <p@rdus.de>
 * @package Kolab_FreeBusy
 */

class Horde_Kolab_FreeBusy_Report {

    var $_break = '<br/>';

    var $_errors = array();

    function Horde_Kolab_FreeBusy_Report()
    {
        if (PHP_SAPI == 'cli') {
            $this->_break = "\n";

            /* Display errors if we are working on the command line */
            ini_set('display_errors', 1);

            /** Don't report notices */
            error_reporting(E_ALL & ~E_NOTICE);
        }
    }

    function start()
    {
        echo Horde_Kolab_FreeBusy_Translation::t("Starting to regenerate the free/busy cache...");
        $this->linebreak(2);
    }

    function success($calendar)
    {
        echo sprintf(Horde_Kolab_FreeBusy_Translation::t("Successfully regenerated calendar \"%s\"!"),
                     $calendar);
        $this->linebreak(1);
    }

    function failure($calendar, $error)
    {
        $this->_errors[] = sprintf(Horde_Kolab_FreeBusy_Translation::t("Failed regenerating calendar %s: %s"),
                                   $calendar, $error->getMessage());
    }

    function stop()
    {
        if (!empty($this->_errors)) {
            $this->linebreak(1);
            echo Horde_Kolab_FreeBusy_Translation::t("Errors:");
            $this->linebreak(1);
            foreach ($this->_errors as $error) {
                echo $error;
            }
            return false;
        } else {
            $this->linebreak(1);
            echo Horde_Kolab_FreeBusy_Translation::t("Successfully regenerated all calendar caches!");
            $this->linebreak(1);
            return true;
        }
    }

    function linebreak($count = 1)
    {
        for ($i = 0; $i < $count; $i++) {
            echo $this->_break;
        }
    }
}
