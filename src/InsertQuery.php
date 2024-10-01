<?php
/**
 * <p>This Class inserts data in to a Database</p>
 * <br>
 * <p>Use the <i>database</i>, <i>table</i> & <i>data</i> Parameter to set the data you wish to use</p>
 * <br>
 * <b>data</b> must be an Associative Array, with the Table Column Name as the Key, and the data as the Value
 * @author    Patrick Hull <patrick.hull1996@gmail.com>
 * @copyright 2024 Patrick Hull
 */

namespace PatrickHull\MysqlWrapper;
use mysqli;

class InsertQuery
{
    /**
     * @var string The name of the database where the table resides.
     */
    public string $database;

    /**
     * @var string The name of the table where the data will be inserted.
     */
    public string $table;

    /**
     * @var array The data to be inserted, represented as an associative array with column names as keys and values.
     */
    public array $data;

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
     * Executes the insert query and returns the result.
     *
     * @return array An associative array representing the result of the insert operation.
     *               The array contains the following keys:
     *               <ul>
     *                  <li>'status' (bool): Indicates the success status of the insert operation.</li>
     *                  <li>'msg' (string): A message describing the result of the insert operation.</li>
     *                  <li>'insert_id' (int|null): The auto-generated ID of the inserted row, if available.</li>
     *                  <li>'err_code' (int|null): The error code associated with a failed insert operation, if applicable.</li>
     *                  <li>'sql_stmt' (string): The SQL statement executed for the insert operation.</li>
     *               <ul>
     */
    public function Execute(): array
    {
        // Global variable representing the database connection
        global $link;

        // Extract the data values and column names
        $all_data = array_values($this->data);
        $all_columns = array_keys($this->data);

        $sql_data = array();
        foreach ($all_data as $row_data) {
            $sql_data[] = $row_data;
        }

        $columns = '';
        $bind_param = '';
        $i = 0;
        $data_definition = '';
        $data_length = count($this->data);

        // Construct the SQL query
        foreach ($all_columns as $column_data) {
            $i++;
            $bind_param .= "s";
            if ($data_length != $i) {
                $columns .= $column_data . ", ";
                $data_definition .= "?, ";
            } else {
                $columns .= $column_data;
                $data_definition .= "?";
            }
        }

        $sql = "INSERT INTO " . $this->database . "." . $this->table . " (" . $columns . ") VALUES (" . $data_definition . ")";

        // Execute the prepared statement
        if ($stmt = mysqli_prepare($this->link, $sql)) {
            mysqli_stmt_bind_param($stmt, $bind_param, ...$sql_data);

            if (mysqli_stmt_execute($stmt)) {
                $return['status'] = true;
                $return['msg'] = "Data added successfully!";
                $return['insert_id'] = mysqli_insert_id($this->link);
            } else {
                $return['status'] = false;
                $return['msg'] = "Error executing statement: " . mysqli_error($this->link);
                $return['err_code'] = mysqli_errno($this->link);
                $return['sql_stmt'] = $sql;
            }
        } else {
            $return['status'] = false;
            $return['msg'] = "Error preparing statement: " . mysqli_error($this->link);
            $return['err_code'] = mysqli_errno($this->link);
            $return['sql_stmt'] = $sql;
        }

        return $return;
    }
}

