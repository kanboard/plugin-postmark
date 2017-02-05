Postmark plugin for Kanboard
============================

[![Build Status](https://travis-ci.org/kanboard/plugin-postmark.svg?branch=master)](https://travis-ci.org/kanboard/plugin-postmark)

Use [Postmark](https://postmarkapp.com/) to create tasks directly by email or to send notifications.

- Send emails through Postmark API
- Create tasks from incoming emails

Author
------

- Frederic Guillot
- License MIT

Requirements
------------

- Kanboard >= 1.0.39
- Postmark API credentials

Installation
------------

You have the choice between 3 methods:

1. Install the plugin from the Kanboard plugin manager in one click
2. Download the zip file and decompress everything under the directory `plugins/Postmark`
3. Clone this repository into the folder `plugins/Postmark`

Note: Plugin folder is case-sensitive.

Kanboard configuration
----------------------

There are two different ways to configure this plugin: with the user interface or with the custom config file.

### Configuration via the user interface

Go to **Settings > Integrations > Postmark**:

![Postmark](https://cloud.githubusercontent.com/assets/323546/15765659/519ef59a-2905-11e6-9caf-1d579e5111ca.png)

### Configuration via the config file

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

The sender email address must be same as the user profile in Kanboard and the user must be member of the project.

Troubleshooting
---------------

- Test the webhook url from the Postmark console, you should have a status code `200 OK`

Changes
-------

### Version 1.0.7

- Use project email address instead of project identifier
