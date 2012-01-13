<?php
/**
 * Components_Runner_Fetchdocs:: fetches documentation for a component.
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
 * Components_Runner_Fetchdocs:: fetches documentation for a component.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
class Components_Runner_Fetchdocs
{
    /**
     * The configuration for the current job.
     *
     * @var Components_Config
     */
    private $_config;

    /**
     * The output handler.
     *
     * @param Component_Output
     */
    private $_output;

    /**
     * A HTTP client
     *
     * @var Horde_Http_Client
     */
    private $_client;

    /**
     * Constructor.
     *
     * @param Components_Config $config  The configuration for the current job.
     * @param Component_Output  $output  The output handler.
     * @param Horde_Http_Client $client  A HTTP client.
     */
    public function __construct(
        Components_Config $config,
        Components_Output $output,
        Horde_Http_Client $client
    ) {
        $this->_config  = $config;
        $this->_output  = $output;
        $this->_client  = $client;
    }

    public function run()
    {
        $docs_origin = $this->_config->getComponent()->getDocumentOrigin();
        if ($docs_origin === null) {
            $this->_output->fail('The component does not offer a DOCS_ORIGIN file with instructions what should be fetched!');
            return;
        } else {
            $this->_output->info(sprintf('Reading instructions from %s', $docs_origin[0]));
            $options = $this->_config->getOptions();
            $helper = new Components_Helper_DocsOrigin(
                $docs_origin, $this->_client
            );
            if (empty($options['pretend'])) {
                $helper->fetchDocuments($this->_output);
            } else {
                foreach ($helper->getDocuments() as $remote => $local) {
                    $this->_output->info(
                        sprintf(
                            'Would fetch remote %s into %s!',
                            $remote,
                            $docs_origin[1] . '/' . $local
                        )
                    );
                } 
            } 
        }
    }
}
