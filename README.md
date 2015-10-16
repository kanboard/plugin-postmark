Postmark plugin for Kanboard
============================

Use [Postmark](https://postmarkapp.com/) to create tasks directly by email or to send notifications.

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

Use Postmark to send emails
---------------------------

Define those constants in your `config.php` file to send notifications with Postmark:

```php
// We choose "postmark" as mail transport
define('MAIL_TRANSPORT', 'postmark');

// Copy and paste your Postmark API token
define('POSTMARK_API_TOKEN', 'COPY HERE YOUR POSTMARK API TOKEN');

// Be sure to use the Postmark configured sender email address
define('MAIL_FROM', 'sender-address-configured-in-postmark@example.org');
```

Use Postmark to create tasks from emails
----------------------------------------

Just follow the [official documentation about inbound email processing](http://developer.postmarkapp.com/developer-process-configure.html).
Basically, you have to forward your own domain or subdomain to a specific Postmark email address.

The Kanboard webhook url is displayed in **Settings > Integrations > Postmark**

1. Be sure that your users have an email address in their profiles
2. Assign a project identifier to the desired projects: **Project settings > Edit**
3. Try to send an email to your project: something+myproject@mydomain.tld

Troubleshootings
----------------

- Test the webhook url from the Postmark console, you should have a status code `200 OK`
