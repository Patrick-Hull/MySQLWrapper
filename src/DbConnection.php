<?php
/**
 * Class DbConnection
 *
 * Represents a connection to a MySQL database.
 * @author    Patrick Hull <patrick.hull1996@gmail.com>
 * @copyright 2024 Patrick Hull
 */

namespace PatrickHull\MysqlWrapper;
use Exception;
use mysqli;




class DbConnection
{
    /**
     * @var string $server The server name or IP address
     */
    private string $server;
    /**
     * @var string $username The username used to authenticate with the server
     */
    private string $username;
    /**
     * @var string $password The password for authentication
     */
    private string $password;

    /**
     * Constructs a new instance of the class.
     *
     * @param string $server The server address.
     * @param string $username The username for authentication.
     * @param string $password The password for authentication.
     *
     * @return void
     */
    public function __construct(string $server, string $username, string $password)
    {
        $this->server = $server;
        $this->username = $username;
        $this->password = $password;
    }


    /**
     * Connects to the database server.
     *
     * @return mysqli The database connection object.
     * @throws Exception If the connection fails.
     */
    public function connect(): mysqli
    {
        $link = mysqli_connect($this->server, $this->username, $this->password);

        if(mysqli_connect_error()) {
            throw new Exception("ERROR: Could not connect. " . mysqli_connect_error());
        }

        return $link;
    }



}
