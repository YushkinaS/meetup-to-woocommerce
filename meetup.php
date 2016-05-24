<?php
/*Copyright 2013 Fokke Zandbergen

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

   http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/

class Meetup {
	const BASE = 'https://api.meetup.com';

	protected $_parameters = array(
		'sign' => 'true',
	);

	public function __construct(array $parameters = array()) {
		$this->_parameters = array_merge($this->_parameters, $parameters);
	}
	
	public function getEvents(array $parameters = array()) {
		return $this->get('/2/events', $parameters);
	}
	
	public function getPhotos(array $parameters = array()) {
		return $this->get('/2/photos', $parameters);
	}
	
	public function getDiscussionBoards(array $parameters = array()) {
		return $this->get('/:urlname/boards', $parameters);
	}
	
	public function getDiscussions(array $parameters = array()) {
		return $this->get('/:urlname/boards/:bid/discussions', $parameters);
	}

	public function getMembers(array $parameters = array()) {
		return $this->get('/2/members', $parameters);
	}

	public function getNext($response) {
		if (!isset($response) || !isset($response->meta->next) || ('' == $response->meta->next))
		{
			return false;
		}
		return $this->get_url($response->meta->next);
	}
	
	public function get($path, array $parameters = array()) {
		$parameters = array_merge($this->_parameters, $parameters);
	
		if (preg_match_all('/:([a-z]+)/', $path, $matches)) {
			
			foreach ($matches[0] as $i => $match) {
			
				if (isset($parameters[$matches[1][$i]])) {
					$path = str_replace($match, $parameters[$matches[1][$i]], $path);
					unset($parameters[$matches[1][$i]]);
				} else {
					throw new Exception("Missing parameter '" . $matches[1][$i] . "' for path '" . $path . "'.");
				}
			}
		}

		$url = self::BASE . $path . '?' . http_build_query($parameters);

		return $this->get_url($url);
	}

	protected function get_url($url) {
	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept-Charset: utf-8"));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$content = curl_exec($ch);
		
		if (curl_errno($ch)) {
			$error = curl_error($ch);
			curl_close($ch);
			
			throw new Exception("Failed retrieving  '" . $url . "' because of ' " . $error . "'.");
		}
		
		$response = json_decode($content);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		curl_close($ch);
		
		if ($status != 200) {
						
			if (isset($response->errors[0]->message)) {
				$error = $response->errors[0]->message;
			} else {
				$error = 'Status ' . $status;
			}
			
			throw new Exception("Failed retrieving  '" . $url . "' because of ' " . $error . "'.");
		}

		if (isset($response) == false) {
		
			switch (json_last_error()) {
				case JSON_ERROR_NONE:
					$error = 'No errors';
				break;
				case JSON_ERROR_DEPTH:
					$error = 'Maximum stack depth exceeded';
				break;
				case JSON_ERROR_STATE_MISMATCH:
					$error = ' Underflow or the modes mismatch';
				break;
				case JSON_ERROR_CTRL_CHAR:
					$error = 'Unexpected control character found';
				break;
				case JSON_ERROR_SYNTAX:
					$error = 'Syntax error, malformed JSON';
				break;
				case JSON_ERROR_UTF8:
					$error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
				break;
				default:
					$error = 'Unknown error';
				break;
			}
    
			throw new Exception("Cannot read response by  '" . $url . "' because of: '" . $error . "'.");
		}
		
		return $response;
	}
}

