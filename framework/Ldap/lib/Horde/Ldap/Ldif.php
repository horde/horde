<?php
/**
 * LDIF capabilities for Horde_Ldap.
 *
 * This class provides a means to convert between Horde_Ldap_Entry objects and
 * LDAP entries represented in LDIF format files. Reading and writing are
 * supported and manipulating of single entries or lists of entries.
 *
 * Usage example:
 * <code>
 * // Read and parse an LDIF file into Horde_Ldap_Entry objects
 * // and print out the DNs. Store the entries for later use.
 * $entries = array();
 * $ldif = new Horde_Ldap_Ldif('test.ldif', 'r', $options);
 * do {
 *     $entry = $ldif->readEntry();
 *     $dn    = $entry->dn();
 *     echo " done building entry: $dn\n";
 *     array_push($entries, $entry);
 * } while (!$ldif->eof());
 * $ldif->done();
 *
 * // Write those entries to another file
 * $ldif = new Horde_Ldap_Ldif('test.out.ldif', 'w', $options);
 * $ldif->writeEntry($entries);
 * $ldif->done();
 * </code>
 *
 * @category  Horde
 * @package   Ldap
 * @author    Benedikt Hallinger <beni@php.net>
 * @author    Jan Schneider <jan@horde.org>
 * @copyright 2009 Benedikt Hallinger
 * @copyright 2010 The Horde Project
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL
 * @see       http://www.ietf.org/rfc/rfc2849.txt
 * @todo      LDAPv3 controls are not implemented yet
 */
class Horde_Ldap_Ldif
{
    /**
     * Options.
     *
     * @var array
     */
    protected $_options = array('encode'    => 'base64',
                                'change'    => false,
                                'lowercase' => false,
                                'sort'      => false,
                                'version'   => null,
                                'wrap'      => 78,
                                'raw'       => '');

    /**
     * File handle for read/write.
     *
     * @var resource
     */
    protected $_fh;

    /**
     * Whether we opened the file handle ourselves.
     *
     * @var boolean
     */
    protected $_fhOpened = false;

    /**
     * Line counter for input file handle.
     *
     * @var integer
     */
    protected $_inputLine = 0;

    /**
     * Counter for processed entries.
     *
     * @var integer
     */
    protected $_entrynum = 0;

    /**
     * Mode we are working in.
     *
     * Either 'r', 'a' or 'w'
     *
     * @var string
     */
    protected $_mode;

    /**
     * Whether the LDIF version string was already written.
     *
     * @var boolean
     */
    protected $_versionWritten = false;

    /**
     * Cache for lines that have built the current entry.
     *
     * @var array
     */
    protected $_linesCur = array();

    /**
     * Cache for lines that will build the next entry.
     *
     * @var array
     */
    protected $_linesNext = array();

