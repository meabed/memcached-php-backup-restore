memcached-php-backup-restore
============================

[![Build Status](https://travis-ci.org/meabed/memcached-php-backup-restore.svg?branch=master)](https://travis-ci.org/meabed/memcached-php-backup-restore)
[![COMMIT](https://images.microbadger.com/badges/commit/meio/go-swap-server.svg)](https://microbadger.com/images/meio/go-swap-server)
[![Blog URL](https://img.shields.io/badge/Author-blog-green.svg?style=flat-square)](https://meabed.com)

Backup/Restore memcached data to file and vice-versa!

I have written this little script in order to Stop/Start/Restart memcache server without sacrificing any cached data.

Now I can dump all memcache data in a file, stop/start/restart memcache server and restore the data from the saved file.

###Usage example

Example Usage:

```
php m.php -h 127.0.0.1 -p 11211 -op backup
```

```
php m.php -h 127.0.0.1 -p 11211 -op restore
```

-h : Memcache Host address ( default is 127.0.0.1 )  
-p : Memcache Port ( default is 11211 )  
-op : Operation is required !! ( available options are : restore , backup )  
-f : File name (default is memcacheData.txt)