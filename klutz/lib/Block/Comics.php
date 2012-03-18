<?php

$block_name = _("Comics");

/**
 * @package Horde_Block
 */
class Horde_Block_Klutz_Comics extends Horde_Core_Block
{
    /**
     * Whether this block has changing content.
     */
    var $updateable = false;

    var $_app = 'klutz';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        return Horde::link(Horde::url('comics.php')) .
            $GLOBALS['registry']->get('name') . '</a>';
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        require_once __DIR__ . '/../base.php';
        global $prefs, $klutz, $klutz_driver;

        $showall = $prefs->getValue('summ_showall');
        $date = time();

        // Get the list of comics to display.
        $comics = explode("\t", $prefs->getValue('viewcomics'));
        if (count($comics) == 1 && empty($comics[0])) {
            $comics = null;
        }

        $comicstoday = $klutz->listEnabled($comics, $date);

        if ($showall) {
            $summary = '';
            foreach ($comicstoday as $index) {
                $name = $klutz->getProperty($index, 'name');
                $author = $klutz->getProperty($index, 'author');
                if ($klutz_driver->imageExists($index, $date)) {
                    $size = $klutz_driver->imageSize($index, $date);
                    $url = Horde_Util::addParameter(Horde::url('comics.php'), array('date' => $date, 'index' => $index));
                    $img = Horde::img(Horde_Util::addParameter($url, 'actionID', 'image'), sprintf("%s by %s", $name, $author), $size, '');
                    $link = Horde::link(Horde_Util::addParameter($url, 'actionID', 'comic'), sprintf("%s by %s", $name, $author));
                    $summary .= '<p>' . $link . $img . '</a></p>';
                }
            }
        } else {
            $this->updateable = true;
            // Pick a comic from the list and make sure it exists.
            do {
                // Make sure we actually have some comics to choose
                // from.
                if (!count($comicstoday)) {
                    return _("Could not find any comics to display.");
                }

                // Pick a comic by random and remove it from the list.
                $i = rand(0, count($comicstoday) - 1);
                $tmp = array_splice($comicstoday, $i, 1);
                $index = array_shift($tmp);
            } while ($klutz_driver->imageExists($index, $date) === false);

            $name = $klutz->getProperty($index, 'name');
            $author = $klutz->getProperty($index, 'author');
            $size = $klutz_driver->imageSize($index, $date);
            $url = Horde_Util::addParameter(Horde::url('comics.php'), array('date' => $date, 'index' => $index));
            $img = Horde::img(Horde_Util::addParameter($url, 'actionID', 'image'), sprintf("%s by %s", $name, $author), $size, '');
            $link = Horde::link(Horde_Util::addParameter($url, 'actionID', 'comic'), sprintf("%s by %s", $name, $author));
            $summary = '<p class="text">' . $link . $name . ' by ' . $author . '</a></p>' .
                '<p>' . $link . $img . '</a></p>';
        }

        return $summary;
    }
}
