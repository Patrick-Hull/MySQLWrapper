<?php
/**
 * Class SelectQuery
 *
 * Represents a select query operation to retrieve data from a specified database table.
 *
 * The following parameters are available:
 * - 'database' (string): MANDATORY. The name of the database where the table resides.
 * - 'table' (string): MANDATORY. The name of the table from which the data will be retrieved.
 * - 'limit' (int): The maximum number of rows to be retrieved from the table.
 * - 'criteria' (array): The criteria used for filtering the query results. Built as an associative array where the Key is the column name, and the Value is the data
 * - 'order_by' (array): The columns and direction used for ordering the query results. Built as an associative array where the Key is the column name and the Value is the direction
 * - 'cache' (bool): Indicates whether caching is enabled for the select query.
 * - 'cache_duration' (int): The duration, in seconds, for which the query results will be cached.
 *
 * When ready to execute the query, use the Execute() function.
 *
 * @author    Patrick Hull <patrick.hull1996@gmail.com>
 * @copyright 2024 Patrick Hull
 */

namespace PatrickHull\MysqlWrapper;
use mysqli;
use Redis;


class SelectQuery
{
    /**
     * @var array|null The columns and direction used for ordering the query results.
     */
    public ?array $order_by;

    /**
     * @var string The name of the database where the table resides.
     */
    public string $database;

    /**
     * @var string The name of the table from which the data will be retrieved.
     */
    public string $table;

    /**
     * @var bool Specify whether the criteria is a fuzzy match
     */
    public bool $fuzzy_match = false;

    /**
     * @var bool Specify whether the criteria is a fuzzy match (wildcard before content)
     */
    public bool $fuzzy_match_start = false;

    /**
     * @var bool Specify whether the criteria is a fuzzy match (wildcard after content)
     */
    public bool $fuzzy_match_end = false;

    /**
     * @var int The maximum number of rows to be retrieved from the table.
     */
    public int $limit;

    /**
     * @var array|null The criteria used for filtering the query results.
     */
    public ?array $criteria;

    /**
     * @var array|null Specify certain columns to be returned by the query
     */
    public ?array $columns;

    /**
     * @var bool Indicates whether caching is enabled for the select query.
     */
    public bool $redis = false;

    /**
     * @var bool Indicates whether caching is enabled for the select query.
     */
    public bool $clear_cache = false;

    /**
     * @var bool Indicates whether caching is enabled for the select query.
     */
    public string $redis_pw;

    /**
     * @var bool Indicates whether the data will be used in a Data Table
     */
    public bool $datatable = false;

    /**
     * @var int The duration, in seconds, for which the query results will be cached.
     */
    public int $cache_duration = 600;

    /**
     * @var bool Choose whether the search criteria will be an AND (True) or an OR (false) search
     */
    public bool $strict_search = true;

    /**
     * @var mysqli This is the DB Connection
     */
    private mysqli $link;


    /**
     * Manages the DB Connection. No parameters required as the $db_connection variable is set at a global level
     */
    public function __construct($link)
    {
        $this->link = $link;
    }

