<?php
/**
* Database class 
* 
* PDO wrapper class for Mysql (Percona or other)
* 
* @file			Database.php
* @author		Alex Kuzmov <alexkuzmov@gmail.com>
*   	
*/
class Database {
	
	protected $debugger = false;
	protected $pdoObject = null;
	
	// Query components
	protected $fields = [];
	protected $table = '';
	protected $joins = [];
	protected $where = [];
	protected $inputParameters = [];
	protected $group = '';
	protected $having = '';
	protected $order = '';
	protected $limit = '';
	protected $sql = '';
	
    public function __construct($config = [])
	{
		// Validate config
		if(empty($config)){
			$this->fatalError('No DB configuration provided.', __FILE__, __LINE__);
		}
		
		if(
			!isset($config['host']) || strlen($config['host']) <= 0
			|| !isset($config['database']) || strlen($config['database']) <= 0
			|| !isset($config['username']) || strlen($config['username']) <= 0
			|| !isset($config['password'])
			|| !isset($config['charset']) || strlen($config['charset']) <= 0
		){
			$this->fatalError('DB configuration incomplete, host, database, username, password and charset required.', __FILE__, __LINE__);
		}
		
		// Test connection
		try {
			
			$this->pdoObject = new PDO('mysql:host=' . $config['host'] . ';dbname=' . $config['database'], $config['username'], $config['password']);
			
		} catch (PDOException $e) {
			$this->fatalError('Connection error: ' . $e->getMessage(), __FILE__, __LINE__);
		}
		
		// Set encoding
        $this->pdoObject->query('SET NAMES "' . $config['charset'] . '"');
		
		$this->debugger = (isset($config['debugger']) && $config['debugger'] ? true : false);
    }
	
    // SELECT/INSERT/UPDATE fields
    public function fields($fields = [], $prefix = '')
	{
		if(!is_array($fields)){
			$this->fatalError('Fields must be an array.', __FILE__, __LINE__);
		}
		
		if(empty($fields)){
			$this->fatalError('There are no provided fields.', __FILE__, __LINE__);
		}
		
		// Add custom prefix to the fields
		if(strlen($prefix) > 0){
			foreach($fields AS $fieldKey => $field){
				
				// Check for preset prefix
				if(
					// Skip already prefixed fields
					substr($field, 0, strlen($prefix)) . '.' == $prefix . '.' 
					
					// Ignore prefixes for MySQL functions
					|| strpos(strtolower($field), 'sum(') !== false
					|| strpos(strtolower($field), 'if(') !== false
					|| strpos(strtolower($field), 'count(') !== false
					|| strpos(strtolower($field), 'min(') !== false
					|| strpos(strtolower($field), 'max(') !== false
					|| strpos(strtolower($field), 'unix_timestamp(') !== false
					|| strpos(strtolower($field), 'str_to_date(') !== false
					
					// Ignore prefixes for MySQL constants and static values
					|| strpos(strtolower($field), 'current_timestamp') !== false
				){
					$fields[$fieldKey] = $field;
				}else{
					$fields[$fieldKey] = $prefix . '.' . $field;
				}
			}
		}
		
		// Check if there are fields already set
		if(is_array($this->fields) && !empty($this->fields)){
			
			// Update/Replace fields or values
			foreach($fields AS $fieldKey => $field){
				$this->fields[] = $field;
			}
		}else{
			$this->fields = $fields;
		}
		
        return $this;
    }
	
    // Sets the base TABLE name
    public function table($table = '', $alias = '')
	{
		if(!is_string($table)){
			$this->fatalError('The base TABLE name must be a string.', __FILE__, __LINE__);
		}
		
		if(strlen($table) <= 0){
			$this->fatalError('The base TABLE name is not provided.', __FILE__, __LINE__);
		}
		
		$this->table = (strlen($alias) > 0 ? $table . ' AS ' . $alias : $table);
		
        return $this;
    }
	
