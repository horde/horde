<?php
/**
 * Migration to move from retired NOAA dataset to the station list provided by
 * https://github.com/datasets/airport-codes.
 */
class HordeServiceWeatherAirportsChange extends Horde_Db_Migration_Base
{
    protected $_handle;

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
        if (file_exists($file_name)) {
            $this->_handle = @fopen($file_name, 'rb');
        } else {
            $file_location = 'https://raw.githubusercontent.com/datasets/airport-codes/master/data/airport-codes.csv';
            $this->_handle = @fopen($file_location, 'rb');
        }
        if (!$this->_handle) {
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
        // Pop the first line off, which contains field names.
        fgetcsv($this->_handle);
        while (($data = fgetcsv($this->_handle)) !== false) {
            if (sizeof($data) < 13) {
                continue;
            }
            try {
                $this->_connection->insert($insert, array(
                    $line,
                    $data[0],
                    $data[2],
                    str_replace($data[7] . '-', '', $data[8]),
                    $data[7],
                    $data[9],
                    !empty($data[3]) ? round($data[3], 4) : 0,
                    !empty($data[4]) ? round($data[4], 4) : 0,
                    !empty($data[5]) ? $data[5] : 0)
                );
            } catch (Horde_Db_Exception $e) {
                $this->announce('ERROR: ' . $e->getMessage());
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
