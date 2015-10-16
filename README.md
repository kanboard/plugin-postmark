Postmark plugin for Kanboard
============================

- Send emails through Postmark API
- Create tasks from incoming emails

Author
------

- Frederic Guillot
- License MIT

Installation
------------

- Create a folder **plugins/Postmark**
- Copy all files under this directory

Configuration
-------------

Define those constants in your `config.php` file:

```php
define('MAIL_TRANSPORT', 'postmark');
define('POSTMARK_API_TOKEN', 'YOUR_API_TOKEN');
define('MAIL_FROM', 'CONFIGURED_EMAIL_IN_POSTMARK');
```