    /**
     * Constructor.
     *
     * Opens an LDIF file for reading or writing.
     *
     * $options is an associative array and may contain:
     * - 'encode' (string): Some DN values in LDIF cannot be written verbatim
     *                      and have to be encoded in some way. Possible
     *                      values:
     *                      - 'none':      No encoding.
     *                      - 'canonical': See {@link
     *                                     Horde_Ldap_Util::canonical_dn()}.
     *                      - 'base64':    Use base64 (default).
     * - 'change' (boolean): Write entry changes to the LDIF file instead of
     *                       the entries itself. I.e. write LDAP operations
     *                       acting on the entries to the file instead of the
     *                       entries contents.  This writes the changes usually
     *                       carried out by an update() to the LDIF
     *                       file. Defaults to false.
     * - 'lowercase' (boolean): Convert attribute names to lowercase when
     *                          writing. Defaults to false.
     * - 'sort' (boolean): Sort attribute names when writing entries according
     *                     to the rule: objectclass first then all other
     *                     attributes alphabetically sorted by attribute
     *                     name. Defaults to false.
     * - 'version' (integer): Set the LDIF version to write to the resulting
     *                        LDIF file. According to RFC 2849 currently the
     *                        only legal value for this option is 1. When this
     *                        option is set Horde_Ldap_Ldif tries to adhere
     *                        more strictly to the LDIF specification in
     *                        RFC2489 in a few places. The default is null
     *                        meaning no version information is written to the
     *                        LDIF file.
     * - 'wrap' (integer): Number of columns where output line wrapping shall
     *                     occur.  Default is 78. Setting it to 40 or lower
     *                     inhibits wrapping.
     * - 'raw' (string): Regular expression to denote the names of attributes
     *                   that are to be considered binary in search results if
     *                   writing entries.  Example: 'raw' =>
     *                   '/(?i:^jpegPhoto|;binary)/i'
     *
     * @param string|ressource $file    Filename or file handle.
     * @param string           $mode    Mode to open the file, either 'r', 'w'
     *                                  or 'a'.
     * @param array            $options Options like described above.
     *
     * @throws Horde_Ldap_Exception
     */
    public function __construct($file, $mode = 'r', $options = array())
    {
        // Parse options.
        foreach ($options as $option => $value) {
            if (!array_key_exists($option, $this->_options)) {
                throw new Horde_Ldap_Exception('Option ' . $option . ' not known');
            }
            $this->_options[$option] = Horde_String::lower($value);
        }

        // Set version.
        $this->version($this->_options['version']);

        // Setup file mode.
        if (!preg_match('/^[rwa]$/', $mode)) {
            throw new Horde_Ldap_Exception('File mode ' . $mode . ' not supported');
        }
        $this->_mode = $mode;

        // Setup file handle.
        if (is_resource($file)) {
            // TODO: checks on mode possible?
            $this->_fh = $file;
            return;
        }

        switch ($mode) {
        case 'r':
            if (!file_exists($file)) {
                throw new Horde_Ldap_Exception('Unable to open ' . $file . ' for reading: file not found');
            }
            if (!is_readable($file)) {
                throw new Horde_Ldap_Exception('Unable to open ' . $file . ' for reading: permission denied');
            }
            break;

        case 'w':
        case 'a':
            if (file_exists($file)) {
                if (!is_writable($file)) {
                    throw new Horde_Ldap_Exception('Unable to open ' . $file . ' for writing: permission denied');
                }
            } else {
                if (!@touch($file)) {
                    throw new Horde_Ldap_Exception('Unable to create ' . $file . ' for writing: permission denied');
                }
            }
            break;
        }

        $this->_fh = @fopen($file, $this->_mode);
        if (!$this->_fh) {
            throw new Horde_Ldap_Exception('Could not open file ' . $file);
        }

        $this->_fhOpened = true;
    }

    /**
     * Reads one entry from the file and return it as a Horde_Ldap_Entry
     * object.
     *
     * @return Horde_Ldap_Entry
     * @throws Horde_Ldap_Exception
     */
    public function readEntry()
    {
        // Read fresh lines, set them as current lines and create the entry.
        $attrs = $this->nextLines(true);
        if (count($attrs)) {
            $this->_linesCur = $attrs;
        }
        return $this->currentEntry();
    }

    /**
     * Returns true when the end of the file is reached.
     *
     * @return boolean
     */
    public function eof()
    {
        return feof($this->_fh);
    }

    /**
     * Writes the entry or entries to the LDIF file.
     *
     * If you want to build an LDIF file containing several entries AND you
     * want to call writeEntry() several times, you must open the file handle
     * in append mode ('a'), otherwise you will always get the last entry only.
     *
     * @todo Implement operations on whole entries (adding a whole entry).
     *
     * @param Horde_Ldap_Entry|array $entries Entry or array of entries.
     *
     * @throws Horde_Ldap_Exception
     */
    public function writeEntry($entries)
    {
        if (!is_array($entries)) {
            $entries = array($entries);
        }

        foreach ($entries as $entry) {
            $this->_entrynum++;
            if (!($entry instanceof Horde_Ldap_Entry)) {
                throw new Horde_Ldap_Exception('Entry ' . $this->_entrynum . ' is not an Horde_Ldap_Entry object');
            }

            if ($this->_options['change']) {
                $this->_changeEntry($entry);
            } else {
                $this->_writeEntry($entry);
            }
        }
    }

