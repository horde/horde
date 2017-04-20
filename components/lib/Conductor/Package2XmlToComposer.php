<?php
/**
 * This file is part of Conductor
 *
 * Copyright © 2012-2013 Clay Loveless <clay@php.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the “Software”),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 * @copyright 2012 Clay Loveless <clay@php.net>
 * @license   http://claylo.mit-license.org/2012/ MIT License
 */

/**
 * Read a package.xml file (version 2.0) and convert it to a composer.json
 * file.
 *
 * Elements not typically found in package.xml files can be set (or
 * overridden) using methods or a config JSON file.
 *
 * Yes, some of this capability is present in Composer itself, given
 * Composer's ability to install PEAR packages. However, Composer relies on
 * PEAR channels, meaning using Composer's classes for this capability would
 * require developers supporting PEAR and Composer to first publish their
 * packages to their channel and then generate composer.json files
 * afterwards.
 *
 * Using this class, and in particular, the package2composer script that
 * leverages this class, developers are able to use the package.xml file
 * they're using with PEAR's package command to generate a composer.json,
 * which allows a one-stage release workflow to be scripted.
 *
 */
class Package2XmlToComposer
{
    protected $xml;
    protected $data;

    protected $type = 'library';
    protected $name;
    protected $keywords;
    protected $license;
    protected $homepage;
    protected $dependency_map = array();
    protected $extra_suggestions = array();
    protected $support = array();
    protected $autoload = array();
    protected $include_path = array();
    protected $bin_files;
    protected $config;
    protected $write_version_to_composer = true;
    protected $write_time_to_composer = true;
    protected $branch_alias;
    protected $extra;
    public $output_file = true;
    protected $package2file_path;

    /* Horde */
    protected $repositories = array();

    public function __construct($package2file, $config = null)
    {
        if (! file_exists($package2file)) {
            throw new \RuntimeException('Could not find ' . $package2file);
        }
        $this->package2file_path = $package2file;
        $this->xml = file_get_contents($package2file);

        if ($config !== null && file_exists($config)) {
            $config_json = file_get_contents($config);
            $this->config = json_decode($config_json, true);
            $this->applyConfig();
        }
    }

    /**
     * Apply values provided in a config JSON file.
     *
     * Recognized values in JSON configuration:
     *
     *   keywords
     *   license
     *   homepage
     *   dependency_map
     *   support
     *   autoload
     *   include_path
     *   bin
     *
     * Non-composer.json-standard values:
     * dependency_map:
     * dependency_map allows mapping of PEAR package dependencies to their
     * composer equivalents.
     *
     * output_path:
     * Allows setting where composer.json should be written. Default is to
     * write it in the same directory as package.xml
     *
     */
    protected function applyConfig()
    {
        if (empty($this->config)) {
            return;
        }

        if (isset($this->config['name'])) {
            $this->setName($this->config['name']);
        }

        if (isset($this->config['keywords'])) {
            $this->setKeywords($this->config['keywords']);
        }

        if (isset($this->config['license'])) {
            $this->setLicense($this->config['license']);
        }

        if (isset($this->config['homepage'])) {
            $this->setHomepage($this->config['homepage']);
        }

        if (isset($this->config['dependency_map'])) {
            $this->setDependencyMap($this->config['dependency_map']);
        }

        if (isset($this->config['extra_suggestions'])) {
            $this->setExtraSuggestions($this->config['extra_suggestions']);
        }
        if (isset($this->config['extra-suggestions'])) {
            $this->setExtraSuggestions($this->config['extra-suggestions']);
        }

        if (isset($this->config['support'])) {
            $this->setSupportInfo($this->config['support']);
        }

        if (isset($this->config['autoload'])) {
            $this->setAutoload($this->config['autoload']);
        }

        if (isset($this->config['include_path'])) {
            $this->setIncludePath($this->config['include_path']);
        }
        if (isset($this->config['include-path'])) {
            $this->setIncludePath($this->config['include-path']);
        }

        if (isset($this->config['bin'])) {
            $this->setBinFiles($this->config['bin']);
        }

        if (isset($this->config['version']) && $this->config['version'] === false) {
            $this->write_version_to_composer = false;
        }

        if (isset($this->config['time']) && $this->config['time'] === false) {
            $this->write_time_to_composer = false;
        }

        if (isset($this->config['branch_alias'])) {
            $this->setBranchAlias($this->config['branch_alias']);
        }
        if (isset($this->config['branch-alias'])) {
            $this->setBranchAlias($this->config['branch-alias']);
        }

        if (isset($this->config['extra'])) {
            // extra voids any branch alias, need to pass it IN extra
            $this->branch_alias = null;
            $this->setExtra($this->config['extra']);
        }

        if (isset($this->config['output_path'])) {
            $this->outputTo($this->config['output_path']);
        } else {
            $package2file_dir = dirname($this->package2file_path);
            $package2file_dir = realpath($package2file_dir);
            $this->outputTo($package2file_dir);
        }

        /* Horde */
        if (isset($this->config['repositories'])) {
            $this->setRepositories($this->config['repositories']);
        }
    }

