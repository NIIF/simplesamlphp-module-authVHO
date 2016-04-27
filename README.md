SSP IdP authsource for aai/vho

composer require niif/simplesamlphp-module-authVHO

config/authsources.php

```
'authVHO' => array(
             'authvho:authVHO',
             'vho_login_url' => 'https://your.vho.com/loginForIdp'
         ),
```