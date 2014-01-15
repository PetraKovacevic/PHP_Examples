<?php
class User{
	private $_db,
			$_data,
			$_sessionName,
			$_cookieName,
			$_isLoggedIn;
	
	public function __construct($user = null){
		$this->_db = DB::getInstance();
		
		$this->_sessionName = Config::get('session/session_name');
		$this->_cookieName = Config::get('remember/cookie_name');
	
		if(!$user){
			if(Session::exists($this->_sessionName)){
				$user = Session::get($this->_sessionName);
				
				if($this->find($user)){
					$this->_isLoggedIn = true;
				}else{
					// process logout
				}
			}
		}else{
			$this->find($user);
		}
	}
	
	public function create($fields = array()){
		if(!$this->_db->insert('users', $fields)){
			throw new Exception('There was a problem creating an account!');
		}
	}
	
	public function update($fields = array(), $id = null){
	
		if(!$id && $this->isLoggedIn()){
			$id = $this->data()->id;
		}
		
		if(!$this->_db->update('users', $id, $fields)){
			throw new Exception('There was a problem updating');
		}
	}
	
	public function find($user = null){
		if($user){
			$field = (is_numeric($user)) ? 'id' : 'username';
			$data = $this->_db->get('users', array($field, '=', $user));
			
			if($data->count()){
				$this->_data = $data->first();
				return true;
			}
		}
		return false;
	}
	
	public function login($username = null, $password = null, $remember = false){
		
		// 1. Remember me scenario:
		// if the username and password are not defined and
		// the user id exists in the db then create a session for user
		
		// The remember me allows users to be automatically
		// logged in even when the session has expired
		
		// 2. Normal login scenario with username and password
		if(!$username && !$password && $this->exists()){
			Session::put($this->_sessionName, $this->data()->id);
		}else{
			$user = $this->find($username);
			if($user){
				if($this->data()->password === Hash::make($password, $this->data()->salt)){
					Session::put($this->_sessionName, $this->data()->id);
					
					if($remember){
						$hashCheck = $this->_db->get('users_session', array('user_id' , '=', $this->data()->id));
					
						if(!$hashCheck->count()){
							$hash = Hash::unique();
							$this->_db->insert('users_session', array(
								'user_id' => $this->data()->id,
								'hash' => $hash
							));
						}else{
							$hash = $hashCheck->first()->hash;
						}
						
						Cookie::put($this->_cookieName, $hash, Config::get('remember/cookie_expiry'));
					} 
					
					return true;
				}
			}
		}
		return false;
	}
	
	public function exists(){
		return (!empty($this->_data)) ? true : false;
	}
	
	public function logout(){
		// when logging out, delete the session, but also delete
		// the cookie. Otherwise the user will neve be able to logout
		// because it would keep logging the uesr in because a cookie exists.
		// Also delete from the database the hash.
		
		$this->_db->delete('users_session', array('user_id', '=', $this->data()->id));
		
		Session::delete($this->_sessionName);
		Cookie::delete($this->_cookieName);
	}
	
	public function hasPermission($key){
		$group = $this->_db->get('groups', array('id', '=', $this->data()->group));
		if($group->count()){
			$permimssions = json_decode($group->first()->permissions, true);
			
			if($permimssions[$key] == true){
				return true;
			}
		}
		
		return false;
	}
	
	public function data(){
		return $this->_data;
	}
	
	public function isLoggedIn(){
		return $this->_isLoggedIn;
	}
	
	// Remember me -> a cookie with a hash value is stored.
	// The hash value and user id is stored in the database
	// (users_session table) if the user selects the remember
	// me checkbox. The next time the user visits the website
	// it will look up the hash stored in the cookie and match
	// the hash to a user id in the database. Then the user_id
	// is assigned to the session that automatically loggs the
	// user in.
}
?>