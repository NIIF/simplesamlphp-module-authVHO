# SSP IdP authsource for aai/vho

## Install

`composer require niif/simplesamlphp-module-authvho`

## Configure

config/authsources.php

```
'default-sp' => array(
             'authVHO:authVHO',
             'vho_login_url' => 'https://your.vho.com/loginForIdp'
         ),
```