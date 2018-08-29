<?php

namespace Bundle;

define("RESULT_TYPE_NUMERIC", 1);
define("RESULT_TYPE_NAMED", 2);
define("RESULT_TYPE_BOTH", 3);

class Database {
	
		private $db;
		private $error;
		private $last_query;
		private $db_type;
		
		public function __construct($server, $login, $password, $database, $encoding = "utf8", $db_type = "mysql") {
		
			$this->error = false;
			$this->db_type = $db_type;
			
			switch($this->db_type) {
				
				case "mysql":
					if(!function_exists("mysql_query")){
						if(function_exists("mysqli_query")) require_once "modules/db_hook.php";
						else {
							echo "Fatal: MySQL and MySQLi modules are not supported in a current PHP configuration! Please install at least one of them.";
							exit();
						}
					}
					$this->db = mysql_connect($server, $login, $password) or die(mysql_error());//die("Cannot connect to mysql server: $login@$server; Using password: ".(bool)strlen($password));
					$this->SetDatabase($database);
					$check = mysql_query("SET NAMES '$encoding'", $this->db) or die("Cannot access database");
					break;
				case "mssql":
					$this->db = mssql_connect($server, $login, $password) or die("Cannot access database: ".mssql_get_last_message());
					$this->SetDatabase($database);
					$check = mssql_query("USE [$database]") or die("Cannot access database: ".mssql_get_last_message());
					break;
			}
			
		}
		
		public function SetDatabase($database) {
			
			switch($this->db_type) {
				
				case "mssql":
					return mssql_select_db($database, $this->db);
				case "mysql":
					return mysql_select_db($database, $this->db);
					
			}
			
			return false;
			
		}
		
		public function Query($query, $filter = false) {
			
			if ($filter) $query = $this->Filter($query);
			
			$result = false;
			switch($this->db_type) {
				case "mysql":
					$result = mysql_query($query, $this->db);
					break;
				case "mssql":
					$result = mssql_query($query, $this->db);
					break;
			}
			
			
			if(!$result) {
				switch($this->db_type) {
					case "mysql":
						$this->error = mysql_error();
						break;
					case "mssql":
						$this->error = mssql_get_last_message();
						break;
				}
				
				//echo $this->error;
				$this->last_query = $query;
			}
			
			return $result;
			
		}
		
		public function Filter($request) {
			
			if($this->db_type == "mysql") $request = str_replace("'", "\\'", $request);
			elseif($this->db_type == "mssql") $request = str_replace("'", "''", $request);
			
			return $request;
		
		}
		
		public function Result($query_result, $result_type = RESULT_TYPE_BOTH) {
			
			$result = array();
			
			$index = 0;
			
			switch($result_type) {
				
				case RESULT_TYPE_BOTH:
					switch($this->db_type) {
						case "mysql":
							while($row = mysql_fetch_array($query_result)) { 
								$result[$index] = $row;
								$index += 1; 
							}
							break;
						case "mssql":
							while($row = mssql_fetch_array($query_result)) { 
								$result[$index] = $row;
								$index += 1; 
							}
							break;
					}
					break;
				case RESULT_TYPE_NUMERIC:
					switch($this->db_type) {
						case "mysql":
							while($row = mysql_fetch_row($query_result)) { 
								$result[$index] = $row;
								$index += 1; 
							}
							break;
						case "mssql":
							while($row = mssql_fetch_row($query_result)) { 
								$result[$index] = $row;
								$index += 1; 
							}
							break;
					}
					break;
				case RESULT_TYPE_NAMED:
					switch($this->db_type) {
						case "mysql":
							while($row = mysql_fetch_array($query_result)) { 
								for($i=0;$i<(count($row)/2);$i++) unset($row[$i]);
								$result[$index] = $row;
								$index += 1; 
							}
							break;
						case "mssql":
							while($row = mssql_fetch_array($query_result)) { 
								for($i=0;$i<(count($row)/2);$i++) unset($row[$i]);
								$result[$index] = $row;
								$index += 1; 
							}
							break;
					}
				
			}
			
			return $result;
			
		}
		
		public function GetLastError() {
			
			$result["error"] = $this->error;
			$result["query"] = str_replace("\t", "", $this->last_query);
			$this->error = false;
			if(!$result["error"]) return false;
			return $result;
			
		}
		
		public function LastInsertId(){
			return mysql_insert_id($this->db);
		}
		
		public function Close() {
			
			switch($this->db_type) {
				case "mysql":
					@mysql_close($this->db);
					break;
				case "mssql":
					@mssql_close($this->db);
					break;
			}
			
		}
		
		public function __destruct() {
			
			$this->Close();
			
		}
	
}

?>