    // LEFT JOIN
    public function leftJoin($table = '', $alias = '', $joinCondition = '')
	{
		$this->joins[] = 'LEFT JOIN ' . $table . ' AS ' . $alias . ' ON ' . $joinCondition;
		
        return $this;
    }
	
    // WHERE
    public function where($conditions = [], $glue = 'AND', $extractConditions = false)
	{
		if(!is_array($conditions)){
			$this->fatalError('WHERE conditions must be an array.', __FILE__, __LINE__);
		}
		
		if(empty($conditions)){
			$this->fatalError('There are no provided WHERE conditions.', __FILE__, __LINE__);
		}
		
		// The input array contains the parsed conditions
		$input = [];
		// The where array contains the input parameters for the PDO prepared statemets and the where clause
		$where = [];
		
		// Form search array
		foreach ($conditions AS $fieldName => $fieldValue) {

			// Void
			// Used to form complex WHERE clause with nested conditions
			if ($fieldName == 'void' || substr($fieldName, 0, 4) == 'void') {
				$input[] = '(' . $fieldValue . ')';
				continue;
				
			// Match against
			} else if (strpos($fieldValue, 'MATCH') !== false && strpos($fieldValue, 'AGAINST') !== false) {
				$input[] = $fieldValue;
				continue;

			// Dates
			} else if (strtolower(substr($fieldName, 0, 4)) == 'date') {
				$input[] = $fieldName . ' = "' . $fieldValue . '"';
				continue;

			// Like
			} else if (strpos($fieldValue, '%') !== false) {
				$input[] = $fieldName . ' LIKE :where_' . str_replace('.', '_', $fieldName) . '';

			// Different
			} else if (strpos($fieldValue, '!=') !== false) {
				$input[] = $fieldName . ' != :where_' . str_replace('.', '_', $fieldName);

			// Lower / equal
			} else if (strpos($fieldValue, '<=') !== false) {
				$input[] = $fieldName . ' <= :where_' . str_replace('.', '_', $fieldName);

			// Greater / equal
			} else if (strpos($fieldValue, '>=') !== false) {
				$input[] = $fieldName . ' >= :where_' . str_replace('.', '_', $fieldName);

			// Lower
			} else if (strpos($fieldValue, '<') !== false) {
				$input[] = $fieldName . ' < :where_' . str_replace('.', '_', $fieldName);

			// Greater
			} else if (strpos($fieldValue, '>') !== false) {
				$input[] = $fieldName . ' > :where_' . str_replace('.', '_', $fieldName);

			// Between
			} else if (strpos($fieldValue, 'BETWEEN') !== false) {
				$input[] = $fieldName . ' ' . $fieldValue;
				continue;

			// In
			} else if (strpos($fieldValue, ' IN ') !== false) {
				$input[] = $fieldName . ' ' . $fieldValue;
				continue;

			// NULL
			} else if (strpos($fieldValue, 'NULL') !== false) {
				$input[] = $fieldName . ' ' . $fieldValue;
				
				// Skip binding if we are dealing with NULL values
				continue;

			// Equal
			} else {
				$input[] = $fieldName . ' = :where_' . str_replace('.', '_', $fieldName);
			}
			
			// Extract values
			$where['inputParameters'][':where_'.str_replace('.', '_', $fieldName)] = trim(str_replace(array('!=', '<=', '>=', '<', '>'), '', $fieldValue));
		}
		
		$conditionsString = implode(' ' . $glue . ' ', $input);
		
		// If we need to return generated where clause
		if($extractConditions){
			return $this->interpolateQuery($conditionsString, (isset($where['inputParameters']) ? $where['inputParameters'] : []));
		}
		
		// Form where clause
		$where['clause'] = ' WHERE ' . $conditionsString;

		// Merge to replace PDO parameters
		if(!empty($where['inputParameters'])){
			$this->inputParameters = array_merge($this->inputParameters, $where['inputParameters']);
		}

		// Record search string
		$this->where = $where;
		
        return $this;
    }
	
