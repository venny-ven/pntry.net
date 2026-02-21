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
	$dbname = 'pntry_v16'; // Changes to internal structure should advance this version
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
	
	// Check if database is there
	$isNewDatabase = $pdo_initial->query("SHOW DATABASES LIKE '$dbname'")->rowCount() > 0;
	
	// Create a database if it's missing
	if (!$isNewDatabase) {
	    try {
		$pdo_initial->exec("CREATE DATABASE $dbname");
	    } catch (PDOException $ex) {
		die("Couldn't create database $dbname: " . $ex->getMessage());
	    }
	}

	// Close connection and connect to the database directly
	$pdo_initial = null;
	$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
	try {
	    $pdo = new PDO($dsn, $user, $pass, $attributes);
	} catch (PDOException $ex) {
	    die("Failed to connect to $dbname: " . $ex->getMessage());
	}

	
	// Create tables if there is no database
	if (!$isNewDatabase)
	{
	    try {
		$pdo->exec
		("
		    -- Cookies. Creation time, last active time, IP, and useragent data are for analytics
		    CREATE TABLE session
		    (
			id INT PRIMARY KEY AUTO_INCREMENT,
			cookie VARCHAR(64) UNIQUE NOT NULL,
			creation_date DATETIME DEFAULT CURRENT_TIMESTAMP,
			last_active_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			ip_address VARCHAR(45),
			useragent_metadata VARCHAR(256)
		    );

		    -- Measurement units like oz and lb
		    CREATE TABLE measurement_unit
		    (
			id INT PRIMARY KEY AUTO_INCREMENT,
			name VARCHAR(16) UNIQUE NOT NULL
		    );

		    -- List of all ingredients out there, a glossary. Shelf life is measured in days
		    -- Table ID identifies which table it goes in
		    CREATE TABLE ingredient
		    (
			id INT PRIMARY KEY AUTO_INCREMENT,
			table_id INT CHECK (table_id BETWEEN 1 AND 4),
			name VARCHAR(64) UNIQUE NOT NULL,
			shelf_life INT,		    
			measurement_unit_id INT,

			FOREIGN KEY (measurement_unit_id) REFERENCES measurement_unit(id)
			    ON UPDATE CASCADE
		    );

		    -- Inventory items of all users in one table
		    -- acquire_date can be null, which allows this table to be joined with glossary even when no item is present
		    CREATE TABLE instance
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
		    CREATE TABLE recipe
		    (
			id INT PRIMARY KEY AUTO_INCREMENT,
			name VARCHAR(128) NOT NULL
		    );

		    -- A junction table matching ingredients with a specific recipe
		    CREATE TABLE recipe_ingredient
		    (
			recipe_id INT,
			ingredient_id INT,
			quantity INT NOT NULL
		    );
		");
	    } catch (PDOException $ex) {
		die("Failed to create tables: " . $ex->getMessage());
	    }
	    try {
		$pdo->exec
		("
		    INSERT INTO measurement_unit (name)
		    VALUES  ('unit'), -- 1
			    ('box'), -- 2
			    ('qrt box'), -- 3
			    ('qrt bag'), -- 4
			    ('can'), -- 5
			    ('jar'), -- 6
			    ('lb'), -- 7
			    ('hlf unit'), -- 8
			    ('hlf bag'), -- 9
			    ('hlf box'), -- 10
			    ('qrt unit'), -- 11
			    ('qrt jar'), -- 12
			    ('tube'), -- 13
			    ('qrt slab'), -- 14
			    ('bottle'); -- 15
		    
		    INSERT INTO ingredient (name, shelf_life, table_id, measurement_unit_id)
		    VALUES  ('Eggs',		35,	1,  1), -- store bought, 3-5 weeks in the fridge
			    ('Chicken',		NULL,   1,  7),
			    ('Beef',		NULL,   1,  7),
			    ('Pork',		NULL,   1,  7),
			    ('Turkey',		NULL,   1,  7),
			    ('Tofu',	        NULL,   1,  2), -- 3-5 months frozen, 3-5 days opened, look for exp. date on sealed
			    ('Sausage',		NULL,   1,  8),
			    ('Deli meat',	14,	1,  9), -- 3-5 days after opening, 2 weeks sealed
			    ('Canned meat',	NULL,   1,  5),
			    ('Shrimp',		NULL,   1,  4),
			    ('Fish',		NULL,   1,  1),
			    ('Canned fish',	NULL,   1,  5),
			    ('Nuggets',		NULL,   1,  4),
			    ('Fish sticks',	NULL,   1,  4),
			    ('Whole cheese',	30,	1,  14), -- Hard cheeses (Cheddar, Parmesan, Swiss) last 42 days, semi-hard (Gouda, Colby Jack) 21 days, soft (Mozzarella) 14 days, opened, do not freeze cheeses
			    ('Shredded cheese',	14,	1,  4), -- Use clean hands to extend shelf life
			    ('Cream cheese',	NULL,	1,  14),
			    ('Yogurt',		14,	1,  12),
			    ('Sour cream',	14,	1,  12),
			    ('Protein shake',	NULL,	1,  15),
			    
			    ('Pasta',		NULL,   2,  3),
			    ('Rice',		NULL,   2,  4),
			    ('Ravioli',		NULL,   2,  3),
			    ('Instant ramen',	NULL,   2,  1),
			    ('Potato',		NULL,   2,  1),
			    ('Chips',		NULL,   2,  9),
			    ('Cookies',		NULL,	2,  3),
			    
			    ('Zucchini',	14,	3,  1), -- 1-2 weeks, 1 week restaurant standard
			    ('Cucumber',	14,	3,  8),
			    ('Broccoli',	7,	3,  4),
			    ('Tomato',		14,	3,  1), -- do not refirgerate
			    ('Apple',		60,	3,  1), -- store in the fridge
			    ('Lemon',		30,	3,  1), -- store citruses in fridge
			    ('Lime',		30,	3,  1),
			    ('Orange',		30,	3,  1),
			    ('Mushrooms',	10,	3,  10),
			    ('Garlic',		21,	3,  11), -- 3 weeks broken, 6 months whole, 1 week peeled 
			    ('Minced garlic',	NULL,	3,  12), -- 18-24 months after opening
			    ('Green onion',	30,	3,  1), -- use bouquet method for herbs https://www.youtube.com/shorts/mdjVdDIOxy4
			    ('Tomato paste',	NULL,   3,  6),
			    ('Carrot',		30,	3,  4),
			    ('Baby carrot',	21,	3,  9),
			    ('Onion, whole',	90,	3,  1), -- store in pantry away from potatoes
			    ('Onion, half',	10,	3,  1), -- sealed tight in fridge
			    
			    ('Sugar',	    NULL,   4,  12),
			    ('Salt',	    NULL,   4,  3),
			    ('Pepper',	    NULL,   4,  6),
			    ('Coconut oil',  NULL,   4,	12);
		");
	    } catch (PDOException $ex) {
		die("Failed to populate tables with default data: " . $ex->getMessage());
	    }
	}
    }

    return $pdo;
}