<?php
/**
 * Class to make an "official" Horde or application release.
 *
 * Copyright 1999 Mike Hardy
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Mike Hardy
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Release
 */
class Horde_Release
{
    /**
     * Default options.
     *
     * @var array
     */
    protected $_options = array(
        'test' => false,
        'nocommit' => false,
        'noftp' => false,
        'noannounce' => false,
        'nofreshmeat' => false,
        'nowhups' => false,
    );

    /* Constants for the release foucs - these are used as tags when sending
     * FM the new release announcement.*/
    const FOCUS_INITIAL = 'Initial announcement';
    const FOCUS_MINORFEATURE = 'Minor feature enhancements';
    const FOCUS_MAJORFEATURE = 'Major feature enhancements';
    const FOCUS_MINORBUG = 'Minor bugfixes';
    const FOCUS_MAJORBUG = 'Major bugfixes';
    const FOCUS_MINORSECURITY = 'Minor security fixes';
    const FOCUS_MAJORSECURITY = 'Major security fixes';
    const FOCUS_DOCS = 'Documentation improvements';

    /**
     * Version number of release.
     *
     * @var string
     */
    protected $_sourceVersionString;

    /**
     * Version number of previous release.
     *
     * @var string
     */
    protected $_oldSourceVersionString;

    /**
     * Version number of next release.
     *
     * @var string
     */
    protected $_newSourceVersionString;

    /**
     * Version number of next release for docs/CHANGES.
     *
     * @var string
     */
    protected $_newSourceVersionStringPlain;

    /**
     * Major version number of Horde compatible to this release.
     *
     * @var string
     */
    protected $_hordeVersionString;

    /**
     * Major version number of Horde compatible to the previous release.
     *
     * @var string
     */
    protected $_oldHordeVersionString;

    /**
     * CVS tag of release.
     *
     * @var string
     */
    protected $_tagVersionString;

    /**
     * CVS tag of previous release.
     *
     * @var string
     */
    protected $_oldTagVersionString;

    /**
     * Revision number of CHANGES file.
     *
     * @var string
     */
    protected $_changelogVersion;

    /**
     * Revision number of previous CHANGES file.
     *
     * @var string
     */
    protected $_oldChangelogVersion;

    /**
     * Version string to use in Whups
     *
     * @var string
     */
    protected $_ticketVersion;

    /**
     * Version description to use in Whups
     *
     * @var string
     */
    protected $_ticketVersionDesc = '';

    /**
     * Directory name of unpacked tarball.
     *
     * @var string
     */
    protected $_directoryName;

    /**
     * Directory name of unpacked previous tarball.
     *
     * @var string
     */
    protected $_oldDirectoryName;

    /**
     * Filename of the tarball.
     *
     * @var string
     */
    protected $_tarballName;

    /**
     * MD5 sum of the tarball.
     *
     * @var string
     */
    protected $_tarballMD5;

    /**
     * Whether or not to create a patch file.
     *
     * @var boolean
     */
    protected $_makeDiff = false;

    /**
     * The list of binary diffs.
     *
     * @var array
     */
    protected $_binaryDiffs = array();

    /**
     * Whether or not we have an old version to compare against.
     *
     * @var boolean
     */
    protected $_oldVersion = false;

    /**
     * Filename of the gzip'ed patch file (without .gz extension).
     *
     * @var string
     */
    protected $_patchName;

    /**
     * MD5 sum of the patch file.
     *
     * @var string
     */
    protected $_patchMD5;

    /**
     * Whether or not this is a final release version.
     *
     * @var boolean
     */
    protected $_latest = true;

    /**
     * Populated when the RELEASE_NOTES file is included.
     * Should probably be refactored to use a setter for each
     * property the RELEASE_NOTES file sets...
     *
     * @var array
     */
    public $notes = array();

    /**
     * Load the configuration
     */
    public function __construct($options = array())
    {
        $this->_options = array_merge($this->_options, $options);
        $cvsroot = getenv('CVSROOT');
        if (empty($cvsroot)) {
            putenv('CVSROOT=:ext:' . $this->_options['horde']['user'] . '@cvs.horde.org:/repository');
        }
        print 'CVSROOT ' . getenv('CVSROOT') . "\n";
        if (!empty($this->_options['cvs']['cvs_rsh'])) {
            putenv('CVS_RSH=' . $this->_options['cvs']['cvs_rsh']);
        }
        print 'CVS_RSH ' . getenv('CVS_RSH') . "\n";
    }

    public function __get($property)
    {
        return $this->{'_' . $property};
    }

    /**
     * Delete the directory given as an argument
     */
    public function deleteDirectory($directory)
    {
        print "Deleting directory $directory\n";
        system("sudo rm -rf $directory");
    }

