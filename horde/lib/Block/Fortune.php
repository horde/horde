<?php
/**
 */
class Horde_Block_Fortune extends Horde_Block
{
    /**
     */
    public $updateable = true;

    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        $this->enabled = (isset($GLOBALS['conf']['fortune']['exec_path']) && is_executable($GLOBALS['conf']['fortune']['exec_path']));
    }

    /**
     */
    public function getName()
    {
        return _("Random Fortune");
    }

    /**
     */
    protected function _title()
    {
        return _("Fortune");
    }

    /**
     */
    protected function _params()
    {
        global $conf;

        $descriptions = array(
            'art' => _("Art"),
            'ascii-art' => _("Ascii Art"),
            'bofh-excuses' => _("BOFH Excuses"),
            'computers' => _("Computers"),
            'cookie' => _("Cookie"),
            'definitions' => _("Definitions"),
            'drugs' => _("Drugs"),
            'education' => _("Education"),
            'ethnic' => _("Ethnic"),
            'food' => _("Food"),
            'fortunes' => _("Fortunes"),
            'fortunes2' => _("Fortunes 2"),
            'goedel' => _("Goedel"),
            'humorists' => _("Humorists"),
            'kernelnewbies' => _("Kernel Newbies"),
            'kids' => _("Kids"),
            'law' => _("Law"),
            'limerick' => _("Limerick"),
            'linuxcookie' => _("Linux Cookie"),
            'literature' => _("Literature"),
            'love' => _("Love"),
            'magic' => _("Magic"),
            'medicine' => _("Medicine"),
            'miscellaneous' => _("Miscellaneous"),
            'news' => _("News"),
            'osfortune' => _("Operating System"),
            'people' => _("People"),
            'pets' => _("Pets"),
            'platitudes' => _("Platitudes"),
            'politics' => _("Politics"),
            'riddles' => _("Riddles"),
            'science' => _("Science"),
            'songs-poems' => _("Songs & Poems"),
            'sports' => _("Sports"),
            'startrek' => _("Star Trek"),
            'translate-me' => _("Translations"),
            'wisdom' => _("Wisdom"),
            'work' => _("Work"),
            'zippy' => _("Zippy")
        );

        $values = array();

        exec($conf['fortune']['exec_path'] . ' -f 2>&1', $output, $status);
        if (!$status) {
            for ($i = 1, $ocnt = count($output); $i < $ocnt; ++$i) {
                $fortune = substr($output[$i], strrpos($output[$i], ' ') + 1);
                $values[$fortune] = isset($descriptions[$fortune])
                    ? $descriptions[$fortune]
                    : $fortune;
            }
        }

        if (empty($values)) {
            $values = $descriptions;
        }

        asort($values);
        $values = array_merge(array('' => _("All")), $values);

        return array(
            'offend' => array(
                'type' => 'enum',
                'name' => _("Offense filter"),
                'default' => '',
                'values' => array(
                    '' => _("No offensive fortunes"),
                    ' -o' => _("Only offensive fortunes"),
                    ' -a' => _("Both")
                )
            ),
            'fortune' => array(
                'type' => 'multienum',
                'name' => _("Fortune type"),
                'default' => array(''),
                'values' => $values
            )
        );
    }

    /**
     */
    protected function _content()
    {
        global $conf;

        $cmdLine = $conf['fortune']['exec_path']
            . $this->_params['offend']
            . ' ' . implode(' ', $this->_params['fortune']);

        return '<span class="fixed"><small>'
            . nl2br($GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter(shell_exec($cmdLine), array('space2html'), array(array('encode' => true))))
            . '</small></span>';
    }

}
