<?php
	/**
	 * OpenidAuthenticate file
	 *
	 * PHP 5
	 *
	 * Copyright 2011, Daniel Auener, daniel.auener@gmail.com
	 *
	 * Licensed under The MIT License
	 * Redistributions of files must retain the above copyright notice
	 * 
	 * NOTE: The implementation of this custom auth object is mainly based on
	 * the OpenId examples on http://code.42dh.com/openid/  
	 *
	 * @copyright     Copyright 2011, Daniel Auener, daniel.auener@gmail.com
	 * @link          http://github.com/danielauener
	 * @since         CakePHP(tm) v 2.0
	 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
	 */

	App::uses('CakeSession', 'Model/Datasource');
	App::uses('BaseAuthenticate', 'Controller/Component/Auth');
	
	App::uses('Model', 'Model');
    //This point don't have an AppModel class defined, so...
    class AppModel extends Model {
    }
	
	/**
	 * OpenidAuthenticate integrates OpenId authentication with the
	 * CakePHP 2.0 Auth-Component. It uses Custom Authentication Objects
	 * as described in 
	 * http://book.cakephp.org/2.0/en/core-libraries/components/authentication.html?#creating-custom-authentication-objects
	 */
	class OpenidAuthenticate extends BaseAuthenticate {
		
		/**
		 * fill in your settings, realm has to be the base url of your webapp,
		 * return_to should be you login action
		 */
		var $settings = array(
		   "realm" => "http://connect.local",
		   "return_to" => "http://connect.local/users/login"
		); 

		
		/**
		 * show a form which redirects the user to the openID service
		 * @param unknown_type $request
		 * @param unknown_type $returnTo
		 * @param unknown_type $realm
		 */
		private function showFormWithAutoSubmit($request, $returnTo, $realm) {
			$session = new CakeSession();
	        $formId = 'openid_message';
	        $formHtml = $request->formMarkup($realm, $returnTo, false , array('id' => $formId));
	
	        if (Auth_OpenID::isFailure($formHtml)) {
	            throw new Exception('Could not redirect to server: '.$formHtml->message);
	        }
	
	        echo '<html><head><title>' . __('OpenID Authentication Redirect') . '</title></head>'.
	             "<body onload='document.getElementById(\"".$formId."\").submit()'>".
	             $formHtml.'</body></html>';
	        exit;
	    }		
		
	    
	    /**
	     * Import the OpenID library by JanRain (http://github.com/openid/php-openid)
	     * Download the complete package with pear on http://code.42dh.com/openid/
 	     */
		private function importOpenid() {
	        $pathExtra = APP.'Vendor'.DS . PATH_SEPARATOR . APP.'Vendor'.DS . 'pear' . DS;
	        $path = ini_get('include_path');
	        $path = $pathExtra . PATH_SEPARATOR . $path;
	        ini_set('include_path', $path);
			App::import('Vendor', 'consumer', array('file' => 'Auth'.DS.'OpenID'.DS.'Consumer.php'));
			App::import('Vendor', 'filestore', array('file' => 'Auth'.DS.'OpenID'.DS.'FileStore.php'));
		}		

		
		/**
		 * Checks if the openID user has already a database entry and 
		 * creates one if not. Returns the user object from the database
		 * @param object $openiduser the user object retrieved from the openID service
 		 */
		private function checkUser($openiduser) {
			App::uses('User', 'Model');
			$User = new User();
			$user = $User->find("first",array("conditions" => array("username" => "openid-".$openiduser)));
			if (!$user) {
				$user = array(
					"User" => array(
						"username" => "openid-".$openiduser,
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
			$this->importOpenid();
			$realm = $this->settings["realm"];
        	$returnTo = $this->settings["return_to"];
	        if (isset($request->data['OpenidUrl']) && ($url = $request->data['OpenidUrl']['openid']) != '') {
	        	$consumer = new Auth_OpenID_Consumer(new Auth_OpenID_FileStore(TMP.'openid'));
	            $authRequest = $consumer->begin($url);
	            if ($authRequest->shouldSendRedirect()) {
            		$this->redirect($authRequest, $returnTo, $realm);
        		} else {
            		$this->showFormWithAutoSubmit($authRequest, $returnTo, $realm);
        		}  
	        } else if (isset($request->query) && isset($request->query['openid_mode'])) {
	        	$consumer = new Auth_OpenID_Consumer(new Auth_OpenID_FileStore(TMP.'openid'));
	        	$query = Auth_OpenID::getQuery();
	        	unset($query['url']);        
		        $response = $consumer->complete($returnTo, $query);
	            if ($response->status == Auth_OpenID_SUCCESS) {
	            	// md5 the identity url to write it to the database
					return $this->checkUser(md5($response->identity_url));
	            }
	        }
	        return false;
		}	
    	
	}
?>