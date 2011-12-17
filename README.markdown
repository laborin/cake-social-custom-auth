CakePHP 2.0: Custom Auth Objects for Facebook, Twitter and OpenId
====================================================================================

Since CakePHP 2.0 you can use custom authentication objects to integrate social 
network authentication in your web app.

Minimal Cake setup
------------------

After you have your basic cake setup running, create a database for your users
and bake your models, views and controllers.

```SQL
CREATE TABLE `users` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`username` char(50) DEFAULT NULL,
	`password` char(40) DEFAULT NULL,
	PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1
```

```terminal
$ ./Console/cake bake model user
$ ./Console/cake bake controller user --public
$ ./Console/cake bake view user
```

To get the auth component working you have to set it up in the users controller 
(or app controller for application-wide auth).

```php
public $components = array(
	'Auth',
	'Session'
);
```

You need to create a login and logout action, as well as a login view.

```php
public function login() {
	if ($this->request->is('post') || $this->request->is('get')) {
		
		// facebook requests a csrf protection token
        if (!($csrf_token = $this->Session->read("state"))) {
			$csrf_token = md5(uniqid(rand(), TRUE));
			$this->Session->write("state",$csrf_token); //CSRF protection
		}
		$this->set("csrfToken",$csrf_token);
		
		// login 		
		if ($this->Auth->login()) {
			return $this->redirect($this->Auth->redirect());
		} else {
			$this->Session->setFlash(__('Your login failed'), 'default', array(), 'auth');
		}
	}
}
```

```php
function logout(){
	$this->Session->setFlash('Logged out.');
	$this->redirect($this->Auth->logout());
}
```

Create links/forms to give the user the possibility to chose a authentication service in your
login.ctp view. The openID authentification can be used for multiple services (myOpenId, google,
yahoo etc.), you just have to change the openid url.

Google: https://www.google.com/accounts/o8/id
Yahoo: http://yahoo.com/

```phphtml
<h1>Sign in</h1>
<p>Sign in with one of the services below.</p>
<h2>Facebook</h2>
<a href="http://www.facebook.com/dialog/oauth?
			client_id=2339307566xxxxx&
			redirect_uri=http://connect.local/users/login&
			state=<?php echo $csrfToken; ?>&
			scope=email">Login with Facebook</a>
			
<h2>Twitter</h2>
<?php
	echo $this->Form->create('User', array('type' => 'post', 'action' => 'login'));
	echo $this->Form->hidden('Twitter.login', array('label' => false,'value' => '1'));
	echo $this->Form->submit("Login with twitter",array('label' => false));
	echo $this->Form->end();
?>
<h2>OpenID</h2>
<?php
	echo $this->Form->create('User', array('type' => 'post', 'action' => 'login'));
	echo $this->Form->hidden('OpenidUrl.openid', array('label' => false,'value' => 'http://myopenid.com/'));
	echo $this->Form->submit("login with openid",array('label' => false,));
	echo $this->Form->end();
?>			
```