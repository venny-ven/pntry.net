<?php

// Returns a session ID which is a primary key in a 'session' table of the DB, it represents a user ID
// If the user has a valid cookie, the session ID is retrieved from the DB
// If there is no valid cookie, a new cookie is created, a new session is created in the DB, and its ID is returned

function getSessionId(): int {
    require_once 'db.php';
    $pdo = getPDO();
    static $session_id;
    
    if (!$session_id) {

	

	// ------- Find a session  -------



	// Check if a browser cookie exists
	if (isset($_COOKIE['session_id'])) {

	    // Find session in DB
	    $stmt = $pdo->prepare('SELECT id FROM session WHERE cookie = :cookie');
	    $stmt->execute([':cookie' => $_COOKIE['session_id']]);
	    $session_id = $stmt->fetchColumn();

	    // If the session is successfully retrieved, get session ID and return
	    if ($session_id !== false) {
		// Update last seen timestamp
		$stmt = $pdo->prepare('UPDATE session SET last_active_date = CURRENT_TIMESTAMP WHERE id = :id');
		$stmt->execute([':id' => $session_id]);
		return $session_id;
	    }

	    // At this point a browser has a cookie but it doesn't match anything in the DB, so expire it
	    // Set to empty hash, -1 hour, root path - apply to all pages on the site
	    // Expired cookie is immediately deleted by the browser
	    setcookie('session_id', '', time() - 3600, '/');
	}



	// ------- Create a session -------



	// Generate a random hash, chance of collision is virtually 0
	$cookie = bin2hex(random_bytes(32));

	// Create a new session in the database
	try {
	    $stmt = $pdo->prepare
	    ('
		INSERT INTO session (cookie, ip_address, useragent_metadata)
		VALUES (:cookie, :ip_address, :useragent_metadata)
	    ');
	    $stmt->execute
	    ([
		':cookie' => $cookie,
		':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
		':useragent_metadata' => $_SERVER['HTTP_USER_AGENT'] ?? null
	    ]);
	} catch (PDOException $ex) {
	    die("Failed to create a new session in DB: " . $ex->getMessage());
	}

	// Configure and initialize a browser cookie
	setcookie('session_id', $cookie, [
	    'expires' => time() + (10 * 365 * 24 * 60 * 60), // Expires in 10 years
	    'path' => '/', // Root path - valid for all pages on the site
	    'secure' => true, // HTTPS only
	    'httponly' => true, // PHP side only, no JS
	    'samesite' => 'Strict' // No cross-site cookie tracking
	]);

	// lastInsertId() returns any auto-incremented value that occured on the last insert
	$session_id = $pdo->lastInsertId();
	return $session_id;
    }
}
