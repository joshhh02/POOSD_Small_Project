<?php

	require_once 'db_config.php';

	$inData = getRequestInfo();
	
	$firstName = "";
	$lastName = "";
	$login = "";
	$password = "";
	
	$conn = get_db_connection();
	if( !$conn )
	{
		returnWithError( "Database connection failed" );
	}
	else
	{
		if( !isset($inData["firstName"]) || !isset($inData["lastName"]) || 
		    !isset($inData["login"]) || !isset($inData["password"]) ||
		    empty(trim($inData["firstName"])) || empty(trim($inData["lastName"])) ||
		    empty(trim($inData["login"])) || empty(trim($inData["password"])) )
		{
			returnWithError("All fields are required");
		}
		else
		{
			$cleanFirstName = trim($inData["firstName"]);
			$cleanLastName = trim($inData["lastName"]);
			$cleanLogin = trim($inData["login"]);
			$cleanPassword = trim($inData["password"]);

			$checkStmt = $conn->prepare("SELECT ID FROM Users WHERE LOWER(Login)=LOWER(?)");
			$checkStmt->bind_param("s", $cleanLogin);
			$checkStmt->execute();
			$checkResult = $checkStmt->get_result();
			
			if( $checkResult->num_rows > 0 )
			{
				returnWithError("Username already exists");
			}
			else
			{
				$stmt = $conn->prepare("INSERT INTO Users (firstName, lastName, Login, Password) VALUES (?, ?, ?, ?)");
				$stmt->bind_param("ssss", $cleanFirstName, $cleanLastName, $cleanLogin, $cleanPassword);
				
				if( $stmt->execute() )
				{
					$newUserId = $conn->insert_id;
					returnWithInfo( $cleanFirstName, $cleanLastName, $newUserId );
				}
				else
				{
					returnWithError("Registration failed");
				}
				
				$stmt->close();
			}
			
			$checkStmt->close();
		}
		
		$conn->close();
	}
	
	/**
	 * Read and decode JSON request body.
	 *
	 * Reads raw input from php://input and decodes it into an associative
	 * array. Returns an empty array if JSON is invalid.
	 *
	 * @return array Decoded request data or empty array on error
	 */
	function getRequestInfo()
	{
		$input = file_get_contents('php://input');
		$decoded = json_decode($input, true);
		
		if (json_last_error() !== JSON_ERROR_NONE) {
			return [];
		}
		
		return $decoded ? $decoded : [];
	}

	/**
	 * Send a JSON response string to the client.
	 *
	 * @param string $obj JSON-encoded string to send as the response body
	 * @return void
	 */
	function sendResultInfoAsJson( $obj )
	{
		header('Content-type: application/json');
		echo $obj;
		exit();
	}
	
	/**
	 * Format and return an error response for user-related endpoints.
	 *
	 * @param string $err Error message
	 * @return void
	 */
	function returnWithError( $err )
	{
		sendResultInfoAsJson(json_encode(array(
			"id" => 0,
			"firstName" => "",
			"lastName" => "",
			"error" => $err
		)));
	}
	
	/**
	 * Format and return success response with user information.
	 *
	 * @param string $firstName User first name
	 * @param string $lastName User last name
	 * @param int $id User ID
	 * @return void
	 */
	function returnWithInfo( $firstName, $lastName, $id )
	{
		sendResultInfoAsJson(json_encode(array(
			"id" => (int)$id,
			"firstName" => $firstName,
			"lastName" => $lastName,
			"error" => ""
		)));
	}
	
?>