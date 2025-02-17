This is a simple image uploader I wrote for myself and decided to share.  The
goal is you can just drop it into any server with PHP support and it'll
hopefully work.  The main usecase is for uploading pictures from your phone to
your own hosting to share with others, embed in forums, and so on.

Licensed with the GNU General Public License version 2 (GPLv2). See COPYING for
full terms.

Features
--------
- Strips GPS info from images.
- Provides scaled down images at reasonable sizes for embedding.
- Reasonable UI for using on a phone.
- Has buttons to copy URLs and phpBB-style embedding code into the clipboard.

Installation
------------
- Requires PHP 8 or later.
- Edit `user.php` and resolve the couple of `TODO` entries as instructed. You
  will need to add your URL and implement an authentication algorithm. (Like a
  simple password string check or something. This isn't Fort Knox.)
- Then just drop the files in this repo on your host:
```
upload.php
user.php
pel/
```
- You may need to configure your PHP installation. For example I have:
```php
file_uploads = True
max_input_time = 60
memory_limit = 128M
post_max_size = 96M
upload_max_filesize = 64M
```
