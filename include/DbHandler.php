<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /* ------------- `users` table method ------------------ */

    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createUser($fname,$lname, $email, $password,$phone,$gcm_regid,$imei)
            {
        require_once 'PassHash.php';
        $response = array();

        // First check if user already existed in db
        if (!$this->isUserExists($phone)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);

            // Generating API key
            $api_key = $this->generateApiKey();
            // insert query
            $stmt = $this->conn->prepare("INSERT INTO users"
                    . "(`fname`, `lname`, `phone`, `email`, `password_hash`, `api_key`, `gcm_regid`,`imei`, `status`,`membership`)"
                    . " values('$fname','$lname',$phone, '$email', '$password_hash','$api_key','$gcm_regid','$imei', 1,0)");
//            $stmt->bind_param("ssssssss", $fname,$lname,$phone, $email, $password_hash,$api_key,$gcm_regid,$imei);

//            echo "INSERT INTO users"
//                    . "(`fname`, `lname`, `phone`, `email`, `password_hash`, `api_key`, `gcm_regid`,`imei`, `status`,`membership`)"
//                    . " values('$fname','$lname',$phone, '$email', '$password_hash','$api_key','$gcm_regid','$imei', 1,0)";
//            
            
             $result = $stmt->execute();

            $stmt->close();
            
            

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }

        return $response;
    }

    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($phone, $password) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE phone = ?");

        $stmt->bind_param("s", $phone);

        $stmt->execute();

        $stmt->bind_result($password_hash);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            // user not existed with the email
            return FALSE;
        }
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($phone) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE phone = $phone");
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Fetching user by email
     * @param String $email User email id
     */
    public function getUserByPhone($phone) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->bind_param("s", $phone);
        
        $stmt->execute();
            		
        $result = $stmt->get_result();
            		
        $stmt->close();
            	
        while ($row_users = $result->fetch_assoc())
        {
        foreach( $row_users as $key=>$value )
        	{
                    	$users_array[$key] = $value;
            	}
            	
        }
        
        return $users_array;
            	
        
        
        
