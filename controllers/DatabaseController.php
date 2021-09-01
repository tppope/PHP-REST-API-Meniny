<?php
require_once "/home/xpopikt/public_html/nameday/helpers/MysqlDatabase.php";


abstract class DatabaseController
{
    protected MysqlDatabase $mysqlDatabase;
    /**
     * Controller constructor.
     */
    public function __construct(){
        $this->mysqlDatabase = new MysqlDatabase();
    }
    /**
     * @return MysqlDatabase
     */
    public function getMysqlDatabase(): MysqlDatabase{
        return $this->mysqlDatabase;
    }
    /**
     * @param MysqlDatabase $mysqlDatabase
     */
    public function setMysqlDatabase(MysqlDatabase $mysqlDatabase): void{
        $this->mysqlDatabase = $mysqlDatabase;
    }
}