    /**
     * Executes the select query and returns the result.
     *
     * On a successful execution, this function returns an array with the following keys:
     * - 'status' (bool): Indicates the success status of the query execution.
     * - 'msg' (string): A message confirming the successful execution.
     * - 'data' (array): Contains the retrieved data as an associative array.
     * - 'data_count' (int): The total number of rows selected.
     *
     * If caching is enabled, the returned array may also contain the following keys:
     * - 'precached_data' (bool): Indicates whether the data was retrieved from cache.
     * - 'cached_time' (int): The timestamp when the data was cached.
     * - 'cache_duration' (int): The duration for which the data is cached.
     * - 'cache_created' (bool): Indicates whether the cache file was created successfully.
     *
     * @return array An associative array representing the result of the select query.
     */
    public function Execute(): array
    {
        global $cache_dir;
        $i=0;
        $cached_data = false;
        $return = array();
        $bind_param = '';
        $sql = "SHOW KEYS FROM " . $this->database . "." . $this->table . " WHERE Key_name = 'PRIMARY'";
        if($result = mysqli_query($this->link, $sql)) {
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $data = [];
                $primary_key = $row['Column_name'];


                if(!isset($this->columns)){
                    $columns = "*";
                } else {
                    $columns = "";
                    $column_count = count($this->columns);
                    $n = 0;
                    foreach($this->columns as $column){
                        $n++;
                        if ($n == $column_count) {
                            $columns .= $column;
                        } else {
                            $columns .= $column . ', ';
                        }
                    }

                }


                $sql = "SELECT " . $columns . " FROM " . $this->database . "." . $this->table . " ";

                $cache_criteria = "";
                if (isset($this->criteria)) {
                    $sql_data = '';
                    $criteria_all = array();
                    $criteria_count = count($this->criteria);
                    foreach ($this->criteria as $key => $val) {
                        if($this->strict_search){
                            $strict_search_type = "AND";
                        } else {
                            $strict_search_type = "OR";
                        }
                        $i++;
                        $bind_param .= 's';
                        if($this->fuzzy_match){
                            if($this->fuzzy_match_start){
                                $val = "%" . $val;
                            }
                            if($this->fuzzy_match_end){
                                $val = $val . "%";
                            }

                            if ($i == $criteria_count) {
                                $sql_data .= $key . ' LIKE ?';
                            } else {
                                $sql_data .= $key . ' LIKE ? ' . $strict_search_type . ' ';
                            }

                        } else {
                            if ($i == $criteria_count) {
                                $sql_data .= $key . '=?';
                            } else {
                                $sql_data .= $key . '=? ' . $strict_search_type . ' ';
                            }
                        }

                        $criteria_all[] = $val;
                    }
                    $sql .= "WHERE " . $sql_data;
                    $cache_criteria = print_r($this->criteria,true);
                }
                if (isset($this->order_by)){
                    $sql .= " ORDER BY " . $this->order_by['col'] . " " . $this->order_by['direction'];
                }
                if (isset($this->limit)){
                    $sql .= " LIMIT " . $this->limit;
                }


                $key = md5($sql . "|" . $cache_criteria);


                if($this->redis){
                    $redis = new Redis();
                    try {
                        $redis->connect('localhost', 6379);
                    } catch (\RedisException $e) {
                        $return['status'] = false;
                        $return['msg'] = "Unable to connect to Redis Server";
                    }

                    try {
                        $redis->auth($this->redis_pw);
                    } catch (\RedisException $e) {
                        $return['status'] = false;
                        $return['msg'] = "Redis Password incorrect";
                    }



                    if($this->clear_cache){
                        try {
                            $redis->del($key);
                        } catch (\RedisException $e) {
                            $return['status'] = false;
                            $return['msg'] = "Unable to delete Key. " . $e->getMessage();
                        }
                    }

                    try {
                        if ($redis->exists($key)) {
                            $return = unserialize($redis->get($key));
                            $return['cached_data'] = true;
                            $cached_data = true;
                        }
                    } catch (\RedisException $e) {
                        $return['status'] = false;
                        $return['msg'] = "Unable to search for Redis Key. " . $e->getMessage();
                    }
                }


                if(!$cached_data){
                    if ($stmt = mysqli_prepare($this->link, $sql)) {
                        if (isset($this->criteria)) {
                            mysqli_stmt_bind_param($stmt, $bind_param, ...$criteria_all);
                        }
                        if (mysqli_stmt_execute($stmt)) {
                            $result = mysqli_stmt_get_result($stmt);
                            if (mysqli_num_rows($result) != 0) {
                                $i=0;
                                while ($response = mysqli_fetch_assoc($result)) {
                                    if(!$this->datatable){
                                        $row_id = $response[$primary_key];
                                        $data[$row_id] = [];
                                        foreach ($response as $col => $row) {
                                            $data[$row_id][$col] = $row;
                                        }
                                    } else {
                                        $row_id = $i;
                                        $data[$row_id] = [];
                                        foreach ($response as $col => $row) {
                                            $data[$i][$col] = $row;
                                        }
                                    }
                                    $i++;

                                }
                                $return['status'] = true;
                                $return['msg'] = "Data Retrieved Successfully";
                                $return['data'] = $data;
                                $return['data_count'] = $i;

                                if($this->redis){

                                    try {
                                        $redis->set($key, serialize($return));
                                    } catch (\RedisException $e) {
                                        $return['status'] = false;
                                        $return['msg'] = "Error setting Cached Data. " . $e->getMessage();
                                    }


                                    if($return['status']){
                                        $return['cache_created'] = true;
                                    }

                                }

                                /*
                                if($this->cache){
                                    $serialized_data = serialize($return);
                                    $ttl = $this->cache_duration;
                                    $cache_filename = $ttl . '_' . time() . '_' . $hash;
                                    if(file_put_contents($cache_dir . "/" . $cache_filename, $serialized_data)){
                                        $return['cache_created'] = true;
                                    } else {
                                        $return['cache_created'] = false;
                                    }
                                }
                                */
                            } else {
                                $return['status'] = true;
                                $return['msg'] = "No Data Exists for Table";
                                $return['data_count'] = 0;
                            }
                        } else {
                            $return['status'] = false;
                            $return['msg'] = "Error Executing Statement";
                        }
                    } else {
                        $return['status'] = false;
                        $return['msg'] = "Error Preparing Statement";
                        $return['sql'] = $sql;
                    }
                }


            } else {
                $return['status'] = false;
                $return['msg'] = "Table does not exist";
            }
        } else {
            $return['status'] = false;
            $return['msg'] = "Error finding Primary Key of " . $this->database . "." . $this->table . ". " . mysqli_error($this->link);
        }
        return $return;
    }


}
