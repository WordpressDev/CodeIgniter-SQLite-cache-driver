CodeIgniter SQLite Cache Driver
=========================================

SQLite is a single disk file relational database system that may be available on your webserver. This driver saves the cache data as a key value pair with an expire timestamp.

Requirements
------------

 - PHP PDO  
   The PHP Data Objects (PDO) extension defines a lightweight, consistent interface for accessing databases in PHP. Each database driver that implements the PDO interface can expose database-specific features as regular extension functions.  
   http://www.php.net/manual/en/pdo.installation.php
 - PDO_SQLITE  
   PDO_SQLITE is a driver that implements the PHP Data Objects (PDO) interface to enable access to SQLite 3 databases.  
   http://www.sqlite.org

Installation
------------

Download the code from github and place the Cache_sqlite.php file in your application/libraries/Cache/drivers/ folder.

CodeIgniter will not auto-detect this file so you HAVE to edit one of the system files to make this driver available. You do this by editing system/libraries/Cache/Cache.php and add 'cache_sqlite' to the supported drivers like this:

	protected $valid_drivers = array(
		'cache_apc', 'cache_file', 'cache_memcached', 'cache_dummy', 'cache_sqlite'
	);

Now you should be able to load the sqlite driver:

	$this->load->driver('cache', array('adapter' => 'sqlite'));

Usage
-----

All of the general cache driver methods are available:

- get($key) - attempt to fetch an item from the cache store. If the item does not exist, the function will return FALSE
- save($key, $data, $ttl) - save an item to the cache store. If saving fails, the function will return FALSE. The optional third parameter (Time To Live) defaults to 60 seconds
- delete($key) - delete a specific item from the cache store. If item deletion fails, the function will return FALSE
- clean() - 'clean' the entire cache. If the deletion of the cache files fails, the function will return FALSE
- cache_info() - return information on the entire cache
- get_metadata($key) - return detailed information on a specific item in the cache

One of the advantages of using SQLite as a cache driver is that it is really easy to remove all of the expired caches with only 1 query. I have made this function available:

- flush() - flush all expired cache items