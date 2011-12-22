<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * @name		CodeIgniter SQLite cache driver
 * @author		Jens Segers
 * @link		http://www.jenssegers.be
 * @license		MIT License Copyright (c) 2011 Jens Segers
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class CI_Cache_sqlite extends CI_Driver {
    
    var $cache_path;
    var $cache_file;
    var $auto_flush;
    
    // the SQLite object
    protected $sqlite;
    
    /**
     * Constructor
     */
    public function __construct() {
        $CI = & get_instance();
        
        // get cache_path from config if available
        $path = $CI->config->item('cache_path');
        $this->cache_path = ($path == '') ? APPPATH . 'cache/' : $path;
        
        // get cache_file name from config if available
        $cache_file = $CI->config->item('cache_file');
        $this->cache_file = ($cache_file == '') ? 'cache.sqlite' : $cache_file;
        
        // get auto_flush name from config if available
        $auto_flush = $CI->config->item('cache_autoflush');
        $this->auto_flush = ($auto_flush == '') ? FALSE : $auto_flush;
        
        // initialize the database
        try {
            $this->sqlite = new PDO('sqlite:' . $this->cache_path . $this->cache_file);
            $this->sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // create cache database
            $this->sqlite->exec("CREATE TABLE IF NOT EXISTS cache (id TEXT PRIMARY KEY, data BLOB, expire INTEGER)");
            
            // don't verify data on disk
            $this->sqlite->exec("PRAGMA synchronous = OFF");
            // turn off rollback
            $this->sqlite->exec("PRAGMA journal_mode = OFF");
            // peridically clean the database
            $this->sqlite->exec("PRAGMA auto_vacuum = INCREMENTAL");
        } catch ( PDOException $e ) {
            show_error($e->getMessage());
        }
    }
    
    // ------------------------------------------------------------------------
    

    /**
     * Fetch from cache
     *
     * @param 	mixed		unique key id
     * @return 	mixed		data on success/false on failure
     */
    public function get($id) {
        try {
            $query = $this->sqlite->query("SELECT * FROM cache WHERE id = '" . (string) $id . "'");
            
            // cache miss
            if (!$query || !$data = $query->fetch(PDO::FETCH_ASSOC))
                return FALSE;
            
     // time to live elapsed
            if (time() > $data['expire']) {
                $this->delete($id);
                return FALSE;
            }
            
            return unserialize($data['data']);
        } catch ( PDOException $e ) {
            return FALSE;
        }
    }
    
    // ------------------------------------------------------------------------
    

    /**
     * Save into cache
     *
     * @param 	string		unique key
     * @param 	mixed		data to store
     * @param 	int			length of time (in seconds) the cache is valid 
     * - Default is 60 seconds
     * @return 	boolean		true on success/false on failure
     */
    public function save($id, $data, $ttl = 60) {
        try {
            // insert or replace data
            $query = $this->sqlite->query("INSERT OR REPLACE INTO cache(id, data, expire) VALUES ('" . (string) $id . "', '" . serialize($data) . "', '" . (time() + $ttl) . "')");
            
            // trigger auto-flush
            if ($this->auto_flush)
                $this->flush();
            
            return $query ? TRUE : FALSE;
        } catch ( PDOException $e ) {
            return FALSE;
        }
    }
    
    // ------------------------------------------------------------------------
    

    /**
     * Delete from Cache
     *
     * @param 	mixed		unique identifier of item in cache
     * @return 	boolean		true on success/false on failure
     */
    public function delete($id) {
        try {
            // delete data
            $query = $this->sqlite->exec("DELETE FROM cache WHERE id = '" . (string) $id . "'");
            return $query ? TRUE : FALSE;
        } catch ( PDOException $e ) {
            return FALSE;
        }
    }
    
    // ------------------------------------------------------------------------
    

    /**
     * Clean the Cache
     *
     * @return 	boolean		false on failure/true on success
     */
    public function clean() {
        try {
            // delete all data
            $query = $this->sqlite->exec("DELETE FROM cache");
            return $query ? TRUE : FALSE;
        } catch ( PDOException $e ) {
            return FALSE;
        }
    }
    
    // ------------------------------------------------------------------------
    

    /**
     * A custom method that will flush all expired cache items
     *
     * @return 	boolean		false on failure/true on success
     */
    public function flush() {
        try {
            $query = $this->sqlite->exec("DELETE FROM cache WHERE expire < '" . time() . "'");
            return $query ? TRUE : FALSE;
        } catch ( PDOException $e ) {
            return FALSE;
        }
    }
    
    // ------------------------------------------------------------------------
    

    /**
     * Cache Info
     *
     * @return 	mixed 	FALSE
     */
    public function cache_info() {
        try {
            $info = array();
            
            // get number of items in cache
            $query = $this->sqlite->query("SELECT count(1) FROM cache");
            if ($query && $result = $query->fetch())
                $info["items"] = $result[0];
            else
                $info["items"] = 0;
            
            $info["size"] = filesize($this->cache_path . $this->cache_file);
            $info["path"] = $this->cache_path;
            $info["filename"] = $this->cache_file;
            
            return $info;
        } catch ( PDOException $e ) {
            return FALSE;
        }
    }
    
    // ------------------------------------------------------------------------
    

    /**
     * Get Cache Metadata
     *
     * @param 	mixed		key to get cache metadata on
     * @return 	mixed		FALSE on failure, array on success.
     */
    public function get_metadata($id) {
        try {
            $query = $this->sqlite->query("SELECT * FROM cache WHERE id = '" . (string) $id . "'");
            
            // cache miss
            if (!$query || !$data = $query->fetch(PDO::FETCH_ASSOC))
                return FALSE;
            
            return $data;
        } catch ( PDOException $e ) {
            return FALSE;
        }
    }
    
    // ------------------------------------------------------------------------
    

    /**
     * Is supported
     *
     * Check if the SQLite PDO driver is available
     * 
     * @return boolean
     */
    public function is_supported() {
        return in_array("sqlite", PDO::getAvailableDrivers());
    }

}