    /**
     * Help output for CLI version
     */
    public static function help()
    {
        $output = <<<EOF
package2composer --package-file [package file] --config [config file]

EOF;
        echo $output;
        exit();
    }

    /**
     * Main method that starts conversion from the command-line
     *
     *
     */
    public static function main()
    {
        $params = array(
            'f:' => 'package-file:',
            'c:' => 'config:',
            'h' => 'help'
        );
        $short = join('', array_keys($params));
        $opts = getopt($short, $params);

        if (isset($opts['h']) || isset($opts['help'])) {
            self::help();
        }

        $config = null;
        $package_file = null;

        if (isset($opts['c'])) {
            $config = $opts['c'];
        } elseif (isset($opts['config'])) {
            $config = $opts['config'];
        }

        if (isset($opts['f'])) {
            $package_file = $opts['f'];
        } elseif (isset($opts['package-file'])) {
            $package_file = $opts['package-file'];
        }


        if ($package_file === null) {
            $cwd = getcwd();
            if (file_exists($cwd . '/package.xml')) {
                $package_file = $cwd . '/package.xml';
            }
        }

        if ($config === null && $package_file !== null) {
            // check same dir as package.xml
            $config_path = dirname($package_file);
            if (file_exists($config_path.'/package-composer.json')) {
                $config = $config_path.'/package-composer.json';
            }
        }

        $converter = new Package2XmlToComposer($package_file, $config);
        $ret = $converter->convert();
        if ($converter->output_file === null) {
            echo $ret;
        }
    }

    /**
     * Set the name of the package to put in composer.json
     *
     * If not set, the channel suggestedalias will be combined with lowercase
     * package name.
     *
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = strval($name);
        return $this;
    }

    /**
     * Set the type of composer package. Defaults to 'library'
     *
     * @param string
     * @return self
     */
    public function setType($type)
    {
        $this->type = strval($type);
        return $this;
    }

    /**
     * Should version information from package.xml be written to composer.json?
     *
     * @param bool
     * @return self
     */
    public function writeVersionToComposer($bool)
    {
        $this->write_version_to_composer = (bool) $bool;
        return $this;
    }

    /**
     * Should release date/time information from package.xml be written to
     * composer.json?
     *
     * @param bool
     * @return self
     */
    public function writeTimeToComposer($bool)
    {
        $this->write_time_to_composer = (bool) $bool;
        return $this;
    }

    /**
     * Set keywords which will be picked up by Packagist and/or other
     * package search tools.
     *
     * @param array $keywords
     * @return self
     */
    public function setKeywords($keywords)
    {
        $this->keywords = (array) $keywords;
        return $this;
    }

    /**
     * Set keywords which will be picked up by Packagist and/or other
     * package search tools.
     *
     * @param array $keywords
     * @return self
     */
    public function setSupportInfo($support)
    {
        $support = (array) $support;
        $this->support = array();
        $valid = array('email', 'issues', 'forum', 'wiki', 'irc', 'source');
        foreach ($support as $key => $val) {
            if (in_array($key, $valid)) {
                $this->support[$key] = $val;
            }
        }

        return $this;
    }

    /**
     * Set homepage to use in composer.json. Defaults to channel if not set.
     * package search tools.
     *
     * @param string $homepage
     * @return self
     */
    public function setHomepage($homepage)
    {
        $this->homepage = strval($homepage);
        return $this;
    }

    /**
     * Set SPDX license string. If omitted, license value from package.xml will
     * be used.
     *
     * @see http://www.spdx.org/licenses/
     * @param string
     * @return self
     */
    public function setLicense($license)
    {
        $this->license = strval($license);
        return $this;
    }

    /**
     * Set a name mapping to dependencies. Naming conventions can vary
     * between PEAR-style and composer/github style.
     *
     * @param array $map
     * @return self
     */
    public function setDependencyMap($map)
    {
        $this->dependency_map = (array) $map;
        return $this;
    }

    /**
     * Set extra suggestions for features, beyond what is mentioned in
     * package.xml
     *
     * @param array $suggestions
     * @return self
     */
    public function setExtraSuggestions($suggestions)
    {
        $this->extra_suggestions = (array) $suggestions;
        return $this;
    }

