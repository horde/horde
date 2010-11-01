<?php
/**
 * $Horde: luxor/lib/Tagger.php,v 1.9 2008/08/01 21:09:31 chuck Exp $
 *
 * @author  Jan Schneider <jan@horde.org>
 * @since   Luxor 0.1
 * @package Luxor
 */
class Luxor_Tagger {

    /**
     * Indexes a file.
     * Parses the files contents for symbols and creates indexes in the
     * storage backend for the file itself and the symbols it defines.
     *
     * @param Luxor_Driver $files   An instance of a storage backend driver.
     * @param string $pathname      The (relative) pathname of the file to
     *                              be processed.
     * @param Luxor_Lang $lang      The language object for $pathname.
     *
     * @return mixed                A PEAR_Error if an error occured.
     */
    function processFile($files, $pathname, $lang)
    {
        global $index;

        /* Get the unique ID for this file. */
        $fileId = $index->fileId($pathname);
        if ($fileId === false) {
            $fileId = $index->createFileId($pathname, '', $files->getFiletime($pathname));
        } elseif (is_a($fileId, 'PEAR_Error')) {
            return $fileId;
        }

        /* Update the file's status. */
        $result = $index->toIndex($fileId);
        if ($result === false) {
            return PEAR::raiseError(sprintf(_("%s was already indexed."), $pathname));
        } elseif (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        /* Empty symbol cache. */
        $index->clearCache();

        /* Find symbols defined by this file. */
        $path = $files->tmpFile($pathname);
        if (!$path) {
            return PEAR::raiseError(sprintf(_("Can't create copy of file %s."), $pathname));
        }
        $result = $lang->indexFile($path, $fileId);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }
    }

    /**
     * References a file.
     * Parses the files contents for symbols and creates references to the
     * files where these symbols are defined.
     *
     * @param Luxor_Driver $files   An instance of a storage backend driver.
     * @param string $pathname      The (relative) pathname of the file to
     *                              be referenced.
     * @param Luxor_Lang $lang      The language object for $pathname.
     *
     * @return mixed                A PEAR_Error if an error occured.
     */
    function processRefs($files, $pathname, $lang)
    {
        global $index;

        /* Get the unique ID for this file. */
        $fileId = $index->fileId($pathname);
        if ($fileId === false) {
            $fileId = $index->createFileId($pathname, '', $files->getFiletime($pathname));
        }
        if (is_a($fileId, 'PEAR_Error')) {
            return $fileId;
        }

        /* Update the file's status. */
        $result = $index->toReference($fileId);
        if ($result === false) {
            return PEAR::raiseError(sprintf(_("%s was already indexed."), $pathname));
        } elseif (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        /* Empty symbol cache. */
        $index->clearCache();

        /* Create references to symbol definitions. */
        $path = $files->tmpFile($pathname);
        if (!$path) {
            return PEAR::raiseError(sprintf(_("Can't create copy of file %s."), $pathname));
        }
        $result = $lang->referenceFile($path, $fileId);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }
    }

}
