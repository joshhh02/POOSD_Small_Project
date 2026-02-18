<?php
	require_once 'db_config.php';

	// reads json data sent from the front end
	$inData = getRequestInfo();
	
	// connects to mysql database 
	$conn = get_db_connection();
	if( $conn === null ) 
	{
		returnWithError( "Database connection failed" );
	}
	else
	{
		// checks both fields are recieved and valid
		if( !isset($inData["search"]) || !isset($inData["userId"]) )
		{
			returnWithError("Missing required fields: search and userId");
		}
		// check id is a valid number
		else if( !is_numeric($inData["userId"]) )
		{
			returnWithError("Invalid userId - must be a number");
		}
		else
		{
			//clean up search input
			$searchTerm = trim($inData["search"]);
			$userId = (int)$inData["userId"];

			if( $userId <= 0 )
			{
				returnWithError("Invalid userId - must be a positive number");
				$conn->close();
				exit();
			}
			
			if( empty($searchTerm) )
			{
				// if search is empty, return all contacts for user
				$stmt = $conn->prepare("SELECT ID, FirstName, LastName, Phone, Email, DateCreated FROM Contacts WHERE UserID=? ORDER BY FirstName ASC, LastName ASC, ID ASC");
				$stmt->bind_param("i", $userId);
				$stmt->execute();
				$result = $stmt->get_result();

				// stores contacts in array to send as json
				$contacts = array();
				while( $row = $result->fetch_assoc() )
				{
					// combined first and last name into name field
					$row['Name'] = $row['FirstName'] . ' ' . $row['LastName'];
					$row['userId'] = $userId; // add userid in response
					unset($row['FirstName']); // removes seperated first last name
					unset($row['LastName']);
					$contacts[] = $row; 
				}
				$stmt->close();

				returnWithInfo($contacts, $userId);
			}
			else
			{
				// if search not empty, search for partial matches in all fields
				$stmt = $conn->prepare("SELECT ID, FirstName, LastName, Phone, Email, DateCreated FROM Contacts WHERE UserID=? AND (LOWER(FirstName) LIKE LOWER(?) OR LOWER(LastName) LIKE LOWER(?) OR Phone LIKE ? OR LOWER(Email) LIKE LOWER(?)) ORDER BY FirstName ASC, LastName ASC, ID ASC");
				if( !$stmt )
				{
					returnWithError("Database prepare error: " . $conn->error);
				}
				else
				{
					$searchPattern = "%" . $searchTerm . "%";
					$stmt->bind_param("issss", $userId, $searchPattern, $searchPattern, $searchPattern, $searchPattern);
					$stmt->execute();
					$result = $stmt->get_result();
					
					$contacts = array();
					while( $row = $result->fetch_assoc() )
					{
						// same combination of first and last
						$row['Name'] = $row['FirstName'] . ' ' . $row['LastName'];
						$row['userId'] = $userId; // adds userId to each contact
						unset($row['FirstName']); // removes individual fields
						unset($row['LastName']);
						$contacts[] = $row; 
					}
					$stmt->close();
					
					returnWithInfo($contacts, $userId);
				}
			}
		}
		
		$conn->close();
	}
	
	// function to decode JSON input from request body
	function getRequestInfo()
	{
		$input = file_get_contents('php://input');
		$decoded = json_decode($input, true);
		
		// checks for JSON decode errors
		if (json_last_error() !== JSON_ERROR_NONE) {
			return array(); // return empty array instead of null
		}
		
		return $decoded ? $decoded : array();
	}

	// function to send JSON response with proper content type header
	function sendResultInfoAsJson( $obj )
	{
		header('Content-type: application/json');
		echo $obj;
		exit();
	}
	
	// function to format and send error response for search
	function returnWithError( $err, $userId = null )
	{
		$response = array(
			"results" => array(),
			"error" => $err
		);

		if( $userId !== null ) {
			$response["userId"] = (int)$userId;
		}

		sendResultInfoAsJson(json_encode($response));
	}
	
	// function to format and send successful response with contact results
	function returnWithInfo( $contacts, $userId = null )
	{
		$response = array(
			"results" => $contacts,
			"error" => ""
		);

		if( $userId !== null ) {
			$response["userId"] = (int)$userId;
		}

		sendResultInfoAsJson(json_encode($response));
	}
	
?>