    /**
     * Set up any autoload configuration necessary
     *
     * @param array $config
     * @return self
     */
    public function setAutoload($config)
    {
        $this->autoload = (array) $config;
        return $this;
    }

    /**
     * Set up any branch-alias that may be specified
     *
     * @param array $config
     * @return self
     */
    public function setBranchAlias($aliases)
    {
        $this->branch_alias = (array) $aliases;
        return $this;
    }

    /**
     * Set the entire 'extra' array
     *
     * @see http://getcomposer.org/doc/04-schema.md#extra
     * @param array $config
     * @return self
     */
    public function setExtra($extra)
    {
        $this->extra = (array) $extra;
        return $this;
    }

    /**
     * If you must, set up any include paths, relative to vendor dir
     *
     * @param array $list of paths
     * @return self
     */
    public function setIncludePath($list)
    {
        $this->include_path = (array) $list;
        return $this;
    }

    /**
     * Set bin-files list
     *
     * @todo attempt to glean this from package.xml with override support
     * @param array $files
     * @return self
     */
    public function setBinFiles($files)
    {
        $this->bin_files = (array) $files;
        return $this;
    }

    /* Horde */
    public function setRepositories($repos)
    {
        $this->repositories = (array) $repos;
        return $this;
    }

    /**
     * Allow retrieval of parsed data structure.
     *
     * @return array
     */
    public function getParsedPackageData()
    {
        return $this->data;
    }

    /**
     * Allow setting of the output path location
     *
     * @param string $output_path The DIRECTORY to write composer.json into
     */
    public function outputTo($output_path)
    {
        $output_path = rtrim($output_path, '/');
        if (is_dir($output_path) && is_writable($output_path)) {
            $this->output_file = $output_path . '/composer.json';
        }
        return $this;
    }

