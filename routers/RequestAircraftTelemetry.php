<?php
include_once("/var/www/html/flight_api/stdlib.php");

class RequestAircraftTelemetry{
    function __construct(){}

    function __destruct(){}

    // List flights
    public function getListFlights(){
        try{
            $database = new Database();

            $join_aircraft_batteries = ' LEFT JOIN '.CreateAircraftTelemetry::AIRCRAFT_BATTERIES_TABLE. ' ON aircrafts.uuid = '.CreateAircraftTelemetry::AIRCRAFT_BATTERIES_TABLE.'.'.CreateAircraftTelemetry::AIRCRAFT_UUID;
            $select_home_point = ', (SELECT latitude, longitude from gps_frames ORDER BY gps_frame_id ASC LIMIT 1) AS home_point';
            $select_duration = ', (SELECT SUM(timestamp)/1000 AS duration from gps_frames) AS duration';
            $aircraft_lists = $database->select(CreateAircraftTelemetry::AIRCRAFT_TABLE.$join_aircraft_batteries.$select_home_point.$select_duration);

            if($aircraft_lists!=0 && $aircraft_lists>0) {
                $result = [];
                foreach ($aircraft_lists as $list) {
                    $result[] = [
                        'UUID' => $list[CreateAircraftTelemetry::UUID],
                        'HomePoint' => $list[CreateAircraftTelemetry::COLUMN_LATITUDE].', '.$list[CreateAircraftTelemetry::COLUMN_LONGITUDE],
                        'FlightDuration' => $list['duration'],
                        'AircraftNameAndSerialNumber' => $list[CreateAircraftTelemetry::AIRCRAFT_NAME] .' : '. $list[CreateAircraftTelemetry::COLUMN_AIRCRAFT_SERIAL_NUMBER],
                        'BatteriesNameAndSerialNumber' => $list[CreateAircraftTelemetry::BATTERY_NAME] .' : '. $list[CreateAircraftTelemetry::COLUMN_BATTERY_SERIAL_NUMBER],
                    ];
                }

                header('Content-Type: application/json');
                echo json_encode($result);
            }

        } catch(Exception $exception) {
            (new ApiException('Bad Request' . $exception, 400))->getException();
        }
    }

    // Flight details
    public function getFlightDetails($path_info){
        try{
            $aircraft_id = substr($path_info, 9); // For aircraft details. Get the id

            $database = new Database();

            if($aircraft_id!='') {
                $fields = 'uuid, aircraft_name, aircraft_serial_number, battery_temperature, battery_percent, latitude, longitude';
                $join_battery_frames = ' LEFT JOIN ' . CreateAircraftTelemetry::TABLE_BATTERY_FRAMES . ' ON aircrafts.uuid = ' . CreateAircraftTelemetry::TABLE_BATTERY_FRAMES . '.' . CreateAircraftTelemetry::AIRCRAFT_UUID;
                $join_gps_frames = ' LEFT JOIN ' . CreateAircraftTelemetry::TABLE_GPS_FRAMES . ' ON aircrafts.uuid = ' . CreateAircraftTelemetry::TABLE_GPS_FRAMES . '.' . CreateAircraftTelemetry::AIRCRAFT_UUID;
                $clause = CreateAircraftTelemetry::UUID . "='$aircraft_id'";
                $aircraft_lists = $database->select(CreateAircraftTelemetry::AIRCRAFT_TABLE . $join_battery_frames . $join_gps_frames ,
                    $clause, Database::RETURN_ARRAY, '', Database::SORT_RESULTS_ASC, '', $fields);

                if ($aircraft_lists != 0 && $aircraft_lists > 0) {
                    $result = [];
                    $batteries = $this->buildBatteriesInfo($aircraft_lists);
                    $flight_path = $this->buildGeoJson($aircraft_lists);
                    foreach ($aircraft_lists as $list) {
                        $result = [
                            'UUID' => $list[CreateAircraftTelemetry::UUID],
                            'AircraftName' => $list[CreateAircraftTelemetry::AIRCRAFT_NAME],
                            'AircraftSerialNumber' => $list[CreateAircraftTelemetry::COLUMN_AIRCRAFT_SERIAL_NUMBER],
                            'Batteries'=>$batteries,
                            'FlightPath'=>$flight_path,
                        ];
                        break;
                    }

                    header('Content-Type: application/json');
                    echo json_encode($result);
                }
            }
        } catch(Exception $exception) {
            (new ApiException('Bad Request' . $exception, 400))->getException();
        }
    }

    private function buildBatteriesInfo($aircraft_lists){
        $batteries = [];

        foreach ($aircraft_lists as $list){
            $batteries[] = [
                'BatteryTemperature' => $list[CreateAircraftTelemetry::BATTERY_TEMPERATURE],
                'BatteryPercent' => $list[CreateAircraftTelemetry::BATTERY_PERCENT],
            ];
        }

        return $batteries;
    }

    private function buildGeoJson($aircraft_lists){
        // Loop through rows to build coordinates array
        $coordinates = [];
        foreach ($aircraft_lists as $list) {
            $coordinates[] = [$list['latitude'],
                    $list['longitude']];
        }

        $geo_json = [
            'type'      => 'FeatureCollection',
            'features'  => [
                'type' => 'Feature',
                'properties' => [],
                'geometry' => [
                    'type' => 'LineString',
                    // Pass Longitude and Latitude Columns here
                    'coordinates' => $coordinates,
                ],
            ],
        ];

        return $geo_json;
    }
}