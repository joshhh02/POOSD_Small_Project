<?php

	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: POST, OPTIONS');
	header('Access-Control-Allow-Headers: Content-Type');
	if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

	require_once 'db_config.php';

	$inData = getRequestInfo();
	
	$id = 0;
	$firstName = "";
	$lastName = "";

	$conn = get_db_connection();
	if( !$conn )
	{
		returnWithError( "Database connection failed" );
	}
	else
	{
		if( !isset($inData["login"]) || !isset($inData["password"]) || 
		    empty(trim($inData["login"])) || empty(trim($inData["password"])) )
		{
			returnWithError("All fields are required");
		}
		else
		{
			$cleanLogin = trim($inData["login"]);
			$cleanPassword = trim($inData["password"]);

			$stmt = $conn->prepare("SELECT ID, firstName, lastName FROM Users WHERE LOWER(Login)=LOWER(?) AND Password=?");
			$stmt->bind_param("ss", $cleanLogin, $cleanPassword);
			$stmt->execute();
			$result = $stmt->get_result();

			if( $row = $result->fetch_assoc() )
			{
				returnWithInfo( $row['firstName'], $row['lastName'], $row['ID'] );
			}
			else
			{
				returnWithError("Invalid username or password");
			}

			$stmt->close();
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