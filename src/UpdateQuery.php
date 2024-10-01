<?php
/**
 * @author    Patrick Hull <patrick.hull1996@gmail.com>
 * @copyright 2024 Patrick Hull
 */

namespace PatrickHull\MysqlWrapper;
use mysqli;

class UpdateQuery
{
    /**
     * @return mysqli|string
     */

    public string $database;
    public string $table;
    public array $data;
    public array $criteria;

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

    public function Execute(): array
    {
        global $link;


        $all_data = array_values($this->data);
        $all_columns = array_keys($this->data);

        $sql_data = array();
        $output_data = '';
        foreach ($all_data as $row_data) {
            $sql_data[] = $row_data;
        }
        $columns = '';
        $bind_param = '';
        $i=0;
        $data_length = count($this->data);
        foreach ($all_columns as $column_data) {
            $i++;
            $bind_param .= "s";
            if ($data_length != $i) {
                $columns .= $column_data . "=?, ";
            } else {
                $columns .= $column_data . "=?";
            }
        }


        $i=0;
        $criteria_count = count($this->criteria);
        $criteria_data = '';

        foreach ($this->criteria as $key => $val) {
            $i++;
            $bind_param .= 's';
            if ($i == $criteria_count) {
                $criteria_data .= $key . '=?';
            } else {
                $criteria_data .= $key . '=? AND ';
            }
            $sql_data[] = $val;
        }


        $sql = "UPDATE " . $this->database . "." . $this->table . " SET " . $columns . " WHERE " . $criteria_data;
        if ($stmt = mysqli_prepare($this->link, $sql)) {
            mysqli_stmt_bind_param($stmt, $bind_param, ...$sql_data);
            if (mysqli_stmt_execute($stmt)) {
                $return['status'] = true;
                $return['msg'] = "Data Updated Successfully";
            } else {
                $return['status'] = false;
                $return['msg'] = "Error Executing Statement ." . mysqli_error($this->link);
                $return['err_code'] = mysqli_errno($this->link);
                $return['sql_stmt'] = $sql;
            }
        } else {
            $return['status'] = false;
            $return['msg'] = "Error Preparing Statement." . mysqli_error($this->link);
            $return['err_code'] = mysqli_errno($this->link);
            $return['sql_stmt'] = $sql;
        }





        return $return;
    }


}
