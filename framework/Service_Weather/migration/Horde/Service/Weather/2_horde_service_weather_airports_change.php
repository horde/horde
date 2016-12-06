<?php
/**
 * Migration to move from retired NOAA dataset to the station list provided by
 * https://github.com/datasets/airport-codes.
 */
class HordeServiceWeatherAirportsChange extends Horde_Db_Migration_Base
{
    protected $_fileContents;

    public function up()
    {
        // Check for the file before attempting to populate the table. Will
        // save us from having to rollback the transaction.
        if (!$this->_checkForMetarData()) {
            throw new Horde_Exception('Unable to locate METAR data.');
        }

        // Get rid of the old data.
        $this->down();
        $this->beginDbTransaction();
        $t = $this->createTable('horde_metar_airports', array('autoincrementKey' => array('id')));
        $t->column('id', 'integer');
        $t->column('icao', 'string', array('limit' => 4));
        $t->column('name', 'string', array('limit' => 80));
        $t->column('state', 'string', array('limit' => 4));
        $t->column('country', 'string', array('limit' => 50));
        $t->column('municipality', 'string', array('limit' => 80));
        $t->column('latitude', 'float', array('default' => 0));
        $t->column('longitude', 'float', array('default' => 0));
        $t->column('elevation', 'float', array('default' => 0));
        $t->end();
        $this->_populateTable();
        $this->commitDbTransaction();
    }

    protected function _checkForMetarData()
    {
        // First see if we have a local copy in the same directory.
        $file_name = __DIR__ . DIRECTORY_SEPARATOR . 'airport-codes.csv';
        $file_location = 'https://raw.githubusercontent.com/datasets/airport-codes/master/data/airport-codes.csv';
        if (file_exists($file_name)) {
            $this->_fileContents = file($file_name);
        } else {
            $this->_fileContents = file($file_location);
        }
        if (empty($this->_fileContents)) {
             $this->announce('ERROR: Unable to populate METAR database.');
             return false;
        }

        return true;
    }

    protected function _populateTable()
    {
        $line = 0;
        $insert = 'INSERT INTO horde_metar_airports '
            . '(id, icao, name, state, country, municipality, latitude,'
            . 'longitude, elevation) '
            . 'VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)';

        /**
         * ident,
         * type,
         * name,
         * latitude_deg,
         * longitude_deg,
         * elevation_ft,
         * continent,
         * iso_country,
         * iso_region,
         * municipality,
         * gps_code,
         * iata_code,
         * local_code
         */

        // Using array_map('str_getcsv', file($file_location)) leads to memory
        // exhaustion on my dev boxes, so iterate to be safe.
        for ($i = 1; $i <= count($this->_fileContents) + 1; $i++) {
            $fields = str_getcsv(trim($this->_fileContents[$i]));
            // Minimum field count. Continue since this is probably a comment
            // or some other incomplete data.
            if (sizeof($fields) < 13) {
                continue;
            }
            $data = array(
                $line,
                $fields[0],
                $fields[2],
                str_replace($fields[7] . '-', '', $fields[8]),
                $fields[7],
                $fields[9],
                !empty($fields[3]) ? round($fields[3], 4) : 0,
                !empty($fields[4]) ? round($fields[4], 4) : 0,
                !empty($fields[5]) ? $fields[5] : 0
            );
            // Only add lines that have a valid ICAO identifier. The dataset
            // seems to have a number of entries with broken identifiers. E.g.,
            // Corydon airport.
            if (strlen(trim($data[1])) > 4) {
                continue;
            }
            try {
                $this->_connection->insert($insert, $data);
            } catch (Horde_Db_Exception $e) {
                $this->announce('ERROR: ' . $e->getMessage());
                $this->announce('SQL: ' . $insert . ' with the following data: ' . print_r($data, true));
                $this->rollbackDbTransaction();
                return;
            }
            $line++;
        }
        $this->announce('Added ' . ($line) . ' airport identifiers to the database.', 'cli.message');
    }

    public function down()
    {
        $tableList = $this->tables();
        if (in_array('horde_metar_airports', $tableList)) {
            $this->dropTable('horde_metar_airports');
        }
    }

}