    // GROUP
    public function group($fields = [])
	{
		if(!is_array($fields)){
			$this->fatalError('GROUP fields must be an array.', __FILE__, __LINE__);
		}
		
		if(empty($fields)){
			$this->fatalError('There are no provided GROUP fields.', __FILE__, __LINE__);
		}
		
        // Add GROUP BY statement
		$this->group = ' GROUP BY ' . implode(', ', $fields);
		
        return $this;
    }
	
    // HAVING
    public function having($condition = '')
	{
		if(!is_string($condition)){
			$this->fatalError('The condition for having must be a string.', __FILE__, __LINE__);
		}
		
		if(strlen($condition) <= 0){
			$this->fatalError('The condition for having is not provided.', __FILE__, __LINE__);
		}
		
		$this->having = ' HAVING ' . $condition;
		
        return $this;
    }
	
    // ORDER
    public function order($by = '', $type = 'ASC')
	{
		if(!is_string($by)){
			$this->fatalError('The field name to ORDER by must be a string.', __FILE__, __LINE__);
		}
		
		if(strlen($by) <= 0){
			$this->fatalError('The field name to ORDER by is not provided.', __FILE__, __LINE__);
		}
		
		// Check for previous ORDER conditions
		if(strlen($this->order) > 0){
			$this->order .= ', ' . $by . ' ' . $type;
		}else{
			$this->order = ' ORDER BY ' . $by . ' ' . $type;
		}
		
        return $this;
    }
	
    // LIMIT
    public function limit($limit = 0, $offset = 0)
	{
		$this->limit = ' LIMIT ' . ($offset ? intval($offset) . ', ' : '') . intval($limit);
		
        return $this;
    }
	
    // Get the last insert id
    public function lastInsertId()
	{
        // Return the last insert id
        return $this->pdoObject->lastInsertId();
    }
	
    // INSERT data
    public function insert()
	{
        // Build the query
        $this->build("insert");

        // Execute the query
        $result = $this->execute();

        // If the query is not executed properly
        if (!$result) {
            return false;
        } else {
            return $this->lastInsertId();
        }
    }
	
    // UPDATE data
    public function update()
	{
        // Build the query
        $this->build("update");

        // Execute the query
        $result = $this->execute();

        // Check if the query is executed properly
        if (!$result) {
            return false;
        } else {
            return true;
        }
    }
	
    // DELETE data
    public function delete()
	{
        // Build the query
        $this->build("delete");

        // Execute the query
        $result = $this->execute();

        // Check if the query is executed properly
        if (!$result) {
            return false;
        } else {
            return true;
        }
    }
	
    // CUSTOM query execution
    public function query($explicitQuery) {

        // Set the query
        $this->sql = $explicitQuery;

        // Execute the query
        $this->execute();

        // Fetch data
        $result = $this->query_object->fetchAll(PDO::FETCH_ASSOC);

        // Check if the query is executed properly
        if (!$result) {
            return false;
        } else {
            return true;
        }
    }
	
    // Get all rows
    public function fetchAll($explicitQuery = "") {

        // If we need to use custom query
        if(!empty($explicitQuery)){
            $this->sql = $explicitQuery;

        // If we need to use the default query builder
        } else {
        
            // Build the query
            $this->build("select");
        }

        // Execute the query
        $this->execute();

        // Fetch data
        $result = $this->query_object->fetchAll(PDO::FETCH_ASSOC);

        // Return fetched data
        return $result;
    }
	
    // Get row
    public function fetchRow($explicitQuery = "") {

        // If we need to use custom query
        if(!empty($explicitQuery)){
            $this->sql = $explicitQuery;

        // If we need to use the default query builder
        } else {

            // Build the query
            $this->build("select");
        }

        // Execute the query
        $this->execute();

        // Fetch data
        $result = $this->query_object->fetch(PDO::FETCH_ASSOC);

        // Return fetched data
        return $result;
    }