    /**
     * Writes an LDIF file that describes an entry change.
     *
     * @param Horde_Ldap_Entry $entry
     *
     * @throws Horde_Ldap_Exception
     */
    protected function _changeEntry($entry)
    {
        // Fetch change information from entry.
        $entry_attrs_changes = $entry->getChanges();
        $num_of_changes = count($entry_attrs_changes['add'])
                        + count($entry_attrs_changes['replace'])
                        + count($entry_attrs_changes['delete']);

        $is_changed = $num_of_changes > 0 || $entry->willBeDeleted() || $entry->willBeMoved();

        // Write version if not done yet, also write DN of entry.
        if ($is_changed) {
            if (!$this->_versionWritten) {
                $this->writeVersion();
            }
            $this->_writeDN($entry->currentDN());
        }

        // Process changes.
        // TODO: consider DN add!
        if ($entry->willBeDeleted()) {
            $this->_writeLine('changetype: delete');
        } elseif ($entry->willBeMoved()) {
            $this->_writeLine('changetype: modrdn');
            $olddn     = Horde_Ldap_Util::ldap_explode_dn($entry->currentDN(), array('casefold' => 'none'));
            $oldrdn    = array_shift($olddn);
            $oldparent = implode(',', $olddn);
            $newdn     = Horde_Ldap_Util::ldap_explode_dn($entry->dn(), array('casefold' => 'none'));
            $rdn       = array_shift($newdn);
            $parent    = implode(',', $newdn);
            $this->_writeLine('newrdn: ' . $rdn);
            $this->_writeLine('deleteoldrdn: 1');
            if ($parent !== $oldparent) {
                $this->_writeLine('newsuperior: ' . $parent);
            }
            // TODO: What if the entry has attribute changes as well?
            //       I think we should check for that and make a dummy
            //       entry with the changes that is written to the LDIF file.
        } elseif ($num_of_changes > 0) {
            // Write attribute change data.
            $this->_writeLine('changetype: modify');
            foreach ($entry_attrs_changes as $changetype => $entry_attrs) {
                foreach ($entry_attrs as $attr_name => $attr_values) {
                    $this->_writeLine("$changetype: $attr_name");
                    if ($attr_values !== null) {
                        $this->_writeAttribute($attr_name, $attr_values, $changetype);
                    }
                    $this->_writeLine('-');
                }
            }
        }

        // Finish this entry's data if we had changes.
        if ($is_changed) {
            $this->_finishEntry();
        }
    }

    /**
     * Writes an LDIF file that describes an entry.
     *
     * @param Horde_Ldap_Entry $entry
     *
     * @throws Horde_Ldap_Exception
     */
    protected function _writeEntry($entry)
    {
        // Fetch attributes for further processing.
        $entry_attrs = $entry->getValues();

        // Sort and put objectclass attributes to first position.
        if ($this->_options['sort']) {
            ksort($entry_attrs);
            if (isset($entry_attrs['objectclass'])) {
                $oc = $entry_attrs['objectclass'];
                unset($entry_attrs['objectclass']);
                $entry_attrs = array_merge(array('objectclass' => $oc), $entry_attrs);
            }
        }

        // Write data.
        if (!$this->_versionWritten) {
            $this->writeVersion();
        }
        $this->_writeDN($entry->dn());
        foreach ($entry_attrs as $attr_name => $attr_values) {
            $this->_writeAttribute($attr_name, $attr_values);
        }
        $this->_finishEntry();
    }

    /**
     * Writes the version to LDIF.
     *
     * If the object's version is defined, this method allows to explicitely
     * write the version before an entry is written.
     *
     * If not called explicitely, it gets called automatically when writing the
     * first entry.
     *
     * @throws Horde_Ldap_Exception
     */
    public function writeVersion()
    {
        if (!is_null($this->version())) {
            $this->_writeLine('version: ' . $this->version(), 'Unable to write version');
        }
        $this->_versionWritten = true;
    }

    /**
     * Returns or sets the LDIF version.
     *
     * If called with an argument it sets the LDIF version. According to RFC
     * 2849 currently the only legal value for the version is 1.
     *
     * @param integer $version LDIF version to set.
     *
     * @return integer The current or new version.
     * @throws Horde_Ldap_Exception
     */
    public function version($version = null)
    {
        if ($version !== null) {
            if ($version != 1) {
                throw new Horde_Ldap_Exception('Illegal LDIF version set');
            }
            $this->_options['version'] = $version;
        }
        return $this->_options['version'];
    }

