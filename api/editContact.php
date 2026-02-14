<?php
	require_once 'db_config.php';
	
	$inData = getRequestInfo();

	$contactId = $inData["contactId"];
	$firstName = $inData["firstName"];
	$lastName = $inData["lastName"];
	$email = $inData["email"];
	$phone = $inData["phone"];
	$userId = $inData["userId"];

	$conn = get_db_connection();
	if ($conn === null) 
	{
		returnWithError("Database connection failed");
	} 
	else
	{
		$stmt = $conn->prepare("UPDATE Contacts SET FirstName=?, LastName=?, Email=?, Phone=?, UserID=? WHERE ID=?");
		$stmt->bind_param("ssssii", $firstName, $lastName, $email, $phone, $userId, $contactId);
		$stmt->execute();
		
		if ($stmt->affected_rows > 0)
		{
			returnWithSuccess();
		}
		else
		{
			returnWithError("No contact updated. Check if contactId is correct.");
		}

		$stmt->close();
		$conn->close();
	}

	function getRequestInfo()
	{
		return json_decode(file_get_contents('php://input'), true);
	}

	function sendResultInfoAsJson($obj)
	{
		header('Content-type: application/json');
		echo $obj;
	}
	
	function returnWithError($err)
	{
		$retValue = '{"success":false,"error":"' . $err . '"}';
		sendResultInfoAsJson($retValue);
	}

	function returnWithSuccess()
	{
		$retValue = '{"success":true,"error":""}';
		sendResultInfoAsJson($retValue);
	}
?>