    // Get one field
    public function fetchOne($explicitQuery = "") {

        // If we need to use custom query
        if(!empty($explicitQuery)){
            $this->sql = $explicitQuery;

        // If we need to use the default query builder
        } else {

            // Build the query
            $this->build("select");
        }

        // Execute the query
        $this->execute();

        // Fetch data
        $result = $this->query_object->fetch(PDO::FETCH_NUM);

        // Return fetched data
        return $result[0];
    }
	
	protected function fatalError($error, $file, $line)
	{
		echo 'Error in ' . $file . ' on line ' . $line . "\n";
		echo $error . "\n";
		
		exit();
	}
	
    // Prepare query for debugger
    protected function interpolateQuery($conditionsString, $inputParameters = [])
	{
        // Array for field names
        $fieldNames = [];

        // Build a regular expression for each parameter
        foreach($inputParameters AS $fieldName => &$fieldValue) {

            $fieldNames[] = '/' . $fieldName . '/';
			
            if(
				!is_int($fieldValue)
				&& !is_float($fieldValue)
				&& !ctype_digit($fieldValue)
				&& strpos($fieldValue, 'NULL') === false
			){
				$inputParameters[$fieldName] = '"' . $fieldValue . '"';
            }
        }

        // Replace PDO statements with real values
        $conditionsString = preg_replace($fieldNames, $inputParameters, $conditionsString, 1);

        // Return data
        return $conditionsString;
    }
	
