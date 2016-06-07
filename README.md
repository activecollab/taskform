How to create form to create a task in Active Collab
=======


# How to use this form on your web site


If you want to include this form on your web site just take a div with id="main-form" and include it into your web site. After adding this you need to copy configure.php file. That file is page that is going to open after successful send.You can modify HTML as you wish. And your name of your company, name of the app that you are developing, your email address and password in:
```php
$authenticator = new \ActiveCollab\SDK\Authenticator\Cloud('name of your company', 'name of the app that you are developing', 'your email address', 'password');
```

And add you Active Collab instance id:
```php
$token = $authenticator->issueToken(<here>);
```

And add your project id into url:
```php
$result = $client->post(projects/<here>/tasks, [
```
We have add javascript to prevent double submit of the form.