<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: POST, OPTIONS');
	header('Access-Control-Allow-Headers: Content-Type');
	if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

	require_once 'db_config.php';
	
	$inData = getRequestInfo();
	if( !isset($inData["contactId"]) || !isset($inData["firstName"]) || !isset($inData["lastName"]) || !isset($inData["email"]) || !isset($inData["phone"]) || !isset($inData["userId"]) )
	{
		returnWithError("Missing required fields");
		exit();
	}

	$contactId = (int)$inData["contactId"];
	$firstName = trim($inData["firstName"]);
	$lastName = trim($inData["lastName"]);
	$email = trim($inData["email"]);
	$phone = trim($inData["phone"]);
	$userId = (int)$inData["userId"];

	if( $contactId <= 0 || $userId <= 0 || $firstName === "" || $email === "" || $phone === "" )
	{
		returnWithError("Invalid contact data");
		exit();
	}

	$conn = get_db_connection();
	if ($conn === null) 
	{
		returnWithError("Database connection failed");
	} 
	else
	{
		$dupStmt = $conn->prepare("SELECT ID FROM Contacts WHERE UserID=? AND LOWER(FirstName)=LOWER(?) AND LOWER(LastName)=LOWER(?) AND LOWER(Email)=LOWER(?) AND Phone=? AND ID<>? LIMIT 1");
		if( !$dupStmt )
		{
			returnWithError("Database prepare error");
			$conn->close();
			exit();
		}

		$dupStmt->bind_param("issssi", $userId, $firstName, $lastName, $email, $phone, $contactId);
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

		$stmt = $conn->prepare("UPDATE Contacts SET FirstName=?, LastName=?, Email=?, Phone=? WHERE ID=? AND UserID=?");
		if( !$stmt )
		{
			returnWithError("Database prepare error");
			$conn->close();
			exit();
		}

		$stmt->bind_param("ssssii", $firstName, $lastName, $email, $phone, $contactId, $userId);
		$stmt->execute();
		
		if ($stmt->affected_rows > 0)
		{
			returnWithSuccess();
		}
		else
		{
			$existsStmt = $conn->prepare("SELECT ID FROM Contacts WHERE ID=? AND UserID=? LIMIT 1");
			if( !$existsStmt )
			{
				$stmt->close();
				$conn->close();
				returnWithError("Database prepare error");
				exit();
			}

			$existsStmt->bind_param("ii", $contactId, $userId);
			$existsStmt->execute();
			$existsResult = $existsStmt->get_result();
			$existsStmt->close();

			if( $existsResult && $existsResult->num_rows > 0 )
			{
				returnWithSuccess();
			}
			else
			{
				returnWithError("No contact updated. Check if contactId is correct.");
			}
		}

		$stmt->close();
		$conn->close();
	}

	function getRequestInfo()
	{
		$decoded = json_decode(file_get_contents('php://input'), true);
		return is_array($decoded) ? $decoded : array();
	}

	function sendResultInfoAsJson($obj)
	{
		header('Content-type: application/json');
		echo $obj;
		exit();
	}
	
	function returnWithError($err)
	{
		sendResultInfoAsJson(json_encode(array(
			"success" => false,
			"error" => $err
		)));
	}

	function returnWithSuccess()
	{
		sendResultInfoAsJson(json_encode(array(
			"success" => true,
			"error" => ""
		)));
	}
?>
