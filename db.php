<?php

function getPDO() {
    static $pdo;

    if (!$pdo) {
	
        $host = 'localhost'; // Target IP - database that's running locally
	$user = 'www-data'; // Username associated with NginX and Apache process
	$pass = '';  // No password since connecting via unix_socket
	$charset = 'utf8mb4'; // Modern charset standard - UTF8 + patch, fast general sorting / comparison, case insensitive
	$dbname = 'pntry_v6'; // Changes to internal structure should advance this version
	$attributes = [
	    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // DB fails will raise exceptions, not warnings, not ignored
	    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Items fetched as key->value, not key->value AND keyID->valueID - cleaner
	    PDO::ATTR_EMULATE_PREPARES   => false, // Don't prepare statements on PDO, prepare natively on DB instead - better security
	];

	// DSN = Data Source Name is a string that has an associated data structure used to describe a connection to a data source
	$dsn_initial = "mysql:host=$host;charset=$charset";

	// Connect to DBSM, not a specific database
	try {
	    $pdo_initial = new PDO($dsn_initial, $user, $pass, $attributes);
	} catch (PDOException $ex) {
	    die("DBMS connection failed: " . $ex->getMessage());
	}

	// Create a database if it's missing
	try {
	    $pdo_initial->exec("CREATE DATABASE IF NOT EXISTS $dbname");
	    } catch (PDOException $ex) {
	    die("Couldn't locate / create database $dbname: " . $ex->getMessage());
	}

	// Close connection and connect to the database directly
	$pdo_initial = null;
	$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
	try {
	    $pdo = new PDO($dsn, $user, $pass, $attributes);
	} catch (PDOException $ex) {
	    die("Failed to connect to $dbname: " . $ex->getMessage());
	}

	// Create tables
	try {
	    $pdo->exec
	    ("
		CREATE TABLE IF NOT EXISTS measurement
		(
		    id INT PRIMARY KEY AUTO_INCREMENT,
		    unit VARCHAR(12) UNIQUE NOT NULL
		);
	
		CREATE TABLE IF NOT EXISTS ingredient
		(
		    id INT PRIMARY KEY AUTO_INCREMENT,
		    name VARCHAR(30) UNIQUE NOT NULL,
		    category VARCHAR(12) CHECK
			(category IN ('protein', 'starch', 'vegetable', 'fruit', 'dairy', 'seasoning')),
		    quantity INT NOT NULL DEFAULT 0,
		    shelf_life_days INT,
		    acquire_date DATETIME,		    
		    measurement_id INT,
	    
		    FOREIGN KEY (measurement_id) REFERENCES measurement(id)
			ON UPDATE CASCADE
		);
	    ");
	} catch (PDOException $ex) {
	    die("Failed to create tables: " . $ex->getMessage());
	}
    }

    return $pdo;
}