    // Build query
    protected function build($type)
	{
        // Check what type is the query
        switch($type){

            // Insert
            case 'insert':

                // Form the params array
                foreach ($this->fields AS $k => $v) {
                    $keys[] = '`' . $k . '`';
                    $values_prepared[] = ':fields_' . $k;
                    $values[':fields_' . $k] = $v;
                }

                // Prepare SQL
                $this->sql = 'INSERT INTO ' . $this->table . ' (' . implode(', ', $keys) . ') VALUES (' . implode(', ', $values_prepared) . ')';

                // Add params for PDO usage
                $this->inputParameters = array_merge($this->inputParameters, $values);
            break;

            // Update
            case 'update':

                // Form the params array
                foreach ($this->fields AS $k => $v) {
                    $keys[] = '`' . $k . '`';
                    $values_prepared[] = '`' . $k . '` = :fields_' . $k;
                    $values[':fields_' . $k] = $v;
                }

                // Prepare SQL
                $this->sql = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $values_prepared) . ' ' . $this->where['clause'] . ' ' . $this->limit;

                // Add params for PDO usage
                $this->inputParameters = array_merge($this->inputParameters, $values);
            break;

            // Delete
            case 'delete':

                // Prepare SQL
                $this->sql = 'DELETE FROM ' . $this->table . ' ' . $this->where['clause'] . ' ' . $this->limit;
            break;

            // Select statements
            case 'select':

                // Joins
                if (!empty($this->joins)) {
                    $joins = implode(' ', $this->joins);
                } else {
                    $joins = '';
                }

                // If executing manual statements it is possible to have missing sql key
                if(!isset($this->where['clause']) || empty($this->where['clause'])){
                    $this->where['clause'] = '';
                }
				
                $this->sql = 'SELECT ' . implode(',', $this->fields) . ' FROM ' . $this->table . ' ' . $joins . ' ' . $this->where['clause'] . ' ' . $this->group . ' ' . $this->having . ' ' . $this->order . ' ' . $this->limit;
            break;
        }
    }
	
    // Execute query
    public function execute()
	{
        // Get start time
        $start = microtime(true);

        // Execure the query
        $this->query_object = $this->pdoObject->prepare($this->sql);

        // Try to execute
        try {

            // Get result if possible
            $result = $this->query_object->execute($this->inputParameters);
            
            // Log bad query if result was false
            if($result == false){

                // Check for empty request URI
                if(!isset($_SERVER['REQUEST_URI']) || empty($_SERVER['REQUEST_URI'])){
                    $_SERVER['REQUEST_URI'] = '';
                }

                // Log the error
                $error = array(
                    'url' => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
                    'get_params' => addslashes(print_r($_GET, true)),
                    'post_params' => addslashes(print_r($_POST, true)),
                    'cookies_params' => addslashes(print_r($_COOKIE, true)),
                    'type' => 'SQL',
                    'error' => $this->interpolateQuery($this->sql, $this->inputParameters),
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'date_added' => date('Y-m-d H:i:s'),
                );
				
                foreach($error AS $errorKey => $errorVal){
                    $error_keys[] = $errorKey;
                    $error_keys_prepared[] = ':error_'.$errorKey;
                    $error_values[':error_'.$errorKey] = $errorVal;
                }
				
                $this->query_object = $this->pdoObject->prepare('INSERT INTO errors ('.implode(', ', $error_keys).') VALUES ('.implode(', ', $error_keys_prepared).')');
                $this->query_object->execute($error_values);
            }

        // In case of error
        } catch (PDOException $e) {
            
            // Show the error to devs only
            if($this->debugger) {
                echo $e->getMessage();
            }
			
            exit();
        }

        // Get end time
        $end = microtime(true);

        // If the debugger is on - add the time and the query
        if ($this->debugger) {
            $this->debuggerData[] = array('time' => round($end - $start, 4), 'query' => $this->interpolateQuery($this->sql, $this->inputParameters));
        }

        // Unset params
        $this->fields = $this->table = $this->order = $this->group = $this->limit = $this->sql = '';
        $this->inputParameters = $this->where = $this->joins = [];

        // Return the result
        return $result;
    }
	
    // Show debugger
    public function showDebugger()
	{
        // Check if the debugger is on
        if ($this->debugger) {

            // If we are using CLI
            if(php_sapi_name() === 'cli'){

                // Show headings
                echo "\nMySQL Debugger\n\n";
                echo "#   |  Time  | Query\n";
                echo "\n";
				
                $time = 0;
                if (!empty($this->debuggerData)) {
                    foreach ($this->debuggerData as $key => $value) {
                        $time += $value['time'];
                        echo str_pad($key, 3) ." | ".round($value['time'], 4) . " | ".$value['query']."\n";
                    }
                }
                echo "\nAll queries time: ".$time."s\n";

            // Web debugger
            } else {

                // Form the main table
                $return = '
                    <table border="1" cellpadding="2" cellspacing="2" width="100%" style="font-size: 12px; background-color: white; font-family: Arial, Helvetica, sans-serif;">
                        <tr>
                            <td align="center" colspan="3"><b>MySQL Debugger Results</b></td>
                        </tr>
                        <tr>
                            <td align="center" width="5%"><i>#</i></td>
                            <td align="left" width="75%"><i>MySQL Query</i></td>
                            <td align="center"><i>Time to get results</i></td>
                        </tr>
                ';

                // Store all queries time
                $time = 0;

                // Add queries data
                if (!empty($this->debuggerData)) {
                    foreach ($this->debuggerData as $key => $value) {
                        $return .= '
							<tr>
								<td align="center"><b>' . $key . '</b></td>
								<td align="left"><b>' . $value['query'] . '</b></td>
								<td align="center"><b>' . $value['time'] . '</b></td>
							</tr>
						';
						
                        $time += $value['time'];
                    }
                }

                $return .= '
					<tr>
						<td align="center" style="color: red"> - - </td>
						<td align="left" style="color: red"><b>All Queries Time</b></td>
						<td align="center" style="color: red"><b>' . $time . '</b></td>
					</tr>
				';

                // Show the SQL debugger
                echo $return . "</table><br /><br />";
            }
        }
    }
}