    /**
     * Returns the file handle the Horde_Ldap_Ldif object reads from or writes
     * to.
     *
     * You can, for example, use this to fetch the content of the LDIF file
     * manually.
     *
     * @return resource
     * @throws Horde_Ldap_Exception
     */
    public function handle()
    {
        if (!is_resource($this->_fh)) {
            throw new Horde_Ldap_Exception('Invalid file resource');
        }
        return $this->_fh;
    }

    /**
     * Cleans up.
     *
     * This method signals that the LDIF object is no longer needed. You can
     * use this to free up some memory and close the file handle. The file
     * handle is only closed, if it was opened from Horde_Ldap_Ldif.
     *
     * @throws Horde_Ldap_Exception
     */
    public function done()
    {
        // Close file handle if we opened it.
        if ($this->_fhOpened) {
            fclose($this->handle());
        }

        // Free variables.
        foreach (get_object_vars($this) as $name => $value) {
            unset($this->$name);
        }
    }

    /**
     * Returns the current Horde_Ldap_Entry object.
     *
     * @return Horde_Ldap_Entry
     * @throws Horde_Ldap_Exception
     */
    public function currentEntry()
    {
        return $this->parseLines($this->currentLines());
    }

    /**
     * Parse LDIF lines of one entry into an Horde_Ldap_Entry object.
     *
     * @todo what about file inclusions and urls?
     *       "jpegphoto:< file:///usr/local/directory/photos/fiona.jpg"
     *
     * @param array $lines LDIF lines for one entry.
     *
     * @return Horde_Ldap_Entry Horde_Ldap_Entry object for those lines.
     * @throws Horde_Ldap_Exception
     */
    public function parseLines($lines)
    {
        // Parse lines into an array of attributes and build the entry.
        $attributes = array();
        $dn = false;
        foreach ($lines as $line) {
            if (!preg_match('/^(\w+)(:|::|:<)\s(.+)$/', $line, $matches)) {
                // Line not in "attr: value" format -> ignore.  Maybe we should
                // rise an error here, but this should be covered by
                // nextLines() already. A problem arises, if users try to feed
                // data of several entries to this method - the resulting entry
                // will get wrong attributes. However, this is already
                // mentioned in the method documentation above.
                continue;
            }

            $attr  = $matches[1];
            $delim = $matches[2];
            $data  = $matches[3];

            switch ($delim) {
            case ':':
                // Normal data.
                $attributes[$attr][] = $data;
                break;
            case '::':
                // Base64 data.
                $attributes[$attr][] = base64_decode($data);
                break;
            case ':<':
                // File inclusion
                // TODO: Is this the job of the LDAP-client or the server?
                throw new Horde_Ldap_Exception('File inclusions are currently not supported');
            default:
                throw new Horde_Ldap_Exception('Parsing error: invalid syntax at parsing entry line: ' . $line);
            }

            if (Horde_String::lower($attr) == 'dn') {
                // DN line detected. Save possibly decoded DN.
                $dn = $attributes[$attr][0];
                // Remove wrongly added "dn: " attribute.
                unset($attributes[$attr]);
            }
        }

        if (!$dn) {
            throw new Horde_Ldap_Exception('Parsing error: unable to detect DN for entry');
        }

        return Horde_Ldap_Entry::createFresh($dn, $attributes);
    }

    /**
     * Returns the lines that generated the current Horde_Ldap_Entry object.
     *
     * Returns an empty array if no lines have been read so far.
     *
     * @return array Array of lines.
     */
    public function currentLines()
    {
        return $this->_linesCur;
    }

