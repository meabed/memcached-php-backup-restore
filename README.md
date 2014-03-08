memcached-php-backup-restore
============================

Save / Restore data from Memcache to File and vice versa !

I have wrote this little script to allow me Restart memcache server without loosing data.

So i dump all the memcache data in file, restart memcache and restore the data from the saved file.

PS : Data saved as array serialized in file ! if you store binary data it won't be restored.

###Usage example

Example Usage : php m.php -h 127.0.0.1 -p 112112 -op restore  
-h : Memcache Host address ( default is 127.0.0.1 )  
-p : Memcache Port ( default is 11211 )  
-p : Operation is required !! ( available options is : restore , backup )  
