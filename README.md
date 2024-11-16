**cas-oauth-laravel**
<hr />

<ins>**Configuration :**</ins>

**⚠️ Note: You need to add checked variables into the `.env` file and create a service, else, the package won't route anything.**

* [ ] `CAS_PROPERTY` : Property used for generating the CAS ticket *(default : `id`)*
* [X] `OAUTH_PROVIDER` : Socialite driver to use.
* [ ] `OAUTH_SCOPES` : Scopes to use, separated with commas *(default : `openid,profile,email`)*.
* [X] `OAUTH_CLIENT_ID` : ID of your OAuth application.
* [X] `OAUTH_CLIENT_SECRET` : Secret of your OAuth application.
* [ ] `OAUTH_PARAMS` : Custom args to pass to the OAuth provider, in format of `key=value`, separated with commas.

<br /><ins>**Requirements :**</ins>

* Install the package using `composer require friezer-85/cas-oauth-laravel` and install your Socialite's driver. BOOM! You're ready to go.
* Create a service in the `config/services.php` file, like this :
```php
return [
  ...
  
  'cas' => [
    'https://friezer.eu/(.*)',
  ],
];
```
