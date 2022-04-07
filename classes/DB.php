<?php
class DB {
    public $link = null;
    public $last_query = null;

    public function __construct($host, $user, $pwd, $name) {
        $this->link = mysqli_connect($host, $user, $pwd, $name) or die("Cannot connect to DB.");

        mysqli_query($this->link, "SET NAMES utf8");
    }

    public function query($q = null) {
        $this->last_query = mysqli_query($this->link, $q);

        return $this->last_query;
    }

    /*
     Создание записи в таблице. Пример использования:

     $db->insert('users', array('id' => NULL, 'login' => 'test'));
    */
    public function insert($tableName = null, $params = null) {
        foreach($params as $key => $value) {
            $keys[] = '`'.$key.'`';
            $values[] = "'".$this->escape($value)."'";
        }

        return $this->query("INSERT INTO `".$tableName."` (".implode(', ', $keys).") VALUES (".implode(', ', $values).");");
    }

    /*
     Изменение записи в таблице. Примеры использования:

     $db->update('users', array('login' => 'new_login'), array('user_id' => 1));
     $db->update('users', array('reputation:+' => 1), array('user_id' => 1)); // прибавит +1 к reputation
    */
    public function update($tableName = null, $params = null, $where = null) {
        foreach($params as $key => $value) {
            if(preg_match('/\:/is', $key)) {
                preg_match('/[\+\-\*]/is', $key, $sign);

                $result[] = "`".str_replace(':'.$sign[0], '', $key)."` = `".str_replace(':'.$sign[0], '', $key)."` ".$sign[0]." ".$value."";
            } else {
                $result[] = "`".$key."` = '".$this->escape($value)."'";
            }
        }

        foreach($where as $key => $value) {
            $result_where[] = "`".$tableName."`.`".$key."` = '".$this->escape($value)."'";
        }

        $where = ((isset($where)) ? " WHERE ".implode(' AND ', $result_where)."" : '');

        return $this->query("UPDATE `".$tableName."` SET ".implode(', ', $result)."".$where.";");
    }

    public function index($q = null, $index = null) {
        $result = array();

        while($assoc = $this->assoc($q)) {
            $result[] = $assoc[$index];
        }

        return $result;
    }

    public function fetch($q = null) {
        return mysqli_fetch_array($q);
    }

    public function num_rows($q = null) {
        return mysqli_num_rows($q);
    }

    public function assoc($q = null) {
        return mysqli_fetch_assoc($q);
    }

    public function escape($q = null) {
        return mysqli_real_escape_string($this->link, $q);
    }

    public function insert_id() {
        return mysqli_insert_id($this->link);
    }

    public function filter($text = null) {
        return htmlspecialchars(stripslashes($text));
    }

    public function error() {
        return mysqli_error($this->link);
    }

    public function __destruct() {
        return mysqli_close($this->link);
    }
}