//        if ($stmt->execute()) {
//            // $user = $stmt->get_result()->fetch_assoc();
//            $stmt->bind_result($name, $email, $api_key, $status, $created_at);
//            $stmt->fetch();
//            
//            
//            
//            
//            $user = array();
//            $user["name"] = $name;
//            $user["email"] = $email;
//            $user["api_key"] = $api_key;
//            $user["status"] = $status;
//            $user["created_at"] = $created_at;
//            $stmt->close();
//            return $user;
//        } else {
//            return NULL;
//        }
    }

    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // $api_key = $stmt->get_result()->fetch_assoc();
            // TODO
            $stmt->bind_result($api_key);
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($user_id);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user_id;
        } else {
            return NULL;
        }
    }

    /**
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isValidApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }

    /* ------------- `tasks` table method ------------------ */

    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createTask($user_id, $task) {
        $stmt = $this->conn->prepare("INSERT INTO tasks(task) VALUES(?)");
        $stmt->bind_param("s", $task);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_task_id = $this->conn->insert_id;
            $res = $this->createUserTask($user_id, $new_task_id);
            if ($res) {
                // task created successfully
                return $new_task_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }
    }

    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getTask($task_id, $user_id) {
        $stmt = $this->conn->prepare("SELECT t.id, t.task, t.status, t.created_at from tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($id, $task, $status, $created_at);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"] = $id;
            $res["task"] = $task;
            $res["status"] = $status;
            $res["created_at"] = $created_at;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }
    
    public function   getQuotes($category_id,$author_id,$quote_id) {
        
    	
    	$authorList_array =array();
    	$author_array = array();
    	$author_quotes_array = array();
    	
        if($category_id == 'all' && $author_id == 'all' && $quote_id == 'null')
            {

            	// All authors All Categories
            	
            $stmtAuthor = $this->conn->prepare("SELECT * from authors 
            		ORDER BY author_name  ASC");
            
            $stmtAuthor->execute();

            $resultAuthor = $stmtAuthor->get_result();
            $stmtAuthor->close();
                  
                  
                 while ($row_authors = $resultAuthor->fetch_assoc()) 
        		{
        			foreach( $row_authors as $key=>$value )
        			{
        				$author_array[$key] = $value;
        			}
        	
        			$author_array['quotes'] = array();
        			$id_author = $row_authors['id_author'];  
        			
        			
                	$stmtQuote = $this->conn->prepare(
                			"SELECT q.* ,c.category_name 
                			FROM quotes q ,categories c 
                			WHERE q.id_category = c.id_category 
                			AND id_author = ".$id_author."
                			ORDER BY q.quote_likes_count  DESC");   
                	
                    $stmtQuote->execute();
                  	$resultQuote = $stmtQuote->get_result();
                  	$stmtQuote->close();
                  
                  	
    				while ($row_quotes = $resultQuote->fetch_assoc())
    				{
    					foreach( $row_quotes as $key=>$value )
    					{
    						$quotes_array[$key] = $value;
    					}
    						
        				array_push($author_array['quotes'],$quotes_array);
    				}

    			array_push($authorList_array,$author_array);
			}


            
            
            return $authorList_array;
            



    }
    else if($category_id == 'all' && $author_id != 'all' && $quote_id == 'null')
            {
            	// Single author All Categories
            	
            	$stmtauthor = $this->conn->prepare("SELECT * from authors 
            			WHERE id_author = ".$author_id."");
            	$stmtauthor->execute();
            	
            	$resultauthor = $stmtauthor->get_result();
            	$stmtauthor->close();
            	
            	
            	while ($row_authors = $resultauthor->fetch_assoc())
            	{
            		foreach( $row_authors as $key=>$value )
            		{
            			$author_array[$key] = $value;
            		}
            		 
            		$author_array['quotes'] = array();
            		$id_author = $row_authors['id_author'];
            		 
            		 
            		$stmtQuote = $this->conn->prepare(
            				"SELECT q.* ,c.category_name
                			FROM quotes q ,categories c
                			WHERE q.id_category = c.id_category
                			AND id_author = ".$id_author." ");
            		 
            		$stmtQuote->execute();
            		$resultQuote = $stmtQuote->get_result();
            		$stmtQuote->close();
            	
            		 
            		while ($row_quotes = $resultQuote->fetch_assoc())
            		{
            			foreach( $row_quotes as $key=>$value )
            			{
            				$quotes_array[$key] = $value;
            			}
            	
            			array_push($author_array['quotes'],$quotes_array);
            		}
            	
            		array_push($authorList_array,$author_array);
            	}
            	

            	return $authorList_array;
            	
            }
   else if($category_id != 'all' && $author_id != 'all' && $quote_id == 'null')
            {
            	// Single author Single Category
            	       
            	$stmtauthor = $this->conn->prepare("SELECT * from author
            			WHERE id_author = ".$author_id."");
            	$stmtauthor->execute();
            	 
            	$resultauthor = $stmtauthor->get_result();
            	$stmtauthor->close();
            	 
            	 
            	while ($row_authors = $resultauthor->fetch_assoc())
            	{
            		foreach( $row_authors as $key=>$value )
            		{
            			$author_array[$key] = $value;
            		}
            		 
            		$author_array['quotes'] = array();
            		$id_author = $row_authors['id_author'];
            		 
            		 
            		
            	
            						
            						
            		$stmtQuote = $this->conn->prepare(
            				"SELECT q.* ,c.category_name
                			FROM quotes q ,categories c
                			WHERE q.id_category = c.id_category
                			AND id_author = ".$id_author." 
            				AND q.id_category = ".$category_id."");
            		 
            		$stmtQuote->execute();
            		$resultQuote = $stmtQuote->get_result();
            		$stmtQuote->close();
            		 
            		 
            		while ($row_quotes = $resultQuote->fetch_assoc())
            		{
            			foreach( $row_quotes as $key=>$value )
            			{
            				$quotes_array[$key] = $value;
            			}
            			 
            			array_push($author_array['quotes'],$quotes_array);
            		}
            		 
            		array_push($authorList_array,$author_array);
            	}
            	 
            	
            	return $authorList_array;
            	 
            }
            else if($category_id != 'all' && $author_id == 'all' && $quote_id == 'null')
            {
            	// Single Category All author
            
            	$stmtauthor = $this->conn->prepare("SELECT * from authors");
            	$stmtauthor->execute();
            
            	$resultauthor = $stmtauthor->get_result();
            	$stmtauthor->close();
            
            
            	while ($row_authors = $resultauthor->fetch_assoc())
            	{
            		foreach( $row_authors as $key=>$value )
            		{
            			$author_array[$key] = $value;
            		}
            		 
            		$author_array['quotes'] = array();
            		$id_author = $row_authors['id_author'];
            		 
            		 
            
            		 
            
       
            		$stmtQuote = $this->conn->prepare(
            				"SELECT q.* ,c.category_name
                			FROM quotes q ,categories c
                			WHERE q.id_category = c.id_category
                			AND id_author = ".$id_author."
            				AND q.id_category = ".$category_id."");
            		 
            		$stmtQuote->execute();
            		$resultQuote = $stmtQuote->get_result();
            		$stmtQuote->close();
            		 
            		 
            		while ($row_quotes = $resultQuote->fetch_assoc())
            		{
            			foreach( $row_quotes as $key=>$value )
            			{
            				$quotes_array[$key] = $value;
            			}
            
            			array_push($author_array['quotes'],$quotes_array);
            		}
            		 
            		array_push($authorList_array,$author_array);
            	}
            
            	 
            	return $authorList_array;
            
            }
            else if($category_id == 'null' && $author_id == 'null' 
            		&& $quote_id != 'null')
            {
            	// Single Category All author
            	
          
            
            	$stmtauthor = $this->conn->prepare("SELECT a.* 
													FROM author a, quotes q
													WHERE a.id_author = q.id_author
													AND id_quote = ".$quote_id."");
            	$stmtauthor->execute();
            
            	$resultauthor = $stmtauthor->get_result();
            	$stmtauthor->close();
            
            
            	while ($row_authors = $resultauthor->fetch_assoc())
            	{
            		foreach( $row_authors as $key=>$value )
            		{
            			$author_array[$key] = $value;
            		}
            		 
            		$author_array['quotes'] = array();
            		$id_author = $row_authors['id_author'];
            		 
            		 
            
            		 
            
            
            		$stmtQuote = $this->conn->prepare(
            				"SELECT q.* ,c.category_name
                			FROM quotes q ,categories c
                			WHERE q.id_category = c.id_category
                			AND id_author = ".$id_author."
            				AND id_quote = ".$quote_id."");
            		 
            		$stmtQuote->execute();
            		$resultQuote = $stmtQuote->get_result();
            		$stmtQuote->close();
            		 
            		 
            		while ($row_quotes = $resultQuote->fetch_assoc())
            		{
            			foreach( $row_quotes as $key=>$value )
            			{
            				$quotes_array[$key] = $value;
            			}
            
            			array_push($author_array['quotes'],$quotes_array);
            		}
            		 
            		array_push($authorList_array,$author_array);
            	}
            
            
            	return $authorList_array;
            
            }
    }
    
	
	public function createCouponRequest($id_offer, $id_retailer,$id_user) {
		
		$digits= 4;
		$coupon_code = rand(pow(10,$digits-1),pow(10, $digits)-1);
        $stmt = $this->conn->prepare("INSERT INTO coupon_requests(id_offer,id_retailer,id_user,coupon_code) VALUES($id_offer, $id_retailer,$id_user,$coupon_code)");
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $new_coupon_id = $this->conn->insert_id;
           
            if ($new_coupon_id) {
                // task created successfully
                return $new_coupon_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }
    }
	
	 public function getCouponById($coupon_id) {
        $stmt = $this->conn->prepare("SELECT * FROM coupon_requests WHERE id = $coupon_id");
        
        $stmt->execute();
            		
        $result = $stmt->get_result();
            		
        $stmt->close();
            	
        while ($rows = $result->fetch_assoc())
        {
        foreach( $rows as $key=>$value )
        	{
                    	$coupon_array[$key] = $value;
            	}
            	
        }
        
        return $coupon_array;
            	
       
    }
	
    
    public function  getServiceProviders($city_id,$category_id) {
    
    	 


    		$stmt = $this->conn->prepare("SELECT s.*, c.city_name
 FROM service_providers s, cities c, service_categories sc
WHERE s.city_id = c.id AND s.category_id =sc.id AND s.city_id=$city_id AND s.category_id = $category_id 
 AND s.status = 1 
 ORDER BY name  ASC");
    		$stmt->execute();
    		$result = $stmt->get_result();
    		$stmt->close();
                
                
    		$item_array = array();
    
    		while($row = $result->fetch_assoc())
    		{
    			
    			
    			foreach( $row as $key=>$value )
    			{
    				$item_temp[$key] = $value;
    			}
    			 
    			array_push($item_array,$item_temp);
    			 
    		}
    

    		return $item_array;
    

    }
	
	
    
     public function  getServiceCategories() {
    
    	 


    		$stmt = $this->conn->prepare("SELECT * from service_categories  
    				WHERE status = 1 ORDER BY name  ASC");
    		$stmt->execute();
    		$result = $stmt->get_result();
    		$stmt->close();
                
                
    		$item_array = array();
    
    		while($row = $result->fetch_assoc())
    		{
    			
    			
    			foreach( $row as $key=>$value )
    			{
    				$item_temp[$key] = $value;
    			}
    			 
    			array_push($item_array,$item_temp);
    			 
    		}
    

    		return $item_array;
    

    }
    
     public function  getOfferCategories() {
    
    	 


    		$stmt = $this->conn->prepare("SELECT * from offer_categories  
    				WHERE status = 1 ORDER BY position  ASC");
    		$stmt->execute();
    		$result = $stmt->get_result();
    		$stmt->close();
                
                
    		$item_array = array();
    
    		while($row = $result->fetch_assoc())
    		{
    			
    			
    			foreach( $row as $key=>$value )
    			{
    				$item_temp[$key] = $value;
    			}
    			 
    			array_push($item_array,$item_temp);
    			 
    		}
    

    		return $item_array;
    

    }
	
 public function  getShoppingCategories() {
    
    	 


    		$stmt = $this->conn->prepare("SELECT * from shopping_categories  
    				WHERE status = 1 ORDER BY position  ASC");
    		$stmt->execute();
    		$result = $stmt->get_result();
    		$stmt->close();
                
                
    		$item_array = array();
    
    		while($row = $result->fetch_assoc())
    		{
    			
    			
    			foreach( $row as $key=>$value )
    			{
    				$item_temp[$key] = $value;
    			}
    			 
    			array_push($item_array,$item_temp);
    			 
    		}
    

    		return $item_array;
    

    }
	
	
	 public function updateValidateCoupon($coupon_id) {
        $stmt = $this->conn->prepare("UPDATE coupon_requests set status = 1 WHERE id = $coupon_id");
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }
	
	
	
	public function  getRetailers($city_id,$offer_category_id) {
    
    	 


    		$stmt = $this->conn->prepare("SELECT s.*, c.city_name
 FROM retailers s, cities c, offer_categories sc
WHERE s.city_id = c.id AND s.category_id =sc.id AND s.city_id=$city_id AND s.category_id = $offer_category_id 
 AND s.status = 1 
 ORDER BY name  ASC");
    		$stmt->execute();
    		$result = $stmt->get_result();
    		$stmt->close();
                
                
    		$item_array = array();
    
    		while($row = $result->fetch_assoc())
    		{
    			
    			
    			foreach( $row as $key=>$value )
    			{
    				$item_temp[$key] = $value;
    			}
    			 
    			array_push($item_array,$item_temp);
    			 
    		}
    

    		return $item_array;
    

    }
	
	
                
     public function  getAdSliders() {
    
    	 


    		$stmt = $this->conn->prepare("SELECT * from adsliders  
    				WHERE status = 1");
    		$stmt->execute();
    		$result = $stmt->get_result();
    		$stmt->close();
                
                
    		$item_array = array();
    
    		while($row = $result->fetch_assoc())
    		{
    			
    			
    			foreach( $row as $key=>$value )
    			{
    				$item_temp[$key] = $value;
    			}
    			 
    			array_push($item_array,$item_temp);
    			 
    		}
    

    		return $item_array;
    

    }
    public function  getCategories() {
    
    	 


    		$stmt = $this->conn->prepare("SELECT * from categories 
    				ORDER BY category_name  ASC");
    		$stmt->execute();
    		$result = $stmt->get_result();
    		$stmt->close();
                
                
    		$categories_array = array();
    
    		while($row = $result->fetch_assoc())
    		{
    			
    			
    			foreach( $row as $key=>$value )
    			{
    				$categories_temp[$key] = $value;
    			}
    			 
    			array_push($categories_array,$categories_temp);
    			 
    		}
    

    		return $categories_array;
    

    }
    
    
    public function  getAuthors($sort) {
    
    
    	$stmt = null;
    	
    	if($sort == "by_asc"){
    		
    		$stmt = $this->conn->prepare("SELECT * from authors
    				ORDER BY author_name  ASC");
    	}
    	else if ($sort == "by_likes"){
    		
    		$stmt = $this->conn->prepare("SELECT * from authors
    				ORDER BY author_likes_count DESC");
    	}

    	$stmt->execute();
    
    	$result = $stmt->get_result();
    	$stmt->close();
    	$authors_array = array();
    
    	while($row = $result->fetch_assoc())
    	{
    		 
    		 
    		foreach( $row as $key=>$value )
    		{
    			$authors_temp[$key] = $value;
    		}
    
    		array_push($authors_array,$authors_temp);
    
    	}
    
    
    	return $authors_array;
    
    
    }

    /**
     * Fetching all user tasks
     * @param String $user_id id of the user
     */ 
    public function getAllUserTasks($user_id) {
        $stmt = $this->conn->prepare("SELECT t.* FROM tasks t, user_tasks ut WHERE t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        
        return $tasks;
    }

    /**
     * Updating task
     * @param String $task_id id of the task
     * @param String $task task text
     * @param String $status task status
     */
    public function updateTask($user_id, $task_id, $task, $status) {
        $stmt = $this->conn->prepare("UPDATE tasks t, user_tasks ut set t.task = ?, t.status = ? WHERE t.id = ? AND t.id = ut.task_id AND ut.user_id = ?");
        $stmt->bind_param("siii", $task, $status, $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Deleting a task
     * @param String $task_id id of the task to delete
     */
    public function deleteTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("DELETE t FROM tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /* ------------- `user_tasks` table method ------------------ */

    /**
     * Function to assign a task to user
     * @param String $user_id id of the user
     * @param String $task_id id of the task
     */
    public function createUserTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("INSERT INTO user_tasks(user_id, task_id) values(?, ?)");
        $stmt->bind_param("ii", $user_id, $task_id);
        $result = $stmt->execute();

        if (false === $result) {
            die('execute() failed: ' . htmlspecialchars($stmt->error));
        }
        $stmt->close();
        return $result;
    }

}

?>