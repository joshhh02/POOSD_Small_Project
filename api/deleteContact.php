<?php
	require_once 'db_config.php';
	
	$inData = getRequestInfo();
	if( !isset($inData["contactId"]) || !isset($inData["userId"]) )
	{
		returnWithError("Missing required fields");
		exit();
	}

	$contactId = (int)$inData["contactId"];
	$userId = (int)$inData["userId"];

	if( $contactId <= 0 || $userId <= 0 )
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
		$stmt = $conn->prepare("DELETE FROM Contacts WHERE ID = ? AND UserID = ?");
		if( !$stmt )
		{
			returnWithError("Database prepare error");
			$conn->close();
			exit();
		}

		$stmt->bind_param("ii", $contactId, $userId);
		$stmt->execute();
		
		if ($stmt->affected_rows > 0)
		{
			returnWithSuccess();
		}
		else
		{
			returnWithError("No contact found with that ID.");
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
