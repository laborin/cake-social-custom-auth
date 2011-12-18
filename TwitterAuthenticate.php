<?php
	/**
	 * TwitterAuthenticate file
	 *
	 * PHP 5
	 *
	 * Copyright 2011, Daniel Auener, daniel.auener@gmail.com
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice
	 * 
	 * NOTE: The implementation of the twitter custom auth object is mainly based on
	 * the OAuth examples on http://code.42dh.com/oauth/.
	 *
	 * @copyright     Copyright 2011, Daniel Auener, daniel.auener@gmail.com
	 * @link          http://github.com/danielauener
	 * @since         CakePHP(tm) v 2.0
	 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
	 */

	App::uses('BaseAuthenticate', 'Controller/Component/Auth');
	App::import('Vendor', 'OAuth', array('file' => 'OAuth'.DS.'oauth_consumer.php'));

	/**
	 * TwitterAuthenticate integrates Twitter authentication with the
	 * CakePHP 2.0 Auth-Component. It uses Custom Authentication Objects
	 * as described in 
	 * http://book.cakephp.org/2.0/en/core-libraries/components/authentication.html?#creating-custom-authentication-objects
	 */
	class TwitterAuthenticate extends BaseAuthenticate {	

		/**
		 * fill in your settings, AppId, AppSecret and the url to your login action. 
		 * To get an AppId you have create an App on http://dev.twitter.com/
		 */
		var $settings = array(
		   "app_id" => "fAVLdC3Hgdx7AFcuANp1ug",
		   "app_secret" => "p5VuTzXuC1w7YnrAWQFYfaLlfjhxF7W635br6UHiSJY",
		   "url" => "http://connect.local/users/login?twitter_callback=1"
		); 
				
		
		/**
		 * Uses the oauth vendor classes to connect to twitter
		 * @param CakeSession $session
		 * @param CakeResponse $response
		 */
		private function getTwitterToken($session,$response) {
	        $consumer = new OAuth_Consumer($this->settings["app_id"], $this->settings["app_secret"]);
	        $requestToken = $consumer->getRequestToken('https://api.twitter.com/oauth/request_token', $this->settings["url"]);
	        $session->write('twitter_request_token_key', $requestToken->key);
	        $session->write('twitter_request_token_secret', $requestToken->secret);
	        $response->header('Location', 'https://api.twitter.com/oauth/authorize?oauth_token='.$requestToken->key);
		}		
		
	            	
		/**
		 * Checks if the twitter user has already a database entry and 
		 * creates one if not. Returns the user object from the database
		 * @param object $twitteruser the user object retrieved from twitter
 		 */
		private function checkUser($twitteruser) {
			App::uses('User', 'Model');
			$User = new User();
			$user = $User->find("first",array("conditions" => array("username" => "twitter-".$twitteruser)));
			if (!$user) {
				$user = array(
					"User" => array(
						"username" => "twitter-".$twitteruser,
					)
				);
				$User->create();
				$User->save($user);
				$user["User"]["id"] = $User->getLastInsertID();
			}
			return $user;
		}	            	

		
		/**
		 * Authenticate method is called from the AuthComponent when the user is logging in
		 * @param CakeRequest $request
		 * @param CakeResponse $response
		 */
		public function authenticate(CakeRequest $request, CakeResponse $response) {
			$session = new CakeSession();
			if (isset($request->data["Twitter"]) && $request->data["Twitter"]["login"] = 1) {
				
				// if the user clicks on twitter auth, we connect to twitter and write the 
				// twitter token to the session
				$this->getTwitterToken($session,$response);
			}
			if (isset($request->query["twitter_callback"]) && $request->query["twitter_callback"] = 1 && isset($request->query["oauth_token"])) {
				
				// read the twitter callback data and create/find a matching user in the database
				$requestToken = new OAuthToken($session->read('twitter_request_token_key'),$session->read('twitter_request_token_secret'));
				$consumer = new OAuth_Consumer($this->settings["app_id"], $this->settings["app_secret"]);
				$accessToken = $consumer->getAccessToken('https://api.twitter.com/oauth/access_token',$requestToken);
				$twitter_user = json_decode($consumer->get($accessToken->key, $accessToken->secret, 'https://api.twitter.com/1/account/verify_credentials.json')->body);
				if (isset($twitter_user->id_str)) {
					return $this->checkUser($twitter_user->id_str);
				}
			}
	        return false;
		}	
    	
	}
?>