    /**
     * Returns the lines that will generate the next Horde_Ldap_Entry object.
     *
     * If you set $force to true you can iterate over the lines that build up
     * entries manually. Otherwise, iterating is done using {@link
     * readEntry()}. $force will move the file pointer forward, thus returning
     * the next entry lines.
     *
     * Wrapped lines will be unwrapped. Comments are stripped.
     *
     * @param boolean $force Set this to true if you want to iterate over the
     *                       lines manually
     *
     * @return array
     * @throws Horde_Ldap_Exception
     */
    public function nextLines($force = false)
    {
        // If we already have those lines, just return them, otherwise read.
        if (count($this->_linesNext) == 0 || $force) {
            // Empty in case something was left (if used $force).
            $this->_linesNext = array();
            $entry_done       = false;
            $fh               = $this->handle();
            // Are we in an comment? For wrapping purposes.
            $commentmode      = false;
            // How many lines with data we have read?
            $datalines_read   = 0;

            while (!$entry_done && !$this->eof()) {
                $this->_inputLine++;
                // Read line. Remove line endings, we want only data; this is
                // okay since ending spaces should be encoded.
                $data = rtrim(fgets($fh));
                if ($data === false) {
                    // Error only, if EOF not reached after fgets() call.
                    if (!$this->eof()) {
                        throw new Horde_Ldap_Exception('Error reading from file at input line ' . $this->_inputLine);
                    }
                    break;
                }

                if (count($this->_linesNext) > 0 && preg_match('/^$/', $data)) {
                    // Entry is finished if we have an empty line after we had
                    // data.
                    $entry_done = true;

                    // Look ahead if the next EOF is nearby. Comments and empty
                    // lines at the file end may cause problems otherwise.
                    $current_pos = ftell($fh);
                    $data        = fgets($fh);
                    while (!feof($fh)) {
                        if (preg_match('/^\s*$/', $data) ||
                            preg_match('/^#/', $data)) {
                            // Only empty lines or comments, continue to seek.
                            // TODO: Known bug: Wrappings for comments are okay
                            //       but are treaten as error, since we do not
                            //       honor comment mode here.  This should be a
                            //       very theoretically case, however I am
                            //       willing to fix this if really necessary.
                            $this->_inputLine++;
                            $current_pos = ftell($fh);
                            $data        = fgets($fh);
                        } else {
                            // Data found if non emtpy line and not a comment!!
                            // Rewind to position prior last read and stop
                            // lookahead.
                            fseek($fh, $current_pos);
                            break;
                        }
                    }
                    // Now we have either the file pointer at the beginning of
                    // a new data position or at the end of file causing feof()
                    // to return true.
                    continue;
                }

                // Build lines.
                if (preg_match('/^version:\s(.+)$/', $data, $match)) {
                    // Version statement, set version.
                    $this->version($match[1]);
                } elseif (preg_match('/^\w+::?\s.+$/', $data)) {
                    // Normal attribute: add line.
                    $commentmode        = false;
                    $this->_linesNext[] = trim($data);
                    $datalines_read++;
                } elseif (preg_match('/^\s(.+)$/', $data, $matches)) {
                    // Wrapped data: unwrap if not in comment mode.
                    if (!$commentmode) {
                        if ($datalines_read == 0) {
                            // First line of entry: wrapped data is illegal.
                            throw new Horde_Ldap_Exception('Illegal wrapping at input line ' . $this->_inputLine);
                        }
                        $this->_linesNext[] = array_pop($this->_linesNext) . trim($matches[1]);
                        $datalines_read++;
                    }
                } elseif (preg_match('/^#/', $data)) {
                    // LDIF comments.
                    $commentmode = true;
                } elseif (preg_match('/^\s*$/', $data)) {
                    // Empty line but we had no data for this entry, so just
                    // ignore this line.
                    $commentmode = false;
                } else {
                    throw new Horde_Ldap_Exception('Invalid syntax at input line ' . $this->_inputLine);
                }
            }
        }

        return $this->_linesNext;
    }

    /**
     * Converts an attribute and value to LDIF string representation.
     *
     * It honors correct encoding of values according to RFC 2849. Line
     * wrapping will occur at the configured maximum but only if the value is
     * greater than 40 chars.
     *
     * @param string $attr_name  Name of the attribute.
     * @param string $attr_value Value of the attribute.
     *
     * @return string LDIF string for that attribute and value.
     */
    protected function _convertAttribute($attr_name, $attr_value)
    {
        // Handle empty attribute or process.
        if (!strlen($attr_value)) {
            return $attr_name.':  ';
        }

        // If converting is needed, do it.
        // Either we have some special chars or a matching "raw" regex
        if ($this->_isBinary($attr_value) ||
            ($this->_options['raw'] &&
             preg_match($this->_options['raw'], $attr_name))) {
            $attr_name .= ':';
            $attr_value = base64_encode($attr_value);
        }

        // Lowercase attribute names if requested.
        if ($this->_options['lowercase']) {
            $attr_name = Horde_String::lower($attr_name);
        }

        // Handle line wrapping.
        if ($this->_options['wrap'] > 40 &&
            strlen($attr_value) > $this->_options['wrap']) {
            $attr_value = wordwrap($attr_value, $this->_options['wrap'], PHP_EOL . ' ', true);
        }

        return $attr_name . ': ' . $attr_value;
    }

