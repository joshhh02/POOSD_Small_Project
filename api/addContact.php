<?php

	require_once 'db_config.php';

	$inData = getRequestInfo();
	
	$firstName = $inData["firstName"];
	$lastName = $inData["lastName"];
	$email = $inData["email"];
	$phone = $inData["phone"];
	$userId = $inData["userId"];

	$conn = get_db_connection();
	if (!$conn) 
	{
		returnWithError("Database connection failed");
	} 
	else
	{
		$stmt = $conn->prepare("INSERT into Contacts (FirstName, LastName, Email, Phone, UserID) VALUES (?, ?, ?, ?, ?)");
		$stmt->bind_param("ssssi", $firstName, $lastName, $email, $phone, $userId);
		$stmt->execute();

		$contactId = $stmt->insert_id;

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
		return json_decode(file_get_contents('php://input'), true);
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
	}
	
	/**
	 * Format and send error response for contact endpoints.
	 *
	 * @param string $err Error message
	 * @return void
	 */
	function returnWithError($err)
	{
		$retValue = '{"success":false,"error":"' . $err . '"}';
		sendResultInfoAsJson($retValue);
	}

	/**
	 * Format and send success response for contact creation.
	 *
	 * @param int $contactId Newly created contact ID
	 * @return void
	 */
	function returnWithSuccess($contactId)
	{
		$retValue = '{"success":true,"error":"","contactId":' . $contactId . '}';
		sendResultInfoAsJson($retValue);
	}
?>