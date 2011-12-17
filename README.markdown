CakePHP 2.0: Creating Custom Authentication Objects for Facebook, Twitter and OpenId
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

```
$ ./Console/cake bake model user
$ ./Console/cake bake controller user --public
$ ./Console/cake bake view user
```