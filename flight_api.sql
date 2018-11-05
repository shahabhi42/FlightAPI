CREATE DATABASE flights_api;

CREATE TABLE flights_api.aircrafts (
  aircraft_id BIGINT(25) PRIMARY KEY AUTO_INCREMENT NOT NULL,
  uuid VARCHAR(25) NOT NULL,
  aircraft_name VARCHAR(25) NOT NULL,
  aircraft_serial_number VARCHAR(25)  NOT NULL
);

CREATE TABLE flights_api.aircrafts_batteries (
  aircraft_battey_id BIGINT(25) PRIMARY KEY AUTO_INCREMENT NOT NULL,
  aircraft_uuid VARCHAR(25) NOT NULL,
  battery_name VARCHAR(25) NOT NULL,
  battery_serial_number VARCHAR(25) NOT NULL
);

CREATE TABLE flights_api.battery_frames (
  battery_frame_id BIGINT(25) PRIMARY KEY AUTO_INCREMENT NOT NULL,
  aircraft_uuid VARCHAR(25) NOT NULL,
  timestamp VARCHAR(25) NOT NULL,
  battery_serial_number VARCHAR(25) NOT NULL,
  battery_percent VARCHAR(25) NOT NULL,
  battery_temperature VARCHAR(25) NOT NULL
);

CREATE TABLE flights_api.gps_frames (
  gps_frame_id BIGINT(25) PRIMARY KEY AUTO_INCREMENT NOT NULL,
  aircraft_uuid VARCHAR(25) NOT NULL,
  timestamp VARCHAR(25) NOT NULL,
  latitude VARCHAR(25) NOT NULL,
  longitude VARCHAR(25) NOT NULL,
  altitude VARCHAR(25) NOT NULL
);