    /**
     * tar and gzip the directory given as an argument
     */
    public function makeTarball()
    {
        print "Setting owner/group to 0/0\n";
        system("sudo chown -R 0:0 $this->_directoryName");

        print "Making tarball\n";
        $this->_tarballName = $this->_directoryName . '.tar.gz';
        if (file_exists($this->_tarballName)) {
            unlink($this->_tarballName);
        }
        system("tar -zcf $this->_tarballName $this->_directoryName");
        exec($this->_options['md5'] . ' ' . $this->_tarballName, $this->_tarballMD5);
    }

    /**
     * Label all of the source here with the new label given as an argument
     */
    public function tagSource($directory = null, $version = null)
    {
        if (empty($directory)) {
            $directory = $this->_directoryName;
        }
        if (empty($version)) {
            $version = $this->_tagVersionString;
        }
        if (!$this->_options['nocommit']) {
            print "Tagging source in $directory with tag $version\n";
            system("cd $directory;cvs tag -F $version > /dev/null 2>&1");
        } else {
            print "NOT tagging source in $directory (would have been tag $version)\n";
        }
    }

    /**
     * Make a diff of the two directories given as arguments
     */
    public function diff()
    {
        $this->_patchName = 'patch-' . $this->_oldDirectoryName . str_replace($this->_options['module'], '', $this->_directoryName);
        print "Making diff between $this->_oldDirectoryName and $this->_directoryName\n";
        system("diff -uNr $this->_oldDirectoryName $this->_directoryName > $this->_patchName");

        // Search for binary diffs
        $this->_binaryDiffs = array();
        $handle = fopen($this->_patchName, 'r');
        if ($handle) {
            while (!feof($handle)) {
                // GNU diff reports binary diffs as the following:
                // Binary files ./locale/de_DE/LC_MESSAGES/imp.mo and ../../horde/imp/locale/de_DE/LC_MESSAGES/imp.mo differ
                if (preg_match("/^Binary files (.+) and (.+) differ$/i", rtrim(fgets($handle)), $matches)) {
                    // [1] = oldname, [2] = newname
                    $this->_binaryDiffs[] = ltrim(str_replace($this->_oldDirectoryName . '/', '', $matches[1]));
                }
            }
            fclose($handle);
        }
        system("gzip -9f $this->_patchName");
        exec($this->_options['md5'] . ' ' . $this->_patchName . '.gz', $this->_patchMD5);
    }

    /**
     * Change the version file for the module in the directory specified to
     * the version specified
     */
    public function updateVersionFile($directory, $version_string)
    {
        $module = $this->_options['module'];
        $all_caps_module = strtoupper($module);
        print "Updating version file for $module\n";

        // construct the filenames
        $filename_only = 'version.php';
        $filename = $directory . '/lib/' . $filename_only;
        $newfilename = $filename . '.new';

        $oldfp = fopen($filename, 'r');
        $newfp = fopen($newfilename, 'w');
        while ($line = fgets($oldfp)) {
            if (strstr($line, 'VERSION')) {
                fwrite($newfp, "<?php define('{$all_caps_module}_VERSION', '$version_string') ?>\n");
            } else {
                fwrite($newfp, $line);
            }
        }
        fclose($oldfp);
        fclose($newfp);

        system("mv -f $newfilename $filename");
        if (!$this->_options['nocommit']) {
            system("cd $directory/lib/; cvs commit -f -m \"Tarball script: building new $module release - $version_string\" $filename_only > /dev/null 2>&1");
        }
    }

    /**
     * Update the CHANGES file with the new version number
     */
    public function updateSentinel()
    {
        $module = $this->_options['module'];
        $all_caps_module = strtoupper($module);
        print "Updating CHANGES file for $module\n";
        $version = 'v' . $this->_newSourceVersionStringPlain;

        // construct the filenames
        $filename_only = 'CHANGES';
        $filename = $this->_directoryName . '/docs/' . $filename_only;
        $newfilename = $filename . '.new';
        $oldfp = fopen($filename, 'r');
        $newfp = fopen($newfilename, 'w');
        fwrite($newfp, str_repeat('-', strlen($version)) . "\n$version\n" .
               str_repeat('-', strlen($version)) . "\n\n\n\n\n");
        while ($line = fgets($oldfp)) {
            fwrite($newfp, $line);
        }
        fclose($oldfp);
        fclose($newfp);

        system("mv -f $newfilename $filename");
        if (!$this->_options['nocommit']) {
            system("cd {$this->_directoryName}/docs/; cvs commit -f -m \"Tarball script: building new $module release - {$this->_newSourceVersionString}\" $filename_only > /dev/null 2>&1");
        }
    }

