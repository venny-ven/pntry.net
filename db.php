<?php

// Returns a query interface called PDO (PHP Data Objects) that is connected to a local database (DB)
// Establishes a connection to the DB using a unix domain socket - a way for a process to talk to another without exposing ports
// The function contains the entire blueprint of the database, all of the CREATE statements
// It will either generate a new database or connect to an existing one depending on if an up-to-date database is found

function getPDO() {
    static $pdo;

    if (!$pdo) {
	
        $host = 'localhost'; // Target IP - database that's running locally
	$user = 'www-data'; // Username associated with NginX and Apache process
	$pass = '';  // No password since connecting via unix_socket
	$charset = 'utf8mb4'; // Modern charset standard - UTF8 + patch, fast general sorting / comparison, case insensitive
	$dbname = 'pntry_v9'; // Changes to internal structure should advance this version
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
		-- Cookies. Creation time, last active time, IP, and useragent data are for analytics
		CREATE TABLE IF NOT EXISTS session
		(
		    id INT PRIMARY KEY AUTO_INCREMENT,
		    cookie VARCHAR(64) UNIQUE NOT NULL,
		    creation_date DATETIME DEFAULT CURRENT_TIMESTAMP,
		    last_active_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		    ip_address VARCHAR(45),
		    useragent_metadata VARCHAR(256)
		);
		
		-- Measurement units like oz and lb
		CREATE TABLE IF NOT EXISTS measurement_unit
		(
		    id INT PRIMARY KEY AUTO_INCREMENT,
		    name VARCHAR(16) UNIQUE NOT NULL
		);
	
		-- List of all ingredients out there, a glossary. Shelf life is measured in days
		CREATE TABLE IF NOT EXISTS ingredient
		(
		    id INT PRIMARY KEY AUTO_INCREMENT,
		    name VARCHAR(64) UNIQUE NOT NULL,
		    category VARCHAR(16) CHECK
			(category IN ('protein', 'starch', 'vegetable', 'fruit', 'dairy', 'seasoning')),
		    shelf_life INT,		    
		    measurement_unit_id INT,
	    
		    FOREIGN KEY (measurement_unit_id) REFERENCES measurement_unit(id)
			ON UPDATE CASCADE
		);
		
		-- Inventory items of all users in one table
		CREATE TABLE IF NOT EXISTS instance
		(
		    session_id INT,
		    ingredient_id INT,
		    acquire_date DATETIME,
		    quantity INT NOT NULL DEFAULT 0,
		    
		    PRIMARY KEY (session_id, ingredient_id),
		    FOREIGN KEY (session_id) REFERENCES session(id) ON DELETE CASCADE,
		    FOREIGN KEY (ingredient_id) REFERENCES ingredient(id) ON DELETE RESTRICT
		);
		
		-- Recipes which are public for now
		CREATE TABLE IF NOT EXISTS recipe
		(
		    id INT PRIMARY KEY AUTO_INCREMENT,
		    name VARCHAR(128) NOT NULL
		);
		
		-- A junction table matching ingredients with a specific recipe
		CREATE TABLE IF NOT EXISTS recipe_ingredient
		(
		    recipe_id INT,
		    ingredient_id INT,
		    quantity INT NOT NULL
		);
	    ");
	} catch (PDOException $ex) {
	    die("Failed to create tables: " . $ex->getMessage());
	}
    }

    return $pdo;
}