<?php

	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: POST, OPTIONS');
	header('Access-Control-Allow-Headers: Content-Type');
	if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

	require_once 'db_config.php';

	$inData = getRequestInfo();

	if( !isset($inData["firstName"]) || !isset($inData["lastName"]) || !isset($inData["email"]) || !isset($inData["phone"]) || !isset($inData["userId"]) )
	{
		returnWithError("Missing required fields");
		exit();
	}

	$firstName = trim($inData["firstName"]);
	$lastName = trim($inData["lastName"]);
	$email = trim($inData["email"]);
	$phone = trim($inData["phone"]);
	$userId = (int)$inData["userId"];

	if( $firstName === "" || $email === "" || $phone === "" || $userId <= 0 )
	{
		returnWithError("Invalid contact data");
		exit();
	}

	$conn = get_db_connection();
	if (!$conn) 
	{
		returnWithError("Database connection failed");
	} 
	else
	{
		$dupStmt = $conn->prepare("SELECT ID FROM Contacts WHERE UserID=? AND LOWER(FirstName)=LOWER(?) AND LOWER(LastName)=LOWER(?) AND LOWER(Email)=LOWER(?) AND Phone=? LIMIT 1");
		if( !$dupStmt )
		{
			returnWithError("Database prepare error");
			$conn->close();
			exit();
		}

		$dupStmt->bind_param("issss", $userId, $firstName, $lastName, $email, $phone);
		$dupStmt->execute();
		$dupResult = $dupStmt->get_result();
		if( $dupResult && $dupResult->num_rows > 0 )
		{
			$dupStmt->close();
			$conn->close();
			returnWithError("Contact already exists");
			exit();
		}
		$dupStmt->close();

		$stmt = $conn->prepare("INSERT into Contacts (FirstName, LastName, Email, Phone, UserID) VALUES (?, ?, ?, ?, ?)");
		if( !$stmt )
		{
			returnWithError("Database prepare error");
			$conn->close();
			exit();
		}

		$stmt->bind_param("ssssi", $firstName, $lastName, $email, $phone, $userId);
		if( !$stmt->execute() )
		{
			$stmt->close();
			$conn->close();
			returnWithError("Failed to add contact");
			exit();
		}

		$contactId = $conn->insert_id;

		$stmt->close();
		$conn->close();

		returnWithSuccess($contactId);
	}

	/**
	 * Read and decode JSON request body for contact endpoints.
	 *
	 * @return array Decoded request data or empty array on JSON error
	 */
	function getRequestInfo()
	{
		$decoded = json_decode(file_get_contents('php://input'), true);
		return is_array($decoded) ? $decoded : array();
	}

	/**
	 * Send a JSON response string to the client.
	 *
	 * @param string $obj JSON-encoded string to send as the response body
	 * @return void
	 */
	function sendResultInfoAsJson($obj)
	{
		header('Content-type: application/json');
		echo $obj;
		exit();
	}
	
	/**
	 * Format and send error response for contact endpoints.
	 *
	 * @param string $err Error message
	 * @return void
	 */
	function returnWithError($err)
	{
		sendResultInfoAsJson(json_encode(array(
			"success" => false,
			"error" => $err
		)));
	}

	/**
	 * Format and send success response for contact creation.
	 *
	 * @param int $contactId Newly created contact ID
	 * @return void
	 */
	function returnWithSuccess($contactId)
	{
		sendResultInfoAsJson(json_encode(array(
			"success" => true,
			"error" => "",
			"contactId" => (int)$contactId
		)));
	}
?>