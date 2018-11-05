<?php
include_once("/var/www/html/flight_api/stdlib.php");

class CreateAircraftTelemetry{

    // Overview information
    const AIRCRAFT_TABLE = 'aircrafts';
    const UUID = 'uuid';
    const AIRCRAFT_NAME = 'aircraft_name';
    const AIRCRAFT_SN = 'aircraft_sn';
    const COLUMN_AIRCRAFT_SERIAL_NUMBER = 'aircraft_serial_number';

    const AIRCRAFT_BATTERIES_TABLE = 'aircrafts_batteries';
    const AIRCRAFT_BATTERIES = 'batteries';
    const BATTERY_NAME = 'battery_name';
    const BATTERY_SN = 'battery_sn';
    const COLUMN_BATTERY_SERIAL_NUMBER = 'battery_serial_number';

    // Frames
    const FRAMES = 'frames';
    const TIMESTAMP = 'timestamp';
    const AIRCRAFT_UUID = 'aircraft_uuid';

    // Batteries
    const TABLE_BATTERY_FRAMES = 'battery_frames';
    const FRAME_BATTERY = 'battery';
    const BATTERY_PERCENT = 'battery_percent';
    const BATTERY_TEMPERATURE = 'battery_temperature';

    // GPS
    const TABLE_GPS_FRAMES = 'gps_frames';
    const FRAME_GPS = 'gps';
    const COLUMN_LATITUDE = 'latitude';
    const COLUMN_LONGITUDE = 'longitude';
    const COLUMN_ALTITUDE = 'altitude';
    const LAT = 'lat';
    const LONG = 'long';
    const ALT = 'alt';

    function __construct(){}

    function __destruct(){}

    public function processRequest($request){
        try{
            $database = new Database();

            // Check if uuid, aircraft_name, and aircraft_serial_number are provided
            if (array_key_exists(self::UUID, $request) && array_key_exists(self::AIRCRAFT_NAME, $request) && array_key_exists(self::AIRCRAFT_SN, $request)) {
                $aircraft_data_array = [self::UUID=>$request[self::UUID],
                    self::AIRCRAFT_NAME=>$request[self::AIRCRAFT_NAME],
                    self::COLUMN_AIRCRAFT_SERIAL_NUMBER=>$request[self::AIRCRAFT_SN],
                ];

                $aircraft_internal_id = $database->insert(self::AIRCRAFT_TABLE, $aircraft_data_array);
                $aircraft_uuid = $request[self::UUID];

                // Check if the overview information was inserted
                if ($aircraft_internal_id!=0 && count($aircraft_internal_id)>0) {
                    // Aircraft batteries
                    foreach ($request[self::AIRCRAFT_BATTERIES] as $battery) {
                        $aircraft_battery_data_array = [self::AIRCRAFT_UUID=>$aircraft_uuid,
                            self::BATTERY_NAME => $battery[self::BATTERY_NAME],
                            self::COLUMN_BATTERY_SERIAL_NUMBER => $battery[self::BATTERY_SN]
                        ];

                        $database->insert(self::AIRCRAFT_BATTERIES_TABLE, $aircraft_battery_data_array);
                    }
                    foreach ($request[self::FRAMES] as $frame) {
                        // Looping through frames
                        switch ($frame['type']) {
                            case self::FRAME_BATTERY:
                                $battery_data_array = [self::AIRCRAFT_UUID=>$aircraft_uuid,
                                    self::TIMESTAMP=>$frame[self::TIMESTAMP],
                                    self::COLUMN_BATTERY_SERIAL_NUMBER=>$frame[self::BATTERY_SN],
                                    self::BATTERY_PERCENT => $frame[self::BATTERY_PERCENT],
                                    self::BATTERY_TEMPERATURE => $frame[self::BATTERY_TEMPERATURE]];

                                $database->insert(self::TABLE_BATTERY_FRAMES, $battery_data_array);
                            case self::FRAME_GPS:
                                $gps_data_array = [self::AIRCRAFT_UUID=>$aircraft_uuid,
                                    self::TIMESTAMP=>$frame[self::TIMESTAMP],
                                    self::COLUMN_LATITUDE=>$frame[self::LAT],
                                    self::COLUMN_LONGITUDE=>$frame[self::LONG],
                                    self::COLUMN_ALTITUDE=>$frame[self::ALT]];

                                $database->insert(self::TABLE_GPS_FRAMES, $gps_data_array);
                            default:
                                break;
                        }
                    }
                    echo $aircraft_internal_id;
                } else {
                    (new ApiException('Bad Request', 400))->getException();
                }
            } else {
                (new ApiException('Bad Request', 400))->getException();
            }
        } catch(Exception $exception) {
            (new ApiException('Bad Request' . $exception, 400))->getException();
        }
    }
}