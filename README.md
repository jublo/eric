eric
============
*The easy way to send MIME mails with PHP.*

Copyright (C) 2011-2014 Jublo Solutions <support@jublo.net>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

### Requirements

- PHP 5.0.0 or higher
- Working PHP mail() function


Including the class
-------------------
Loading the class is possible by using [Composer](http://getcomposer.org).
You can also manually require the class file, like this:

```php
// include Eric class
require_once 'vendor/eric.php';
```

Mail text encoding
------------------
MIME mails sent with Eric are encoded with UTF-8 headers.
The HTML or text content you hand over to Eric should be encoded in UTF-8, too.

Usage examples
--------------
You give Eric either a plain text content, or a HTML-formatted one.
Eric will convert your data to the respective other format automatically.

### Sending a mail with text-only content

```php
Eric::send_mail(
    $to        = 'to@example.com',
    $subject   = 'Test message text-only',
    $content   = 'A simple text-only mail.',
    $html      = false,
    $from      = 'sender@example.com',
    $from_name = 'Sender name'
);
```

### Sending a mail with HTML-formatted content

```php
Eric::send_mail(
    $to        = 'to@example.com',
    $subject   = 'Test message html',
    $content   = 'A simple <strong>html</strong> mail.',
    $html      = true,
    $from      = 'sender@example.com',
    $from_name = 'Sender name'
);
```

How Do I…?
----------

### …send an e-mail with attachments?

You should have the attachments to send as files on the local disk.
Collect the files and their names in an array, like this:

```php
// initialize files array
$collected_files = array();

// for each file, repeat this step:
$collected_files[] = array(
    'wp-content/uploads/some-file.txt', // first array field is filename on disk
    'name-in-attachment.txt'            // second is desired file name in e-mail
);


// send out mail with attachments
```php
Eric::send_mail(
    $to        = 'to@example.com',
    $subject   = 'Test message with attm',
    $content   = 'A simple mail with attachments.',
    $html      = false,
    $from      = 'sender@example.com',
    $from_name = 'Sender name',
    $files     = $collected_files
);
```

### …use an image attachment inline in the HTML?

HTML mails may contain images.
For example, many companies include their logo in the e-mail header.
To protect your privacy, most e-mail clients block external images by default,
since the e-mail sender could track when an image was accessed on their server.

To circumvent this, HTML mails can use images attached to the e-mail as files.
This works with using a special format for the `<img src="">` parameter.

To get an idea how this works with Eric, see the following example:

```php
// send mail with inline html image

$collected_files = array();
$collected_files[] = array(
    'local/folder/some-logo.gif',
    'logo.gif',
    true                          // Set the 3rd param to true to tell Eric
                                  // that you want to use this image inline
);

Eric::send_mail(
    $to        = 'to@example.com',
    $subject   = 'Test message with inline image',
    $content   = 'A mail with image.<br><img src="cid:logo.gif" alt="Logo">',
    $html      = true,
    $from      = 'sender@example.com',
    $from_name = 'Sender name',
    $files     = $collected_files
);
```
