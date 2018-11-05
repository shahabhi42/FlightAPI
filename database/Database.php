<?php
class Database extends PDO{

    const RETURN_OBJECT = 0;
    const RETURN_ARRAY = 1;

    const SORT_RESULTS_ASC = 0;
    const SORT_RESULTS_DESC = 1;

    private $error;
    private $sql;
    private $bind;
    private $errorCallbackFunction;
    private $errorMsgFormat;
    private $isConnected = true;

    // Database parameters
    private $database = 'flights_api';
    private $hostname = 'localhost';
    private $password = 'root';
    private $schema = 'NONE';
    private $type = 'mysql';
    private $username = 'root';

    /**
     * Function __construct
     *
     * @param unknown $schema
     * @param unknown $database
     * @param unknown $type
     * @param unknown $hostname
     * @param unknown $username
     * @param unknown $password
     */
    public function __construct(){
        $this->schema = ($this->schema == "NONE") ? "" : $this->schema;
        $charset = ($this->type != 'pgsql') ? "charset=utf8;" : "";
        $dsn = "$this->type:host=" . $this->hostname . ";dbname=" . $this->database . ";$charset";
        $options = [self::ATTR_TIMEOUT => "5000",self::ATTR_PERSISTENT=>false, self::ATTR_ERRMODE=>self::ERRMODE_EXCEPTION];

        try{
            parent::__construct($dsn, $this->username, $this->password, $options);
        }
        catch(Exception $e){
            $this->isConnected = false;
        }
    }

    function __destruct(){
        unset($this->error);
        unset($this->sql);
        unset($this->bind);
        unset($this->errorCallbackFunction);
        unset($this->errorMsgFormat);
        unset($this->schema);
    }

    public function isConnected(){
        return $this->isConnected;
    }

    /*
     * Function select
     * @param unknown $table          Table to select from.
     * @param string $where           Condition to use to limit results.
     * @param unknown $returnas       Type of return (Array | Object).
     * @param string $orderby         Field in which sort is applied.
     * @param unknown $sort           Indication of how to sort (ASC | DESC).
     * @param string $limit           Maximum number of records to return.
     * @param string $fields          Array of specific fields.
     * @param string $bind            Array of values to alias fields with.
     *
     * @return Ambigous <multitype: Array, Object:>
     *
     * @example  select("customers")
     * @example  select("customers", "customer_id = 2")
     * @example  select("customers", "company_id = 3", Database::RETURN_ARRAY, "customer_id", Database::SORT_ASC)
     */
    public function select($table, $where = "", $returnas = Database::RETURN_ARRAY, $orderby = "", $sort = Database::SORT_RESULTS_ASC, $limit = "", $fields = "*", $bind = ""){
        $sql_schema = ($this->schema != "") ? $this->schema . "." : "";
        $sql = "SELECT " . $fields . " FROM " . $sql_schema . $table;

        if(isset($where) && $where != "") $sql .= " WHERE " . $where;
        if(isset($orderby) && $orderby != ""){ $sql .= (isset($sort) && $sort == 1) ? " ORDER BY $orderby DESC" : " ORDER BY $orderby ASC"; }
        if (isset($limit) && $limit != "") $sql .= " LIMIT $limit";

        $sql .= ";";

        return $this->run($sql, $returnas, $bind);
    }

    /*
     * Function  insert
     * @param string $table          Table to select from.
     * @param array $info            Array of key=>value pairs to insert.
     *
     * @return Ambigous <boolean, multitype:> If no SQL errors are produced, this method will return the number of rows affected by the INSERT statement.
     *
     * @example  insert("customers", ["first_name"=>"George", "last_name"=>"Jetson"])
     */
    public function insert($table, $info){
        //if($this->isConnected()){
        $fields = $this->filter($this->schema, $table, $info);
        $sql_schema = ($this->schema != "") ? $this->schema . "." : "";
        $sql = "INSERT INTO " . $sql_schema . $table . " (" . implode($fields, ", ") . ") VALUES (:" . implode($fields, ", :") . ");";
        $bind = [];

        foreach($fields as $field){ $bind[":$field"] = $info[$field]; }

        return $this->run($sql, $returnas = Database::RETURN_OBJECT, $bind);
    }

    public function run($sql, $returnas=Database::RETURN_ARRAY, $bind = ""){
        $this->sql = trim($sql);
        $this->bind = $this->cleanup($bind);
        $this->error = "";

        try{
            $pdostmt = $this->prepare($this->sql);
            if($pdostmt->execute($this->bind) !== false){
                if(preg_match("/^(" . implode("|", ["select", "describe", "pragma"]) . ") /i", $this->sql)){
                    return $pdostmt->fetchAll((($returnas==Database::RETURN_OBJECT) ? self::FETCH_OBJ : self::FETCH_ASSOC));
                }
                elseif(preg_match("/^(" . implode("|", ["insert"]) . ") /i", $this->sql)){
                    $last_id = $this->lastInsertId();
                    $driver = $this->getAttribute(self::ATTR_DRIVER_NAME);

                    if($driver == 'mysql'){
                        if(is_numeric($last_id)) return $last_id;
                        elseif(!is_array($last_id) && $last_id == false) return -1;
                    }
                    else{
                        if(is_numeric($last_id)) return $last_id;
                        elseif(!isset($last_id) || $last_id==null) return 0;
                        else return $last_id;
                    }
                }
                elseif(preg_match("/^(" . implode("|", ["delete", "update"]) . ") /i", $this->sql)){
                    return $pdostmt->rowCount();
                }
            }
        }
        catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function count($table, $where){
        $sql_schema = ($this->schema!="") ? $this->schema."." : "";
        $sql = "SELECT COUNT(*) FROM " . $sql_schema.$table;
        if(isset($where) && $where != "") $sql .= " WHERE $where";
        $sql .= ";";

        return $this->query($sql)->fetchColumn();
    }

    // Database Structure Functions

    public function setErrorCallbackFunction($errorCallbackFunction, $errorMsgFormat = "html"){
        // Variable functions for won't work with language constructs such as echo and print, so these are replaced with print_r.
        if(in_array(strtolower($errorCallbackFunction), ["echo","print"])){
            $errorCallbackFunction = "print_r";
        }

        if(function_exists($errorCallbackFunction)){
            $this->errorCallbackFunction = $errorCallbackFunction;

            if(!in_array(strtolower($errorMsgFormat), ["html","text"])){
                $errorMsgFormat = "html";
            }

            $this->errorMsgFormat = $errorMsgFormat;
        }
    }

    private function filter($schema, $table, $info){
        $driver = $this->getAttribute(self::ATTR_DRIVER_NAME);

        if($driver == 'sqlite'){
            $sql = "PRAGMA table_info('" . $table . "');";
            $key = "name";
        }
        elseif($driver == 'mysql'){
            $sql = "DESCRIBE " . $table . ";";
            $key = "Field";
        }
        else{
            $sql = "SELECT * FROM information_schema.columns WHERE table_schema = '". $schema . "' AND table_name = '" . $table . "';";
            $key = "column_name";
            $list = $this->run($sql);
        }

        if(false !== ($list = $this->run($sql))){
            $fields = [];

            foreach($list as $record){
                $fields[] = $record[$key];
            }

            return array_values(array_intersect($fields, array_keys($info)));
        }

        return array();
    }

    private function cleanup($bind){
        if(!is_array($bind)){
            if(!empty($bind)) $bind = [$bind];
            else $bind = [];
        }

        return $bind;
    }
}