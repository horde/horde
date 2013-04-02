<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://pear.horde.org/index.php?package=Components
 * @package   Components
 */

/**
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://pear.horde.org/index.php?package=Components
 * @package   Components
 */
class Components_Helper_Composer
{
    /**
     * The output handler.
     *
     * @param Component_Output
     */
    private $_output;

    /**
     * Constructor.
     *
     * @param Component_Output $output The output handler.
     */
    public function __construct(Components_Output $output)
    {
        $this->_output = $output;
    }

    /**
     * Updates the component information in the horde-web repository.
     *
     * @param Components_Component $component The data of this component will
     *                                        be updated.
     * @param array                $options   The set of options for the
     *                                        operation.
     */
    public function generateComposeJson(Components_Component $component,
                                        $options)
    {
        require_once __DIR__ . '/../../Conductor/PEARPackageFilev2.php';
        require_once __DIR__ . '/../../Conductor/Package2XmlToComposer.php';

        $converter = new Package2XmlToComposer($component->getPackageXmlPath());
        $converter->setRepositories(array(
            array('pear', 'http://pear.horde.org')
        ));
        $converter->convert();

        $this->_output->ok(
            'Created composer.json file.'
        );
    }

}
