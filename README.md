memcached-php-backup-restore
============================

[![Build Status](https://travis-ci.org/meabed/memcached-php-backup-restore.svg?branch=master)](https://travis-ci.org/meabed/memcached-php-backup-restore)
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

```
Example Usage:
php m.php -h 127.0.0.1 -p 11211 -op backup
php m.php -h 127.0.0.1 -p 11211 -op restore

-h : Memcache Host address ( default is 127.0.0.1 )
-p : Memcache Port ( default is 11211 )
-op : Operation is required ( available options is: restore, backup )
-f : File path (default is __DIR__.'/memcacheData.txt') relative path, or absolute path

NB: The -h address can now contain multiple memcache servers in a 'pool' configuration
    these can be listed in an comma seperated list and each my optionally have a port
    number associated with it by seperating with a colon thus: 
        192.168.1.100:11211,192.168.1.101,192.168.1.100:11211
    In the above example the are two physical machines but 192.168.1.100 is running two
    instances of memcached. 
    The servers MUST be listed here in the same order that is used to write to the pool
    elsewhere in order for the keys to be correctly retrieved.

```


## Contributing

Anyone is welcome to [contribute](CONTRIBUTING.md), however, if you decide to get involved, please take a moment to review the guidelines:

* [Only one feature or change per pull request](CONTRIBUTING.md#only-one-feature-or-change-per-pull-request)
* [Write meaningful commit messages](CONTRIBUTING.md#write-meaningful-commit-messages)
* [Follow the existing coding standards](CONTRIBUTING.md#follow-the-existing-coding-standards)


## License

The code is available under the [MIT license](LICENSE.md).