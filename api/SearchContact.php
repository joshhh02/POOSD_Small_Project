

<?php

	// reads json data sent from the front end
	$inData = getRequestInfo();
	
	// test for what log recieved; remove when deploying publicly
	error_log("Received data: " . json_encode($inData));
	
	$searchResults = "";
	$searchCount = 0;
	
	// connects to mysql database 
	$conn = new mysqli("localhost", "TheBeast", "WeLoveCOP4331", "cop4331");
	if( $conn->connect_error ) 
	{
		returnWithError( $conn->connect_error );
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
			
			if( empty($searchTerm) )
			{
				// if search is empty, return all contacts for user
				$stmt = $conn->prepare("SELECT ID, FirstName, LastName, Phone, Email FROM Contacts WHERE UserID=?");
				$stmt->bind_param("i", $userId);
				$stmt->execute();
				$result = $stmt->get_result();

				// stores contacts in array to send as json
				$contacts = array();
				while( $row = $result->fetch_assoc() )
				{
					// combind first and last name into name field
					$row['Name'] = $row['FirstName'] . ' ' . $row['LastName'];
					$row['userId'] = $userId; // add userid in response
					unset($row['FirstName']); // removes seperated first last name
					unset($row['LastName']);
					$contacts[] = $row; 
				}
				$stmt->close();

				// if 0 < contacts , return contacts
				if( count($contacts) > 0 )
				{
					returnWithInfo($contacts, $userId);
				}
				else
				{
					returnWithError("No contacts found.", $userId);
				}
			}
			else
			{
				// if search not empty, search for partial matches in all fields
				$stmt = $conn->prepare("SELECT ID, FirstName, LastName, Phone, Email FROM Contacts WHERE UserID=? AND (FirstName LIKE ? OR LastName LIKE ? OR Phone LIKE ? OR Email LIKE ?)");
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
					
					// if no matches , try fuzzy search for typos
					if( count($contacts) == 0 )
					{
						$contacts = performFuzzySearch($conn, $userId, $searchTerm);
					}
					
					if( count($contacts) > 0 )
					{
						returnWithInfo($contacts, $userId);
					}
					else
					{
						returnWithError("No contacts found.", $userId);
					}
				}
			}
		}
		
		$conn->close();
	}
	
	// function to work for typos
	function performFuzzySearch($conn, $userId, $searchTerm)
	{
		$contacts = array();
		
		// get all contacts for the user
		$stmt = $conn->prepare("SELECT ID, FirstName, LastName, Phone, Email FROM Contacts WHERE UserID=?");
		if( !$stmt )
		{
			return $contacts; // return empty array on error
		}
		
		$stmt->bind_param("i", $userId);
		$stmt->execute();
		$result = $stmt->get_result();
		
		while( $row = $result->fetch_assoc() )
		{
			// combines first and last name for searching
			$fullName = $row['FirstName'] . ' ' . $row['LastName'];
			
			// checks  similarity with full name, first name, last name, phone, and email
			$fullNameDistance = levenshteinDistance(strtolower($searchTerm), strtolower($fullName));
			$firstNameDistance = levenshteinDistance(strtolower($searchTerm), strtolower($row['FirstName']));
			$lastNameDistance = levenshteinDistance(strtolower($searchTerm), strtolower($row['LastName']));
			$phoneDistance = levenshteinDistance(strtolower($searchTerm), strtolower($row['Phone']));
			$emailDistance = levenshteinDistance(strtolower($searchTerm), strtolower($row['Email']));
			
			$minDistance = min($fullNameDistance, $firstNameDistance, $lastNameDistance, $phoneDistance, $emailDistance);
			
			// allow up to 2 character differences for typos
			$maxAllowedDistance = min(2, floor(strlen($searchTerm) * 0.3));
			
			if( $minDistance <= $maxAllowedDistance )
			{
				// formats the response to match expected structure
				$contact = array(
					'ID' => $row['ID'], 
					'Name' => $fullName,
					'Phone' => $row['Phone'],
					'Email' => $row['Email'],
					'userId' => $userId // adds userId to each contact
				);
				$contacts[] = $contact;
			}
		}
		
		$stmt->close();
		return $contacts;
	}

	// helper function to see how similar inputs are to users
	function levenshteinDistance($str1, $str2)
	{
		$len1 = strlen($str1);
		$len2 = strlen($str2);
		
		if( $len1 == 0 ) return $len2;
		if( $len2 == 0 ) return $len1;
		
		$matrix = array();
		
		// initialize first row and column
		for( $i = 0; $i <= $len1; $i++ )
		{
			$matrix[$i][0] = $i;
		}
		for( $j = 0; $j <= $len2; $j++ )
		{
			$matrix[0][$j] = $j;
		}
		
		// fill the matrix
		for( $i = 1; $i <= $len1; $i++ )
		{
			for( $j = 1; $j <= $len2; $j++ )
			{
				$cost = ($str1[$i-1] == $str2[$j-1]) ? 0 : 1;
				
				$matrix[$i][$j] = min(
					$matrix[$i-1][$j] + 1,        // deletion
					$matrix[$i][$j-1] + 1,        // insertion
					$matrix[$i-1][$j-1] + $cost   // substitution
				);
			}
		}
		
		return $matrix[$len1][$len2];
	}
	
	// function to decode JSON input from request body
	function getRequestInfo()
	{
		$input = file_get_contents('php://input');
		error_log("Raw input: " . $input); // Debug line
		$decoded = json_decode($input, true);
		
		// cehcks for JSON decode errors
		if (json_last_error() !== JSON_ERROR_NONE) {
			error_log("JSON decode error: " . json_last_error_msg());
			return array(); // return empty array instead of null
		}
		
		return $decoded ? $decoded : array();
	}

	// function to send JSON response with proper content type header
	function sendResultInfoAsJson( $obj )
	{
		header('Content-type: application/json');
		echo $obj;
	}
	
	// function to format and send error response for search
	function returnWithError( $err, $userId = null )
	{
		$retValue = '{"results":[],"error":"' . $err . '"';
		if( $userId !== null ) {
			$retValue .= ',"userId":' . $userId;
		}
		$retValue .= '}';
		sendResultInfoAsJson( $retValue );
	}
	
	// function to format and send successful response with contact results
	function returnWithInfo( $contacts, $userId = null )
	{
		$resultsJson = json_encode($contacts);
		if( $resultsJson === false )
		{
			returnWithError("Error encoding results to JSON", $userId);
			return;
		}
		
		$retValue = '{"results":' . $resultsJson . ',"error":""';
		if( $userId !== null ) {
			$retValue .= ',"userId":' . $userId;
		}
		$retValue .= '}';
		sendResultInfoAsJson( $retValue );
	}
	
?>
