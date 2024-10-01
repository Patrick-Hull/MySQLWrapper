<?php
/**
 * @author    Patrick Hull <patrick.hull1996@gmail.com>
 * @copyright 2024 Patrick Hull
 */

namespace PatrickHull\MysqlWrapper;
use mysqli;

class DeleteQuery
{
    /**
     * @return mysqli|string
     */

    public string $database;
    public string $table;
    public array $criteria;

    /**
     * @var mysqli This is the DB Connection
     */
    private mysqli $link;


    /**
     * Manages the DB Connection.
     */
    public function __construct($link)
    {
        $this->link = $link;
    }

    public function Execute(): array
    {
        $i=0;
        $bind_param = '';
        $data = [];
        $sql = "DELETE FROM " . $this->database . "." . $this->table . " ";

        if (isset($this->criteria)) {
            $sql_data = '';
            $criteria_all = array();
            $criteria_count = count($this->criteria);
            foreach ($this->criteria as $key => $val) {
                $i++;
                $bind_param .= 's';
                if ($i == $criteria_count) {
                    $sql_data .= $key . '=?';
                } else {
                    $sql_data .= $key . '=? AND ';
                }
                $criteria_all[] = $val;
            }
            $sql .= "WHERE " . $sql_data;
            if ($stmt = mysqli_prepare($this->link, $sql)) {
                mysqli_stmt_bind_param($stmt, $bind_param, ...$criteria_all);
                if (mysqli_stmt_execute($stmt)) {
                    $i=0;
                    $return['status'] = true;
                    $return['msg'] = "Data Deleted Successfully";
                    $return['data'] = $data;
                    $return['data_count'] = $i;
                } else {
                    $return['status'] = false;
                    $return['msg'] = "Error Executing Statement";
                }
            } else {
                $return['status'] = false;
                $return['msg'] = "Error Preparing Statement";
            }
        } else {
            $return['status'] = false;
            $return['msg'] = "Criteria must be presented for a Delete Statement";
        }





        return $return;
    }


}