    /**
     * get and save the revision number of the CHANGES file
     */
    public function saveChangelog($old = false, $directory = null)
    {
        if (empty($directory)) {
            if ($old) {
                $directory = './' . $this->_oldDirectoryName . '/docs';
            } else {
                $directory = './' . $this->_directoryName . '/docs';
            }
        }
        if (!$old) {
            include "$directory/RELEASE_NOTES";
            if (strlen($this->notes['fm']['changes']) > 600) {
                print "WARNING: freshmeat release notes are longer than 600 characters!\n";
            }
        }
        exec("cd $directory; cvs status CHANGES", $output);
        foreach ($output as $line) {
            if (preg_match('/Repository revision:\s+([\d.]+)/', $line, $matches)) {
                if ($old) {
                    $this->_oldChangelogVersion = $matches[1];
                } else {
                    $this->_changelogVersion = $matches[1];
                }
                break;
            }
        }
    }

    /**
     * work through the source directory given, cleaning things up by removing
     * directories and files we don't want in the tarball
     */
    public function cleanDirectories($directory)
    {
        print "Cleaning source tree\n";
        $directories = explode("\n", `find $directory -type d \\( -name CVS -o -name packaging -o -name framework \\) -print | sort -r`);
        foreach ($directories as $dir) {
            system("rm -rf $dir");
        }
        $cvsignores = explode("\n", `find $directory -name .cvsignore -print`);
        foreach ($cvsignores as $file) {
            if (!empty($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Check out the tag we've been given to work with and move it to the
     * directory name given
     */
    public function checkOutTag($mod_version, $directory, $module = null)
    {
        if (empty($module)) {
            $module = $this->_options['module'];
        }

        if (@is_dir($module)) {
            system("rm -rf $module");
        }

        // Use CVS to check the source out
        if ($mod_version == 'HEAD') {
            print "Checking out HEAD for $module\n";
            $cmd = "cvs -q co -P $module > /dev/null";
            system($cmd, $status);
        } else {
            print "Checking out tag $mod_version for $module\n";
            $cmd = "cvs -q co -P -r$mod_version $module > /dev/null";
            system($cmd, $status);
        }
        if ($status) {
            die("\nThere was an error running the command\n$cmd\n");
        }

        // Move the source into the directory specified
        print "Moving $module to $directory\n";
        if (@is_dir($directory)) {
            system("rm -rf $directory");
        }
        system("mv -f $module $directory");
    }

    /**
     * Checkout and install framework
     */
    public function checkOutFramework($mod_version, $directory)
    {
        if ($this->_options['module'] == 'horde' &&
            ($this->_options['branch'] == 'HEAD' ||
             strstr($this->_options['branch'], 'FRAMEWORK'))) {
            if ($this->_options['branch'] == 'HEAD') {
                print "Checking out HEAD for framework\n";
            } else {
                print "Checking out tag $mod_version for framework\n";
            }
            $cmd = "cd $directory; cvs co -P -r$mod_version framework > /dev/null 2>&1; cd ..";
            system($cmd, $status);
            if ($status) {
                die("\nThere was an error running the command\n$cmd\n");
            }
            print "Installing framework packages\n";
            passthru("install_framework --copy --src ./$directory/framework --horde /tmp --dest ./$directory/lib", $result);
            if ($result) {
                exit;
            }

            print "Setting include path\n";
            $filename = $directory . '/lib/core.php';
            $newfilename = $filename . '.new';
            $oldfp = fopen($filename, 'r');
            $newfp = fopen($newfilename, 'w');
            while ($line = fgets($oldfp)) {
                fwrite($newfp, str_replace('// ini_set(\'include_path\'', 'ini_set(\'include_path\'', $line));
            }
            fclose($oldfp);
            fclose($newfp);
            system("mv -f $newfilename $filename");
        }
    }

    /**
     * Upload tarball to the FTP server
     */
    public function upload()
    {
        $module = $this->_options['module'];
        $user = $this->_options['horde']['user'];
        $identity = empty($this->_options['ssh']['identity']) ? '' : ' -i ' . $this->_options['ssh']['identity'];
        $chmod = "chmod 664 /horde/ftp/pub/$module/$this->_tarballName;";
        if ($this->_makeDiff) {
            $chmod .= " chmod 664 /horde/ftp/pub/$module/patches/$this->_patchName.gz;";
        }
        if ($this->_latest &&
            strpos($this->_options['branch'], 'RELENG') !== 0) {
            $chmod .= " ln -sf $this->_tarballName /horde/ftp/pub/$module/$module-latest.tar.gz;";
        }

        if (!$this->_options['noftp']) {
            print "Uploading $this->_tarballName to $user@ftp.horde.org:/horde/ftp/pub/$module/\n";
            system("scp -P 35$identity $this->_tarballName $user@ftp.horde.org:/horde/ftp/pub/$module/");
            if ($this->_makeDiff) {
                print "Uploading $this->_patchName.gz to $user@ftp.horde.org:/horde/ftp/pub/$module/patches/\n";
                system("scp -P 35$identity $this->_patchName.gz $user@ftp.horde.org:/horde/ftp/pub/$module/patches/");
            }
            print "Executing $chmod\n";
            system("ssh -p 35 -l $user$identity ftp.horde.org '$chmod'");
        } else {
            print "NOT uploading $this->_tarballName to ftp.horde.org:/horde/ftp/pub/$module/\n";
            if ($this->_makeDiff) {
                print "NOT uploading $this->_patchName.gz to $user@ftp.horde.org:/horde/ftp/pub/$module/patches/\n";
            }
            print "NOT executing $chmod\n";
        }
    }

    /**
     * announce release to mailing lists and freshmeat.
     */
    public function announce($doc_dir = null)
    {
        $module = $this->_options['module'];
        if (!isset($this->notes)) {
            print "NOT announcing release, RELEASE_NOTES missing.\n";
            return;
        }
        if (!empty($this->_options['noannounce']) ||
            !empty($this->_options['nofreshmeat'])) {
            print "NOT announcing release on freshmeat.net\n";
        } else {
            print "Announcing release on freshmeat.net\n";
        }

        if (empty($doc_dir)) {
            $doc_dir = $module . '/docs';
        }

        $url_changelog = $this->_oldVersion
            ? "http://cvs.horde.org/diff.php/$doc_dir/CHANGES?rt=horde&r1={$this->_oldChangelogVersion}&r2={$this->_changelogVersion}&ty=h"
            : '';

        // Params to add new release on FM
        $version = array('version' => $this->_sourceVersionString,
                         'changelog' => $this->notes['fm']['changes']);

        if (is_array($this->notes['fm']['focus'])) {
            $version['tag_list'] = $this->notes['fm']['focus'];
        } else {
            $version['tag_list'] = array($this->notes['fm']['focus']);
        }

        // Params to update the various project links on FM
        $links = array();
        $links[] = array('label' => 'Tar/GZ',
                         'location' => "ftp://ftp.horde.org/pub/$module/{$this->_tarballName}");
        if (!empty($url_changelog)) {
            $links[] =  array('label' => 'Changelog',
                              'location' => $url_changelog);
        }

        if (!empty($this->_options['noannounce']) ||
            !empty($this->_options['nofreshmeat'])) {

            print "Announcement data:\n";
            print_r($version);
            print_r($links);
        } else {
            try {
                $fm = $this->_fmPublish($version);
                $fm = $this->_fmUpdateLinks($links);
            } catch (Horde_Exception $e) {
                print "Error publishing to FM:\n";
                print $e->getMessage();
            }
        }

        $ml = (!empty($this->notes['list'])) ? $this->notes['list'] : $module;
        if (substr($ml, 0, 6) == 'horde-') {
            $ml = 'horde';
        }

        $to = "announce@lists.horde.org, vendor@lists.horde.org, $ml@lists.horde.org";
        if (!$this->_latest) {
            $to .= ', i18n@lists.horde.org';
        }

        if (!empty($this->_options['noannounce'])) {
            print "NOT announcing release on $to\n";
        } else {
            print "Announcing release to $to\n";
        }

        // Building headers
        $subject = $this->notes['name'] . ' ' . $this->_sourceVersionString;
        if ($this->_latest) {
            $subject .= ' (final)';
        }
        if (in_array(self::FOCUS_MAJORSECURITY, $version['tag_list'])) {
            $subject = '[SECURITY] ' . $subject;
        }
        $headers = array('From' => $this->_options['ml']['from'],
                         'To' => $to,
                         'Subject' => $subject);

        // Building message text
        $body = $this->notes['ml']['changes'];
        if ($this->_oldVersion) {
            $body .= "\n\n" .
                sprintf('The full list of changes (from version %s) can be viewed here:', $this->_oldSourceVersionString) .
                "\n\n" .
                $url_changelog;
        }
        $body .= "\n\n" .
            sprintf('The %s %s distribution is available from the following locations:', $this->notes['name'], $this->_sourceVersionString) .
            "\n\n" .
            sprintf('    ftp://ftp.horde.org/pub/%s/%s', $module, $this->_tarballName) . "\n" .
            sprintf('    http://ftp.horde.org/pub/%s/%s', $module, $this->_tarballName);
        if ($this->_makeDiff) {
            $body .= "\n\n" .
                sprintf('Patches against version %s are available at:', $this->_oldSourceVersionString) .
                "\n\n" .
                sprintf('    ftp://ftp.horde.org/pub/%s/patches/%s.gz', $module, $this->_patchName) . "\n" .
                sprintf('    http://ftp.horde.org/pub/%s/patches/%s.gz', $module, $this->_patchName);

            if (!empty($this->_binaryDiffs)) {
                $body .= "\n\n" .
                    'NOTE: Patches do not contain differences between files containing binary data.' . "\n" .
                    'These files will need to be updated via the distribution files:' . "\n\n    " .
                    implode("\n    ", $this->_binaryDiffs);
            }
        }
        $body .= "\n\n" .
            'Or, for quicker access, download from your nearest mirror:' .
            "\n\n" .
            '    http://www.horde.org/mirrors.php' .
            "\n\n" .
            'MD5 sums for the packages are as follows:' .
            "\n\n" .
            '    ' . $this->_tarballMD5[0] . "\n" .
            '    ' . $this->_patchMD5[0] .
            "\n\n" .
            'Have fun!' .
            "\n\n" .
            'The Horde Team.';

        if (!empty($this->_options['noannounce'])) {
            print "Message headers:\n";
            print_r($headers);
            print "Message body:\n$body\n";
            return;
        }

        // Building and sending message
        $mail = new Horde_Mime_Mail();
        $mail->setBody($body, 'utf-8', false);
        $mail->addHeaders($headers);
        try {
            $class = 'Horde_Mail_Transport_' . ucfirst($this->_options['mailer']['type']);
            $mail->send(new $class($this->_options['mailer']['params']));
        } catch (Horde_Mime_Exception $e) {
            print $e->getMessage() . "\n";
        }
    }

    /**
     * Attempt to publish the new release to the fm restful api.
     *
     * @param array $params  The array of fm release parameters
     *
     * @return mixed Result of the attempt / PEAR_Error on failure
     */
    protected function _fmPublish($params)
    {
        $key = $this->_options['fm']['user_token'];
        $fm_params = array('auth_code' => $key,
                           'release' => $params);
        $http = new Horde_Http_Client();
        try {
            $response = $http->post('http://freshmeat.net/projects/' . $this->notes['fm']['project'] . '/releases.json',
                                    Horde_Serialize::serialize($fm_params, Horde_Serialize::JSON),
                                    array('Content-Type' => 'application/json'));
        } catch (Horde_Http_Exception $e) {
            if (strpos($e->getMessage(), '201 Created') === false) {
                throw new Horde_Exception_Prior($e);
            } else {
                return '';
            }
        }

        // 201 Created
        return $response->getBody();
    }

    /**
     * Attempt to update FM project links
     */
    public function _fmUpdateLinks($links)
    {
        // Need to get the list of current URLs first, then find the one we want
        // to update.
        $http = new Horde_Http_Client();
        try {
            $response = $http->get('http://freshmeat.net/projects/' . $this->notes['fm']['project'] . '/urls.json?auth_code=' . $this->_options['fm']['user_token']);
        } catch (Horde_Http_Exception $e) {
            throw new Horde_Exception_Prior($e);
        }

        $url_response = Horde_Serialize::unserialize($response->getBody(), Horde_Serialize::JSON);
        if (!is_array($url_response)) {
            $url_response = array();
        }

        // Should be an array of URL info in response...go through our requested
        // updates and see if we can find the correct 'permalink' parameter.
        foreach ($links as $link) {
            $permalink = '';
            foreach ($url_response as $url) {
                // FM docs contradict this, but each url entry in the array is
                // wrapped in a 'url' property.
                $url = $url->url;
                if ($link['label'] == $url->label) {
                    $permalink = $url->permalink;
                    break;
                }
            }
            $link = array('auth_code' => $this->_options['fm']['user_token'],
                          'url' => $link);
            $http = new Horde_Http_Client();
            if (empty($permalink)) {
                // No link found to update...create it.
                try {
                    $response = $http->post('http://freshmeat.net/projects/' . $this->notes['fm']['project'] . '/urls.json',
                                            Horde_Serialize::serialize($link, Horde_Serialize::JSON),
                                            array('Content-Type' => 'application/json'));
                    $response = $response->getBody();
                } catch (Horde_Http_Exception $e) {
                    if (strpos($e->getMessage(), '201 Created') === false) {
                        throw new Horde_Exception_Prior($e);
                    } else {
                        $response = '';
                    }
                }
            } else {
                // Found the link to update...update it.
                try {
                    $response = $http->put('http://freshmeat.net/projects/' . $this->notes['fm']['project'] . '/urls/' . $permalink . '.json',
                                           Horde_Serialize::serialize($link, Horde_Serialize::JSON),
                                           array('Content-Type' => 'application/json'));
                    $response = $response->getBody();
                    // Status: 200???
                } catch (Horde_Http_Exception $e) {
                    throw new Horde_Exception_Prior($e);
                }
            }
        }

        return true;
    }

    /**
     * Do testing (development only)
     */
    public function test()
    {
        if (!$this->_options['test']) {
            return;
        }

        print "options['version']={$this->_options['version']}\n";
        print "options['oldversion']={$this->_options['oldversion']}\n";
        print "options['module']={$this->_options['module']}\n";
        print "options['branch']={$this->_options['branch']}\n";

        $this->setVersionStrings();

        print "hordeVersionString={$this->_hordeVersionString}\n";
        print "oldHordeVersionString={$this->_oldHordeVersionString}\n";
        print "makeDiff={$this->_makeDiff}\n";
        print "oldVersion={$this->_oldVersion}\n";
        print "directoryName={$this->_directoryName}\n";
        if ($this->_oldVersion) {
            print "oldDirectoryName={$this->_oldDirectoryName}\n";
        }
        print "tagVersionString={$this->_tagVersionString}\n";
        if ($this->_oldVersion) {
            print "oldTagVersionString={$this->_oldTagVersionString}\n";
        }
        print "sourceVersionString={$this->_sourceVersionString}\n";
        if ($this->_oldVersion) {
            print "oldSourceVersionString={$this->_oldSourceVersionString}\n";
        }
        print "newSourceVersionString={$this->_newSourceVersionString}\n";
        print "newSourceVersionStringPlain={$this->_newSourceVersionStringPlain}\n";
        print "ticketVersion={$this->_ticketVersion}\n";
        print "ticketVersionDesc=MODULE{$this->_ticketVersionDesc}\n";
        if ($this->_latest) {
            print "This is a production release\n";
        }
        exit(0);
    }

    /**
     * Add the new version to bugs.horde.org
     */
    public function addWhupsVersion()
    {
        if (!isset($this->notes)) {
            print "\nNOT updating bugs.horde.org, RELEASE_NOTES missing.\n";
            return;
        }
        $this->_ticketVersionDesc = $this->notes['name'] . $this->_ticketVersionDesc;

        $params = array('url' => 'https://dev.horde.org/horde/rpc.php',
                        'user' => $this->_options['horde']['user'],
                        'pass' => $this->_options['horde']['pass']);
        $whups = new Horde_Release_Whups($params);

        if (!$this->_options['nowhups']) {
            print "Adding new versions to bugs.horde.org: ";
            /* Set the new version in the queue */
            try {
                $whups->addNewVersion($this->_options['module'], $this->_ticketVersion, $this->_ticketVersionDesc);
                print "OK\n";
            } catch (Horde_Exception $e) {
                print "Failed:\n";
                print $e->getMessage() . "\n";
            }
        } else {
            print "NOT updating bugs.horde.org:\n";
            print "New ticket version WOULD have been {$this->_ticketVersion}\n";
            print "New ticket version description WOULD have been {$this->_ticketVersionDesc}\n";

            /* Perform some sanity checks on bugs.horde.org */
            try {
                $queue = $whups->getQueueId($this->_options['module']);

                if ($queue === false) {
                    print "Was UNABLE to locate the queue id for {$this->_options['module']}\n";
                } else {
                    print "The queue id on bugs.horde.org is $queue \n";
                }
            } catch (Horde_Exception $e) {
                print "Will be UNABLE to update bugs.horde.org:\n";
                print $e->getMessage() . "\n";
            }
        }
    }

    /**
     * Set the version strings to use given the arguments
     */
    public function setVersionStrings()
    {
        $ver = explode('.', $this->_options['version']);
        if (preg_match('/(\d+)\-(.*)/', $ver[count($ver) - 1], $matches)) {
            $ver[count($ver) - 1] = $matches[1];
            $plus = $matches[2];
        }
        if (preg_match('/(H\d)-(\d+)/', $ver[0], $matches)) {
            $ver[0] = $matches[2];
            $this->_hordeVersionString = $matches[1];
        }
        if (count($ver) > 2 && $ver[count($ver) - 1] == '0') {
            die("version {$this->_options['version']} should not have the trailing 3rd-level .0\n");
        }

        // check if --oldversion is empty or 0
        if (!empty($this->_options['oldversion'])) {
            $this->_oldVersion = true;
        }
        $oldver = explode('.', $this->_options['oldversion']);
        if (preg_match('/(\d+)\-(.*)/', $oldver[count($oldver) - 1], $matches)) {
            $oldver[count($oldver) - 1] = $matches[1];
            $oldplus = $matches[2];
        }
        if (preg_match('/(H\d)-(\d+)/', $oldver[0], $matches)) {
            $oldver[0] = $matches[2];
            $this->_oldHordeVersionString = $matches[1];
        }

        // set the string to use as the tag name in CVS
        $this->_tagVersionString = strtoupper($this->_options['module'] . '_' . preg_replace('/\W/', '_', implode('_', $ver)));
        if (isset($plus)) {
            $this->_tagVersionString .= '_' . $plus;
        }

        // create patches only if not a major version change
        if ($this->_options['oldversion'] && $ver[0] == $oldver[0]) {
            $this->_makeDiff = true;
        }

        // is this really a production release?
        if (isset($plus) && !preg_match('/^pl\d/', $plus)) {
            $this->_latest = false;
        }

        // set the string to insert into the source version file
        $this->_sourceVersionString = implode('.', $ver);
        if (isset($plus)) {
            $this->_sourceVersionString .= '-' . $plus;
        }

        // set the string to be used for the directory to package from
        $this->_directoryName = $this->_options['module'] . '-';
        if (!empty($this->_hordeVersionString)) {
            $this->_directoryName .= $this->_hordeVersionString . '-';
        }
        $this->_directoryName = strtolower($this->_directoryName . $this->_sourceVersionString);

        if (!empty($this->_hordeVersionString)) {
            $this->_sourceVersionString = $this->_hordeVersionString . ' (' . $this->_sourceVersionString . ')';
        }

        if ($this->_oldVersion) {
            $this->_oldSourceVersionString = implode('.', $oldver);
            if (isset($oldplus)) {
                $this->_oldSourceVersionString .= '-' . $oldplus;
            }
            $this->_oldTagVersionString = strtoupper($this->_options['module'] . '_' . implode('_', $oldver));
            if (isset($oldplus)) {
                $this->_oldTagVersionString .= '_' . $oldplus;
            }
            $this->_oldDirectoryName = strtolower($this->_options['module'] . '-' . $this->_oldHordeVersionString . $this->_oldSourceVersionString);
            $this->_oldDirectoryName = $this->_options['module'] . '-';
            if (!empty($this->_oldHordeVersionString)) {
                $this->_oldDirectoryName .= $this->_oldHordeVersionString . '-';
            }
            $this->_oldDirectoryName = strtolower($this->_oldDirectoryName . $this->_oldSourceVersionString);

            if (!empty($this->_oldHordeVersionString)) {
                $this->_oldSourceVersionString = $this->_oldHordeVersionString . ' (' . $this->_oldSourceVersionString . ')';
            }
        }

        // Set string to use for updating ticketing system.
        $this->_ticketVersion = implode('.', $ver);
        if (!empty($plus)) {
            $this->_ticketVersion .= '-' . $plus;
        }

        if (!empty($this->_hordeVersionString)) {
            $this->_ticketVersionDesc .= ' ' . $this->_hordeVersionString;
        }

        // Account for the 'special' case of the horde module.
        if ($this->_options['module'] == 'horde') {
            $this->_ticketVersionDesc .= ' ' . implode('.', $ver);
        } else {
            $this->_ticketVersionDesc .= ' ' . '(' . implode('.', $ver) . ')';
        }

        // See if we have a 'Final', 'Alpha', or 'RC' to add.
        if ($this->_latest) {
            $this->_ticketVersionDesc .= ' Final';
        } elseif (!empty($plus) &&
                  preg_match('/^RC(\d+)/', $plus, $matches)) {
            $this->_ticketVersionDesc .= ' Release Candidate ' . $matches[1];

        } elseif (!empty($plus) && strtolower($plus) == 'alpha') {
            $this->_ticketVersionDesc .= ' Alpha';
        }

        // set the name of the string to put into the source version file when
        // done
        if (!isset($plus)) {
            while (count($ver) < 3) {
                $ver[] = '0';
            }
            $ver[count($ver) - 1] += 1;
        }
        $this->_newSourceVersionString = implode('.', $ver) . '-cvs';
        $this->_newSourceVersionStringPlain = $this->_newSourceVersionString;

        if (!empty($this->_hordeVersionString)) {
            $this->_newSourceVersionString = $this->_hordeVersionString .
                ' (' . $this->_newSourceVersionString . ')';
        }

    }

    /**
     * Get all of the command-line arguments from the user
     */
    public function getArguments()
    {
        global $argv;

        // Parse the command-line arguments
        array_shift($argv);
        foreach ($argv as $arg) {
            // Check to see if they gave us a module
            if (preg_match('/--module=(.*)/', $arg, $matches)) {
                $this->_options['module'] = $matches[1];

            // Check to see if they tell us the version of the tarball to make
            } elseif (preg_match('/--version=(.*)/', $arg, $matches)) {
                $this->_options['version']= $matches[1];

            // Check to see if they tell us the last release version
            } elseif (preg_match('/--oldversion=(.*)/', $arg, $matches)) {
                $this->_options['oldversion']= $matches[1];

            // Check to see if they tell us which branch to work with
            } elseif (preg_match('/--branch=(.*)/', $arg, $matches)) {
                $this->_options['branch']= $matches[1];

            // Check to see if they tell us not to commit or tag
            } elseif (strstr($arg, '--nocommit')) {
                $this->_options['nocommit']= true;

            // Check to see if they tell us not to upload
            } elseif (strstr($arg, '--noftp')) {
                $this->_options['noftp']= true;

            // Check to see if they tell us not to announce
            } elseif (strstr($arg, '--noannounce')) {
                $this->_options['noannounce']= true;

            // Check to see if they tell us not to announce
            } elseif (strstr($arg, '--nofreshmeat')) {
                $this->_options['nofreshmeat']= true;

            // Check to see if they tell us not to add new ticket versions
            } elseif (strstr($arg, '--noticketversion')) {
                $this->_options['nowhups'] = true;

            // Check to see if they tell us to do a dry run
            } elseif (strstr($arg, '--dryrun')) {
                $this->_options['nocommit'] = true;
                $this->_options['noftp'] = true;
                $this->_options['noannounce'] = true;
                $this->_options['nowhups'] = true;
                $this->_options['nofreshmeat']= true;

            // Check to see if they tell us to test (for development only)
            } elseif (strstr($arg, '--test')) {
                $this->_options['test']= true;
                // safety first
                $this->_options['nocommit'] = true;
                $this->_options['noftp'] = true;
                $this->_options['noannounce'] = true;
                $this->_options['nowhups'] = true;
                $this->_options['nofreshmeat']= true;

            // Check for help usage.
            } elseif (strstr($arg, '--help')) {
                $this->print_usage();
                exit;

            // We have no idea what this is
            } else {
                $this->print_usage('You have used unknown arguments: ' . $arg);
                exit;
            }
        }
    }

    /**
     * Check the command-line arguments and set some internal defaults
     */
    public function checkArguments()
    {
        // Make sure that we have a module defined
        if (!isset($this->_options['module'])) {
            $this->print_usage('You must define which module to package.');
            exit;
        }

        // Let's make sure that there are valid version strings in here...
        if (!isset($this->_options['version'])) {
            $this->print_usage('You must define which version to package.');
            exit;
        }
        if (!preg_match('/\d+\.\d+.*/', $this->_options['version'])) {
            $this->print_usage('Incorrect version string.');
            exit;
        }
        if (!isset($this->_options['oldversion'])) {
            $this->print_usage('You must define last release\'s version.');
            exit;
        }
        if (!preg_match('/\d+(\.\d+.*)?/', $this->_options['oldversion'])) {
            $this->print_usage('Incorrect old version string.');
            exit;
        }

        // Make sure we have a horde.org user
        if (empty($this->_options['horde']['user'])) {
            $this->print_usage('You must define a horde.org user.');
            exit;
        }

        // If there is no branch defined, we're using the tip revisions.
        // These releases are always developmental, and should use the HEAD "branch" name.
        if (!isset($this->_options['branch'])) {
            $this->_options['branch'] = 'HEAD';
        }
    }

    /**
     * Check the command-line arguments and set some internal defaults
     */
    public function checkSetSystem()
    {
        // Set umask
        umask(022);
    }

    /**
     * Show people how to use the damned thing
     */
    public function print_usage($message = null)
    {
        if (!is_null($message)) {
            print "\n***  ERROR: $message  ***\n";
        }

        print <<<USAGE

make-release.php: Horde release generator.

   This script takes as arguments the module to make a release of, the
   version of the release, and the branch:

      horde-make-release.php --module=<name>
                         --version=[Hn-]xx.yy[.zz[-<string>]]
                         --oldversion=[Hn-]xx[.yy[.zz[-<string>]]]
                         [--branch=<branchname>] [--nocommit] [--noftp]
                         [--noannounce] [--nofreshmeat] [--noticketversion]
                         [--test] [--dryrun] [--help]

   If you omit the branch, it will implicitly work with the HEAD branch.
   If you release a new major version use the --oldversion=0 option.
   Use the --nocommit option to do a test build (without touching the CVS
   repository).
   Use the --noftp option to not upload any files on the FTP server.
   Use the --noannounce option to not send any release announcements.
   Use the --nofreshmeat option to not send any freshmeat announcements.
   Use the --noticketversion option to not update the version information on
   bugs.horde.org.
   The --dryrun option is an alias for:
     --nocommit --noftp --noannounce --nofreshmeat --noticketversion.
   The --test option is for debugging purposes only.

   EXAMPLES:

   To make a new development release of Horde:
      horde-make-release.php --module=horde --version=2.1-dev --oldversion=2.0

   To make a new stable release of Turba:
      horde-make-release.php --module=turba --version=H3-2.0.2 \
        --oldversion=H3-2.0.1 --branch=FRAMEWORK_3

   To make a new stable release of IMP 3:
      horde-make-release.php --module=imp --version=3.0 --oldversion=2.3.7 \
        --branch=RELENG_3

   To make a brand new Alpha/Beta/RC release of Luxor:
      horde-make-release.php --module=luxor --version=H3-1.0-ALPHA \
        --oldversion=0

USAGE;
    }

}