    /**
     *
     */
    public function convert($output_file = null)
    {
        $pv2 = new PEARPackageFilev2($this->xml);
        $this->data = $pv2->parse();

        if (! empty($this->extra_suggestions)) {
            $this->data['dependencies']['optional'] = array_merge(
                $this->data['dependencies']['optional'],
                $this->extra_suggestions
            );
        }

        if (empty($this->output_file) && ! empty($output_file)) {
            $this->output_file = $output_file;
        }

        if (empty($this->name) && isset($this->data['channel'])) {
            $suggested_alias = $this->getChannelSuggestedAlias($this->data['channel']);
            $pkgname = strtolower($this->data['name']);
            $pkgname = str_replace('_', '-', $pkgname);
            $this->name = strtolower($suggested_alias . '/' . $pkgname);
        }

        $this->autoload = array('psr-0' => array($this->data['name'] => "lib/"));

        // assemble human-readable composer.json
        $j = new stdClass();
        $j->name = $this->name;

        // short package.xml summaries are what composer means for descriptions
        if (isset($this->data['summary'])) {
            $j->description = $this->data['summary'];
        }

        if (! empty($this->type)) {
            $j->type = $this->type;
        }

        if (! empty($this->keywords)) {
            $j->keywords = $this->keywords;
        }

        if (! empty($this->homepage)) {
            $homepage = $this->homepage;
        } elseif (isset($this->data['channel'])) {
            $homepage = 'https://' . $this->data['channel'];
        }
        $j->homepage = $homepage;

        if (! empty($this->license)) {
            $license = $this->license;
        } elseif (isset($this->data['license']['type'])) {
            $license = $this->data['license']['type'];
        }
        $j->license = $license;

        $j->authors = array();
        $author_types = array('lead', 'developer', 'contributor', 'helper');
        foreach ($author_types as $atype) {
            if (! empty($this->data[$atype])) {
                foreach ($this->data[$atype] as $dev) {
                    $author = new stdClass();
                    if (! empty($dev['name'])) {
                        $author->name = $dev['name'];
                    }
                    if (! empty($dev['email'])) {
                        $author->email = $dev['email'];
                    }
                    $author->role = $atype;
                    $j->authors[] = $author;
                }
            }
        }

        if (isset($this->data['version']['release']) && $this->write_version_to_composer) {
            $j->version = $this->data['version']['release'];
        }
        if (isset($this->data['date']) && $this->write_time_to_composer) {
            $j->time = $this->data['date'];
        }

        if (! empty($this->support)) {
            $j->support = $this->support;
        }

        /* Horde */
        if (!empty($this->repositories)) {
            $j->repositories = array();
            foreach ($this->repositories as $val) {
                $j->repositories[] = array('type' => $val[0], 'url' => $val[1]);
            }
        }

        // requirements
        $deptypes = array('required' => 'require', 'optional' => 'suggest');
        foreach ($deptypes as $pear_deptype => $composer_deptype) {
            if (! empty($this->data['dependencies'][$pear_deptype])) {
                $deps = new stdClass();
                foreach ($this->data['dependencies'][$pear_deptype] as $req) {
                    if ($req['dep'] == 'pearinstaller') {
                        continue;
                    }
                    if ($req['dep'] == 'php') {
                        $deps->php = $this->getDepVersionString($req);
                    }
                    if ($req['dep'] == 'extension') {
                        $deps->{'ext-' . $req['name']} = $this->getDepVersionString($req);
                    }
                    if ($req['dep'] == 'package') {

                        $reqname = '';
                        // is it in the map?
                        $reqkey = '';
                        if (isset($req['channel'])) {
                            $reqkey .= $req['channel'];
                        } else {
                            $reqkey .= $this->data['channel'];
                        }
                        $reqkey .= '/' . $req['name'];
                        if (isset($this->dependency_map[$reqkey])) {
                            $reqname = $this->dependency_map[$reqkey];
                        } else {
                            $reqname = 'pear-' . $reqkey;
                        }

                        $deps->$reqname = $this->getDepVersionString($req);
                    }
                }
                $j->$composer_deptype = $deps;
            }
        }

        $replaceVersion = preg_replace('/^(\d+)\..*/', '$1.*', $j->version);
        $j->replace = new stdClass();
        $j->replace->{'pear-' . $this->data['channel'] . '/' . $this->data['name']} = $replaceVersion;
        $j->replace->{'pear-' . $this->getChannelSuggestedAlias($this->data['channel']) . '/' . $this->data['name']} = $replaceVersion;

        if (! empty($this->bin_files)) {
            $j->bin = array();
            foreach ($this->bin_files as $file) {
                // composer creates its own .bat wrapper, so skip this.
                // @see https://github.com/sebastianbergmann/phpunit/pull/648
                if (substr($file, -4) == '.bat') {
                    continue;
                }
                $j->bin[] = $file;
            }

            $j->config = array('bin-dir' => 'bin');
        }

        if (! empty($this->autoload)) {
            $j->autoload = new stdClass();
            foreach ($this->autoload as $type => $list) {
                if ($type == 'psr-0') {
                    $j->autoload->{'psr-0'} = new stdClass();
                    foreach ($list as $key => $val) {
                        $j->autoload->{'psr-0'}->$key = (string)$val;
                    }
                } elseif ($type == 'files' || $type == 'classmap') {
                    $j->autoload->$type = array();
                    foreach ($list as $val) {
                        $j->autoload->{$type}[] = $val;
                    }
                }
            }
        }

        if (! empty($this->extra)) {
            $j->extra = $this->extra;
        } elseif (! empty($this->branch_alias)) {
            $j->extra = array('branch-alias' => $this->branch_alias);
        }

        if (! empty($this->include_path)) {
            $j->{'include-path'} = array();
            foreach ($this->include_path as $val) {
                $j->{'include-path'}[] = (string)$val;
            }
        }

        // wrap it up
        $j = json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

        if ($this->output_file === false) {
            return $j;
        } elseif ($this->output_file === true) {
            $cwd = getcwd();
            file_put_contents($cwd.'/composer.json', $j);
        } else {
            file_put_contents($this->output_file, $j);
        }
    }

    protected function getDepVersionString($req)
    {
        $out = array();
        if (! empty($req['min'])) {
            $v = '^' . trim($req['min'], '.0');
            if ($req['dep'] == 'package' &&
                $this->data['stability']['release'] == 'stable') {
                $v .= '@stable';
            }
            $out[] = $v;
        }
        if (!empty($req['max'])) {
            if (!empty($req['exclude']) &&
                is_array($req['exclude']) &&
                $req['exclude'][0] == $req['max'] &&
                preg_match('/(\d)\.0\.0alpha1/', $req['max'], $version)) {
                if (substr($req['min'], 0, 2) != ($version[1] - 1) . '.') {
                    if ($req['dep'] == 'php') {
                        $out[] = '|| ^' . ($version[1] - 1);
                    } else {
                        $out[] = '<'.preg_replace('/(.0)*alpha1$/', '', $req['max']);
                    }
                }
            } else {
                $out[] = '<='.$req['max'];
            }
        }

        if (! empty($out)) {
            $ret = join(' ', $out);
        } else {
            $ret = '*';
        }
        return $ret;
    }

    protected function getChannelSuggestedAlias($channel)
    {
        $channelxml = file_get_contents('https://' . $channel . '/channel.xml');
        $channel = new \SimpleXMLElement($channelxml);

        return (string) $channel->suggestedalias;
    }
}
