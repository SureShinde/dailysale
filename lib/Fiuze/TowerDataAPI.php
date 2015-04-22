<?php
/**
 * The TowerDataAPI class is used to access the API (application programming 
 * interface) provided by TowerData.
 */
class TowerDataAPI
{
	/**
	 * @access protected
	 * @var array This array stores all the configuration data that is read 
	 *				from the INI configuration file.
	 */
	protected $config;
	
	/**
	 * @access protected
	 * @var resource The cURL resource that this instance will use.
	 */
	protected $curl;	
	
	/**
	 * @access protected
	 * @var float Total number of seconds in terms of API response time.
	 */
	protected $totResponseTime = 0;
	
	/**
	 * @access protected
	 * @var int Number of API calls made.
	 */
	protected $apiCalls = 0;
	
	/**
	 * Load the configuration variables that may be required by the API access 
	 * class. These variables are loaded from the file path in the constant 
	 * TOWER_CONFIG, or on failure of the existent of that constant, the 
	 * configuration is assumed to be in a file called config.ini in the same 
	 * directory as this file.
	 * 
	 * @access public
	 */
	public function __construct()
	{
		/*
		 * Define the constant TOWER_CONFIG to an absolute path to the .ini 
		 * file. If it is not set, then the configuration is assumed to be set 
		 * in a file called config.ini in the same directory as this file.
		 */
		$configFile = defined('TOWER_CONFIG') ? TOWER_CONFIG : __DIR__ 
				. '/config.ini';
		$this->config = parse_ini_file($configFile);
		
		$request = curl_init();
		
		curl_setopt($request, CURLOPT_HEADER, 0);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($request, CURLOPT_CONNECTTIMEOUT, 
				$this->config['connect_timeout']);
		
		$this->curl = $request;
	}
	
	/**
	 * The callApi method accesses the API, providing any data that was 
	 * provided in the arguments to this method. You may leave off any 
	 * arguments to the right of the ones you need to set. If you want to set 
	 * an argument (e.g. arg 5 - addr1) but not one before it (e.g. arg 3 - 
	 * email), then just pass FALSE as the value for arg 3.
	 *
	 * @access public
	 * 
	 * @param bool|string $fname The first name, or FALSE if not set.
	 * @param bool|string $lname The last name, or FALSE if not set.
	 * @param bool|string $email The email address, or FALSE if not set.
	 * @param bool|string $phone The phone number, or FALSE if not set.
	 * @param bool|string $addr1 The first line of the address, or FALSE if not 
	 *								set.
	 * @param bool|string $addr2 The second line of the address, or FALSE if 
	 *								not set.
	 * @param bool|string $cityn The city name, or FALSE if not set.
	 * @param bool|string $state The state, or FALSE if not set.
	 * @param bool|string $zipcd The zip code, or FALSE if not set.
	 * @param bool|string $ipadd The IP address, or FALSE if not set.
	 * 
	 * @return stdClass An object with all data is returned on successful 
	 *					execution.
	 */
	public function callApi($fname = false, $lname = false, 
			$email = false, $phone = false, $addr1 = false, $addr2 = false, 
			$cityn = false, $state = false, $zipcd = false, $ipadd = false)
	{
		/*
		 * Construct the start of the URL to call.
		 */
		$uri = $this->config['api_url'] . '?license=' 
				. urlencode($this->config['license_key']);
		
		/*
		 * Construct the rest of the URL. Done by checking if the value is 
		 * exactly equal to false. If not, then the value is appended to the 
		 * URI.
		 */
		$uri .= $fname !== false ? '&fname=' . urlencode($fname) : null;
		$uri .= $lname !== false ? '&lname=' . urlencode($lname) : null;
		$uri .= $email !== false ? '&email=' . urlencode($email) : null;
		$uri .= $phone !== false ? '&phone=' . urlencode($phone) : null;
		$uri .= $addr1 !== false ? '&address1=' . urlencode($addr1) : null;
		$uri .= $addr2 !== false ? '&address2=' . urlencode($addr2) : null;
		$uri .= $cityn !== false ? '&city=' . urlencode($cityn) : null;
		$uri .= $state !== false ? '&state=' . urlencode($state) : null;
		$uri .= $zipcd !== false ? '&zip=' . urlencode($zipcd) : null;
		$uri .= $ipadd !== false ? '&ip=' . urlencode($ipadd) : null;
		
		$this->config['connect_timeout'] = 
				isset($this->config['connect_timeout']) 
				? $this->config['connect_timeout'] : 10;
		
		/*
		 * Make any URL modifications that need to be made based on 
		 * configuration values.
		 */
		$uri .= '&';
		$uri .= isset($this->config['api_correct']) ? 
				$this->config['api_correct'] : null;
		$uri .= isset($this->config['api_find']) ? 
                                $this->config['api_find'] : null;
		$uri .= isset($this->config['api_demos']) ? 
                                $this->config['api_demos'] : null;
		$uri .= isset($this->config['api_validate']) ? 
				$this->config['api_validate'] : null;
		$uri .= isset($this->config['api_log_calls']) ? 
				$this->config['api_log_calls'] : null;
		$uri .= isset($this->config['api_timeout']) ? 
				$this->config['api_timeout'] : null;
		
		/*
		 * Construct a cURL request, set it up and execute it. Read the 
		 * returned data into a string to be processed further.
		 */
		$request = $this->curl;
		
		curl_setopt($request, CURLOPT_URL, $uri);
		
		$returnVal = curl_exec($request);
		$headers = curl_getinfo($request);
		
		$this->totResponseTime += $headers['total_time'];
		++$this->apiCalls;
		
		/*
		 * Decode the JSON into a native object.
		 */
		$json = json_decode($returnVal);
		
		/*
		 * If we don't get a 200 OK response, then an exception is thrown.
		 */
		if ($headers['http_code']!==200)
		{
			throw new Exception("HTTP code expected to be 200, 
					{$headers['http_code']} returned instead. Message given was 
					\"{$json->status_code} - {$json->status_desc}\".");
		}
		
		/*
		 * If the status code is over 10, an exception is thrown with the 
		 * message.
		 */
		if ($json->status_code>10)
		{
			throw new Exception("Status over 10, 10 expected. Error message is 
					\"{$json->status_code} - {$json->status_desc}\".");
		}
		
		return $json;
	}
	
