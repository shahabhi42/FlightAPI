<?php
include_once("/var/www/html/flight_api/stdlib.php");

// Endpoints
const CREATE_FLIGHT = '/CreateFlight';
const REQUEST_FLIGHT = '/flights';

const FLIGHT_API_AUTHORIZATION = 'Basic dGVzdDp0ZXN0'; //  user_name test password test

// Get the uploaded file
$original_request = file_get_contents('php://input');

// Check if valid json
$request = [];
if (is_string($original_request) && is_object(json_decode($original_request)) && (json_last_error() == JSON_ERROR_NONE)) {
    // Parse the json
    $request = json_decode($original_request, true);
}

// Process the provided request path info
$path_info = $_SERVER['PATH_INFO'];
$aircraft_info_path = substr($path_info, -2); // For aircraft details. Get the id
if (isset($path_info) && $path_info != '') {
    // Check if provided authorization is valid
    $authorization = array_key_exists('Authorization', apache_request_headers()) ? apache_request_headers()['Authorization'] : null;
    if ($authorization != FLIGHT_API_AUTHORIZATION) {
        (new ApiException('Forbidden', 403))->getException();
    }

    // Condition to route the request
    if ($path_info == CREATE_FLIGHT) { // Insert flight telemetry
        // Process the request in the applicable class
        (new CreateAircraftTelemetry)->processRequest($request);
    } elseif ($path_info == REQUEST_FLIGHT) { // Get flight list
        // Process the request in the applicable class
        (new RequestAircraftTelemetry())->getListFlights();
    } elseif ($aircraft_info_path!='') { // Get flight details
        // Process the request in the applicable class
        (new RequestAircraftTelemetry())->getFlightDetails($path_info);
    } else {
        (new ApiException('Bad Request', 400))->getException();
    }
}