    /**
     * Converts an entry's DN to LDIF string representation.
     *
     * It honors correct encoding of values according to RFC 2849.
     *
     * @todo I am not sure, if the UTF8 stuff is correctly handled right now
     *
     * @param string $dn UTF8 encoded DN.
     *
     * @return string LDIF string for that DN.
     */
    protected function _convertDN($dn)
    {
        // If converting is needed, do it.
        return $this->_isBinary($dn)
            ? 'dn:: ' . base64_encode($dn)
            : 'dn: ' . $dn;
    }

    /**
     * Returns whether some data is considered binary and must be
     * base64-encoded.
     *
     * @param string $value  Some data.
     *
     * @return boolean  True if the data should be encoded.
     */
    protected function _isBinary($value)
    {
        $binary = false;

        // ASCII-chars that are NOT safe for the start and for being inside the
        // value. These are the integer values of those chars.
        $unsafe_init = array(0, 10, 13, 32, 58, 60);
        $unsafe      = array(0, 10, 13);

        // Test for illegal init char.
        $init_ord = ord(substr($value, 0, 1));
        if ($init_ord > 127 || in_array($init_ord, $unsafe_init)) {
            $binary = true;
        }

        // Test for illegal content char.
        for ($i = 0; $i < strlen($value); $i++) {
            $char_ord = ord(substr($value, $i, 1));
            if ($char_ord >= 127 || in_array($char_ord, $unsafe)) {
                $binary = true;
            }
        }

        // Test for ending space
        if (substr($value, -1) == ' ') {
            $binary = true;
        }

        return $binary;
    }

    /**
     * Writes an attribute to the file handle.
     *
     * @param string       $attr_name   Name of the attribute.
     * @param string|array $attr_values Single attribute value or array with
     *                                  attribute values.
     *
     * @throws Horde_Ldap_Exception
     */
    protected function _writeAttribute($attr_name, $attr_values)
    {
        // Write out attribute content.
        if (!is_array($attr_values)) {
            $attr_values = array($attr_values);
        }
        foreach ($attr_values as $attr_val) {
            $line = $this->_convertAttribute($attr_name, $attr_val);
            $this->_writeLine($line, 'Unable to write attribute ' . $attr_name . ' of entry ' . $this->_entrynum);
        }
    }

    /**
     * Writes a DN to the file handle.
     *
     * @param string $dn DN to write.
     *
     * @throws Horde_Ldap_Exception
     */
    protected function _writeDN($dn)
    {
        // Prepare DN.
        if ($this->_options['encode'] == 'base64') {
            $dn = $this->_convertDN($dn);
        } elseif ($this->_options['encode'] == 'canonical') {
            $dn = Horde_Ldap_Util::canonical_dn($dn, array('casefold' => 'none'));
        }
        $this->_writeLine($dn, 'Unable to write DN of entry ' . $this->_entrynum);
    }

    /**
     * Finishes an LDIF entry.
     *
     * @throws Horde_Ldap_Exception
     */
    protected function _finishEntry()
    {
        $this->_writeLine('', 'Unable to close entry ' . $this->_entrynum);
    }

    /**
     * Writes an arbitary line to the file handle.
     *
     * @param string $line  Content to write.
     * @param string $error If error occurs, throw this exception message.
     *
     * @throws Horde_Ldap_Exception
     */
    protected function _writeLine($line, $error = 'Unable to write to file handle')
    {
        $line .= PHP_EOL;
        if (is_resource($this->handle()) &&
            fwrite($this->handle(), $line, strlen($line)) === false) {
            throw new Horde_Ldap_Exception($error);
        }
    }
}
