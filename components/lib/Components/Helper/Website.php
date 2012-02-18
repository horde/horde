<?php
/**
 * Components_Helper_Website:: is a helper for a horde-web git repository
 * checkout.
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
 * Components_Helper_Website:: is a helper for a horde-web git repository
 * checkout.
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
class Components_Helper_Website
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
     *
     * @return NULL
     */
    public function update(Components_Component $component, $options)
    {
        if (empty($options['destination'])) {
            throw new Components_Exception('"destination" MUST be set for this action!');
        } else {
            $destination = $options['destination'];
        }

        $tmp_dir = Horde_Util::createTempDir();
        $archive = $component->placeArchive(
            $tmp_dir, array('logger' => $this->_output)
        );
        if (!$archive[0]) {
            throw new Components_Exception('Failed retrieving the component archive!');
        }
        system('cd ' . $tmp_dir . ' && tar zxpf ' . $archive[0]);

        $source = preg_replace('/\.tgz$/', '', $archive[0]);
        $doc_files = $this->_identifyDocFiles($source . '/doc');
        $doc_files = array_merge(
            $doc_files,
            $this->_identifyDocFiles($source . '/docs')
        );
        if (file_exists($source . '/README')) {
            $doc_files[$source . '/README'] = 'README';
        }

        if (preg_match('/^Horde_/', $component->getName())) {
            $view_root = $destination . '/app/views/Library/libraries/' .
                $component->getName() . '/docs';
        } else {
            $view_root = $destination . '/app/views/App/apps/' .
                $component->getName() . '/docs';
        }
        if (!file_exists($view_root)) {
            mkdir($view_root, 0777, true);
        }

        $docs = '<h3>Documentation</h3>

<p>These are the documentation files as distributed with the latest component\'s release tarball.</p>

<ul>
';
        foreach ($doc_files as $path => $filename) {
            if (preg_match('/^Horde_/', $component->getName())) {
                $docs .= '<li><a href="<?php echo $this->urlWriter->urlFor(array(\'controller\' => \'library\', \'action\' => \'docs\', \'library\' => \'' . $component->getName() . '\', \'file\' => \'' . $filename. '\')); ?>">' . $filename. '</a></li>' . "\n";
            } else {
                $docs .= '<li><a href="<?php echo $this->urlWriter->urlFor(array(\'controller\' => \'apps\', \'action\' => \'docs\', \'app\' => \'' . $component->getName() . '\', \'file\' => \'' . $filename. '\')); ?>">' . $filename. '</a></li>' . "\n";
            }

            if ($filename == 'CHANGES') {
                $out = '<h3>Changes by Release</h3><pre>';
                $orig = file_get_contents($path);
                $orig = preg_replace('/</', '&lt;', $orig);
                $orig = preg_replace(
                    ';pear\s*(bug|request)\s*#([[:digit:]]*);',
                    '<a href="http://pear.php.net/bugs/bug.php?id=\2">\0</a>;',
                    $orig
                );
                $orig = preg_replace(
                    ';(,\s*|\()((bug|request)\s*#(\d*));i',
                    '\1<a href="http://bugs.horde.org/ticket/\4">\2</a>',
                    $orig
                );
                $out .= $orig . '</pre>';
            } else if ($filename == 'RELEASE_NOTES') {
                $out = "<h3>Release notes for the latest release</h3><pre>\n";
                include $path;
                $out .= $this->notes['ml']['changes'];
                $out .= "</pre>\n";
            } else {
                $descriptorspec = array(
                    array('pipe', 'r'),
                    array('pipe', 'w'),
                    array('pipe', 'w')
                );
                $process = proc_open(
                    $options['html_generator'] . ' --output-encoding=UTF-8 --rfc-references ' . $path,
                    $descriptorspec,
                    $pipes
                );
                if (is_resource($process)) {
                    fclose($pipes[0]);
                    $out = stream_get_contents($pipes[1]);
                    fclose($pipes[1]);
                    $errors = stream_get_contents($pipes[2]);
                    fclose($pipes[2]);
                    $return_value = proc_close($process);

                    $out = preg_replace('#.*<body>[\n]?(.*)</body>.*#ms', '\1', $out);

                    if ($filename == 'DOCS_ORIGIN') {
                        $out = preg_replace('#\?actionID=export&amp;format=rst#', '', $out);
                        $out = preg_replace(
                            '#<td class="field-body">(.*)</td>#',
                            '<td class="field-body"><a href="<?php echo $this->urlWriter->urlFor(array(\'controller\' => \'library\', \'action\' => \'docs\', \'library\' => \'' . $component->getName() . '\', \'file\' => \'\1\')); ?>">\1</a></td>',
                            $out
                        );
                    }

                    if (!empty($errors)) {
                        $this->_output->warn(print_r($errors, true));
                    }
                } else {
                    //@todo
                }
            }
            //@todo Pretend
            if (!empty($out)) {
                file_put_contents(
                    $view_root . '/' . $filename . '.html',
                    $out
                );
                $this->_output->ok(
                    sprintf(
                        'Wrote documentation file "%s"!',
                        $view_root . '/' . $filename . '.html'
                    )
                );
            }
        }
        $docs .= '</ul>';

        file_put_contents(
            $view_root . '/docs.html',
            $docs
        );


        $data_file = $destination . '/config/components.d/' . strtolower($component->getName()) . '.json';
        if (empty($options['pretend'])) {
            $data = $component->getData();
            $data->hasDocuments = !empty($doc_files);
            file_put_contents($data_file, json_encode($data));
            $this->_output->ok(
                sprintf(
                    'Wrote data for component %s to %s',
                    $component->getName(),
                    $data_file
                )
            );
        } else {
            $this->_output->info(
                sprintf(
                    'Would write data for component %s to %s',
                    $component->getName(),
                    $data_file
                )
            );
        }
    }

    private function _identifyDocFiles($path)
    {
        $doc_files = array();
        if (!is_dir($path)) {
            return $doc_files;
        }
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
            if ($file->isFile() &&
                preg_match('/[A-Z_]+/', $file->getFilename()) &&
                !in_array($file->getFilename(), array('COPYING', 'LICENSE')) &&
                !preg_match('#/examples/#', $file->getPathname())) {
                $doc_files[$file->getPathname()] = $file->getFilename();
            }
        }
        return $doc_files;
    }
}
