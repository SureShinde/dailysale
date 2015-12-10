<?php
class IWD_Signin_Helper_Yahoo extends Mage_Core_Helper_Abstract{
	
	public function oauth_compute_hmac_sig($http_method, $url, $params, $consumer_secret, $token_secret){
		
	
		$base_string = $this->signature_base_string($http_method, $url, $params);
		$signature_key = $this->rfc3986_encode($consumer_secret) . '&' . $this->rfc3986_encode($token_secret);
		$sig = base64_encode(hash_hmac('sha1', $base_string, $signature_key, true));
		
		return $sig;
	}
	
	public function oauth_http_build_query($params, $excludeOauthParams=false){
		$query_string = '';
		if (! empty($params)) {
	
			// rfc3986 encode both keys and values
			$keys = $this->rfc3986_encode(array_keys($params));
			$values =$this-> rfc3986_encode(array_values($params));
			$params = array_combine($keys, $values);
	
			// Parameters are sorted by name, using lexicographical byte value ordering.
			// http://oauth.net/core/1.0/#rfc.section.9.1.1
			uksort($params, 'strcmp');
	
			// Turn params array into an array of "key=value" strings
			$kvpairs = array();
			foreach ($params as $k => $v) {
				if ($excludeOauthParams && substr($k, 0, 5) == 'oauth') {
					continue;
				}
				if (is_array($v)) {
					// If two or more parameters share the same name,
					// they are sorted by their value. OAuth Spec: 9.1.1 (1)
					natsort($v);
					foreach ($v as $value_for_same_key) {
						array_push($kvpairs, ($k . '=' . $value_for_same_key));
					}
				} else {
					// For each parameter, the name is separated from the corresponding
					// value by an '=' character (ASCII code 61). OAuth Spec: 9.1.1 (2)
					array_push($kvpairs, ($k . '=' . $v));
				}
			}
	
			// Each name-value pair is separated by an '&' character, ASCII code 38.
			// OAuth Spec: 9.1.1 (2)
			$query_string = implode('&', $kvpairs);
		}
	
		return $query_string;
	}
	
	public function signature_base_string($http_method, $url, $params){
		// Decompose and pull query params out of the url
		$query_str = parse_url($url, PHP_URL_QUERY);
		if ($query_str) {
			$parsed_query = $this->oauth_parse_str($query_str);
			// merge params from the url with params array from caller
			$params = array_merge($params, $parsed_query);
		}
	
		// Remove oauth_signature from params array if present
		if (isset($params['oauth_signature'])) {
			unset($params['oauth_signature']);
		}
	
		// Create the signature base string. Yes, the $params are double encoded.
		$base_string = $this->rfc3986_encode(strtoupper($http_method)) . '&' .
				$this->rfc3986_encode($this->normalize_url($url)) . '&' .
				$this->rfc3986_encode($this->oauth_http_build_query($params));
	
		
	
		return $base_string;
	}
	
	public function normalize_url($url){
		$parts = parse_url($url);
		
		$scheme = $parts['scheme'];
		$host = $parts['host'];
		$port = isset($parts['port'])?$parts['port']:false;
		$path = $parts['path'];
	
		$port = 443;
		$scheme ='https';
	
		return "$scheme://$host$path";
	}
	
	
	public function rfc3986_encode($raw_input){
		if (is_array($raw_input)) {
			return array_map(array($this,'rfc3986_encode'), $raw_input);
		} else if (is_scalar($raw_input)) {
			return str_replace('%7E', '~', rawurlencode($raw_input));
		} else {
			return '';
		}
	}
	
	public function rfc3986_decode($raw_input){
		return rawurldecode($raw_input);
	}
	
	
	public function oauth_parse_str($query_string){
		$query_array = array();
	
		if (isset($query_string)) {
	
			// Separate single string into an array of "key=value" strings
			$kvpairs = explode('&', $query_string);
	
			// Separate each "key=value" string into an array[key] = value
			foreach ($kvpairs as $pair) {
				list($k, $v) = explode('=', $pair, 2);
	
				// Handle the case where multiple values map to the same key
				// by pulling those values into an array themselves
				if (isset($query_array[$k])) {
					// If the existing value is a scalar, turn it into an array
					if (is_scalar($query_array[$k])) {
						$query_array[$k] = array($query_array[$k]);
					}
					array_push($query_array[$k], $v);
				} else {
					$query_array[$k] = $v;
				}
			}
		}
	
		return $query_array;
	}
	
	
	public function build_oauth_header($params, $realm=''){
		$header = 'Authorization: OAuth realm="' . $realm . '"';
		foreach ($params as $k => $v) {
			if (substr($k, 0, 5) == 'oauth') {
				$header .= ',' . $this->rfc3986_encode($k) . '="' . $this->rfc3986_encode($v) . '"';
			}
		}
		return $header;
	}
	
	
	function do_get($url, $port=80, $headers=NULL){
		$retarr = array(); // Return value
	
		$curl_opts = array(CURLOPT_URL => $url,
				CURLOPT_PORT => $port,
				CURLOPT_POST => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_RETURNTRANSFER => true);
	
		if ($headers) { $curl_opts[CURLOPT_HTTPHEADER] = $headers; }
	
		$response = $this->do_curl($curl_opts);
	
		if (! empty($response)) { $retarr = $response; }
	
		return $retarr;
	}
	
	
	function do_curl($curl_opts){
		global $debug;
	
		$retarr = array(); // Return value
	
		if (! $curl_opts) {
		
			return $retarr;
		}
	
		// Open curl session
		$ch = curl_init();
		if (! $ch) {
		
			return $retarr;
		}
	
		// Set curl options that were passed in
		curl_setopt_array($ch, $curl_opts);
	
		// Ensure that we receive full header
		curl_setopt($ch, CURLOPT_HEADER, true);
	
		if ($debug) {
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);
			curl_setopt($ch, CURLOPT_VERBOSE, true);
		}
	
		// Send the request and get the response
		ob_start();
		$response = curl_exec($ch);
		$curl_spew = ob_get_contents();
		ob_end_clean();
		
	
		// Check for errors
		if (curl_errno($ch)) {
			$errno = curl_errno($ch);
			$errmsg = curl_error($ch);
			
			curl_close($ch);
			unset($ch);
			return $retarr;
		}
	
		
	
		// Get information about the transfer
		$info = curl_getinfo($ch);
	
		// Parse out header and body
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size );
	
		// Close curl session
		curl_close($ch);
		unset($ch);
	
		
	
		// Set return value
		array_push($retarr, $info, $header, $body);
	
		return $retarr;
	}
	
	
	function do_post($url, $postbody, $port=80, $headers=NULL){
		$retarr = array(); // Return value
	
		$curl_opts = array(CURLOPT_URL => $url,
				CURLOPT_PORT => $port,
				CURLOPT_POST => true,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_POSTFIELDS => $postbody,
				CURLOPT_RETURNTRANSFER => true);
	
		if ($headers) { $curl_opts[CURLOPT_HTTPHEADER] = $headers; }
	
		$response = $this->do_curl($curl_opts);
	
		if (! empty($response)) { $retarr = $response; }
	
		return $retarr;
	}
	
}