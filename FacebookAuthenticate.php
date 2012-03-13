<?php
	App::uses('CakeSession', 'Model/Datasource');
	App::uses('BaseAuthenticate', 'Controller/Component/Auth');
	
	App::uses('Model', 'Model');
    //This point don't have an AppModel class defined, so...
    class AppModel extends Model {
    }
	
	class FacebookAuthenticate extends BaseAuthenticate {

		var $settings = array(
		   "app_id" => "233930756678530",
		   "app_secret" => "ed1bbd7cc397d85514e8dda70effa128",
		   "url" => "http://connect.local/users/login"
		); 
		
        public function authenticate(CakeRequest $request, CakeResponse $response) {
           $session = new CakeSession();
            if (isset($request->query) && isset($request->query['code']) && isset($request->query['state'])) {
                if($request->query['state'] == $session->read('state')) {
                    $token_url = "https://graph.facebook.com/oauth/access_token?"
                        . "client_id=" . $this->settings["app_id"]
                        . "&redirect_uri=" . urlencode($this->settings["url"])
                           . "&client_secret=" . $this->settings["app_secret"]
                           . "&code=" . $request->query['code'];
                           
                    $response = file_get_contents($token_url);
                    $params = null;
                    parse_str($response, $params);
                    if (isset($params['access_token'])) {
                        $graph_url = "https://graph.facebook.com/me?access_token=".$params['access_token'];
                         $fb_user = json_decode(file_get_contents($graph_url));
                         App::uses('User', 'Model');
                        $User = new User();
                        $user = $User->find("first",array("conditions" => array("username" => "facebook-".$fb_user->id)));
                        if (!$user) {
                            $user = array(
                                "User" => array(
                                    "username" => "facebook-".$fb_user->id,
                                )
                            );
                            $User->create();
                            $User->save($user);
                            $user["User"]["id"] = $User->getLastInsertID();
                        }
                        return $user["User"];
                    }
                }
            }    
            return false;        
        }    
    	
	}
?>