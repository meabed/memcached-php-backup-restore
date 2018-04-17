memcached-php-backup-restore
============================

Save / Restore data from Memcache to File and vice versa !

I have written this little script in order to Stop/Start/Restart memcache server without sacrificing any cached data.

Now I can dump all memcache data in a file, stop/start/restart memcache server and restore the data from the saved file.

PS : Data saved as array serialized in the file! if you save it as binary data it won't be restored.

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