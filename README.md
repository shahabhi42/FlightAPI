# Flight API

API to ingest and read flight telemetry data

## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes.

### Prerequisites

```
MYSQL 5.7+
PHP 7.0+
Postman

```

### Getting Started

A step by step series of examples that tell you how to get your localhost env running

```
Initialize SQL schema
1. Run the flight_api.sql file on your localhost
2. The default user and password is set to testuser and testuser. You may change the default credentials in '/database/Database.php'

Server Path
1. For the test purposes the server path is set to /var/www/html

```

## Running the tests

```
Postman Examples
1. Create Flight - http://localhost/flight_api/flight_service.php/CreateFlight. Body - Upload JSON file  
2. List Flight - http://localhost/flight_api/flight_service.php/flights
3. Flight Details - http://localhost/flight_api/flight_service.php/flights/{uuid}
```
