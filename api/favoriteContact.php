<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: POST, OPTIONS');
	header('Access-Control-Allow-Headers: Content-Type');
	if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

	require_once 'db_config.php';

	$inData = getRequestInfo();
	if( !isset($inData["contactId"]) || !isset($inData["userId"]) || !array_key_exists("favorite", $inData) )
	{
		returnWithError("Missing required fields");
		exit();
	}

	$contactId = (int)$inData["contactId"];
	$userId = (int)$inData["userId"];

	$favRaw = $inData["favorite"];
	if( is_bool($favRaw) )
	{
		$favorite = $favRaw ? 1 : 0;
	}
	else
	{
		$favorite = ((int)$favRaw) ? 1 : 0;
	}

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
		$stmt = $conn->prepare("UPDATE Contacts SET Favorite=? WHERE ID=? AND UserID=?");
		if( !$stmt )
		{
			returnWithError("Database prepare error. Make sure Contacts has a Favorite column.");
			$conn->close();
			exit();
		}

		$stmt->bind_param("iii", $favorite, $contactId, $userId);
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
