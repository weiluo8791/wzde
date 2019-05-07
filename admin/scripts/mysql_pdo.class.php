<?php
/*******************************************************************************
mysql PDO database class 

By 		Wei Qi Luo 
Version	1.0
Date	02/2014

mysql_pdo::__construct          - initialize database
mysql_pdo::query                - prepare statement
mysql_pdo::bind                 - bind value for statement
mysql_pdo::execute              - execute prepared statement
mysql_pdo::resultset            - return multiple row result
mysql_pdo::single               - return 1 row result
mysql_pdo::rowCount             - return result row count
mysql_pdo::lastInsertId         - return last inserted idate
mysql_pdo::beginTransaction     - begin transaction
mysql_pdo::endTransaction       - end transaction
mysql_pdo::cancelTransaction    - cancel transaction
mysql_pdo::debugDumpParams      - dumps what is in the prepared statement
*******************************************************************************/

class mysql_pdo {
    private $host      = DB_HOST;
    private $user      = DB_USER;
    private $pass      = DB_PASS;
    private $dbname    = DB_NAME;

    private $dbh;
    private $error;
    private $state;
    private $stmt;

    public function __construct(){
        $this->error = Array();

        // Set DSN
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
        // Set options
        $options = array(
        PDO::ATTR_PERSISTENT    => TRUE,
        PDO::ATTR_ERRMODE       => PDO::ERRMODE_EXCEPTION
        );
        // Create a new PDO instanace
        try{
            set_error_handler(array($this, 'errHandler'));
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
            restore_error_handler();
            $this->state = true;

        }
        // Catch any errors
        catch(PDOException $e){
            //$this->error = $e->getCode();
            $this->error[] = $e;
            $this->state = false;
        }
    }

    public function errHandler($errno, $errstr){
        
        //custom error handler
    }
    
    public function query($query){
        $this->stmt = $this->dbh->prepare($query);
    }
    
    public function bind($param, $value, $type = null){
        if (is_null($type)) {
            switch (true) {
            case is_int($value):
                $type = PDO::PARAM_INT;
                break;
            case is_bool($value):
                $type = PDO::PARAM_BOOL;
                break;
            case is_null($value):
                $type = PDO::PARAM_NULL;
                break;
            default:
                $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }
    
    public function execute(){
        return $this->stmt->execute();
    }
    
    public function resultset(){
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function single(){
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function rowCount(){
        return $this->stmt->rowCount();
    }
    
    public function lastInsertId(){
        return $this->dbh->lastInsertId();
    }
    
    public function beginTransaction(){
        return $this->dbh->beginTransaction();
    }
    
    public function endTransaction(){
        return $this->dbh->commit();
    }

    public function cancelTransaction(){
        return $this->dbh->rollBack();
    }

    public function debugDumpParams(){
        return $this->stmt->debugDumpParams();
    }

    public function getLastError(){
        return $this->stmt->errorCode();
    }
    
    public function getLastErrorExtended(){
        return $this->stmt->errorInfo();
    } 

}