	/**
	 * The printOutput method takes the same arguments as the callApi method, 
	 * calls callApi with those arguments and echoes out the return value.
	 *
	 * @access public
	 * @see TowerDataAPI::callApi()
	 * 
	 * @param bool|string $fname The first name, or FALSE if not set.
	 * @param bool|string $lname The last name, or FALSE if not set.
	 * @param bool|string $email The email address, or FALSE if not set.
	 * @param bool|string $phone The phone number, or FALSE if not set.
	 * @param bool|string $addr1 The first line of the address, or FALSE if not 
	 *								set.
	 * @param bool|string $addr2 The second line of the address, or FALSE if 
	 *								not set.
	 * @param bool|string $cityn The city name, or FALSE if not set.
	 * @param bool|string $state The state, or FALSE if not set.
	 * @param bool|string $zipcd The zip code, or FALSE if not set.
	 * @param bool|string $ipadd The IP address, or FALSE if not set.
	 * 
	 * @return void
	 */
	public function printOutput($fname = false, $lname = false, 
			$email = false, $phone = false, $addr1 = false, $addr2 = false, 
			$cityn = false, $state = false, $zipcd = false, $ipadd = false)
	{
		/*
		 * Call the callApi method with any arguments that were passed to this 
		 * function. This allows us to reduce duplication and make maintenance 
		 * easier as only one change is needed (rather than two) if there are 
		 * any bugs.
		 */
		$apiCall = $this->callApi($fname, $lname, $email, $phone, $addr1, 
				$addr2, $cityn, $state, $zipcd, $ipadd);
		
		/*
		 * Dump the data to the standard output stream. This can be reformatted 
		 * later if required.
		 */
		$statCode = $apiCall->status_code;
		$statDesc = $apiCall->status_desc;
		
		echo <<<END
Status Code: $statCode
Status Description: $statDesc


RAW DATA:

END;
		print_r($apiCall);
	}
	
	/**
	 * Get the average response time of the API.
	 * 
	 * @access public
	 * @see TowerDataAPI::$apiCalls, TowerDataAPI::$totResponseTime
	 * 
	 * @return float The average response time in seconds.
	 */
	public function getAvgResponseTime()
	{
		if ($this->apiCalls===0)
		{
			throw new Exception('Divide by zero! No calls have been made yet.');
		}
		return $this->totResponseTime / $this->apiCalls;
	}
	
	/**
	 * Get the number of requests made to the API.
	 *
	 * @access public
	 * @see TowerDataAPI::$apiCalls
	 * 
	 * @return int The number of requests made.
	 */
	public function getApiCallCount()
	{
		return $this->apiCalls;
	}
	
	/**
	 * Close the opened cURL resource that has been used.
	 * 
	 * @access public
	 * 
	 * @return void
	 */
	public function __destruct()
	{
		curl_close($this->curl);
	}
}
