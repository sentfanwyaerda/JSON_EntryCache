<?php 
if(file_exists(dirname(dirname(__FILE__)).'/JSONplus/JSONplus.php')){require_once(dirname(dirname(__FILE__)).'/JSONplus/JSONplus.php'); }

class JSON_EntryCache {
	var $file = NULL;
	var $db = array();
	function JSON_EntryCache($file=NULL){
		if($file != NULL){
			$file = $this->_get_file_url($file, FALSE);
			$this->file = $file;
			$this->open($file);
		}
	}
	/** HELPER FUNCTIONS **/
	private function _get_file_url($manual=NULL, $recursive=TRUE){
		if( $manual != NULL || $recursive === FALSE ){
			if( file_exists($manual) ){ return $manual; }
			else{ return FALSE; }
		}
		else{
			return $this->_get_file_url($this->file, FALSE);
		}
	}
	private function _set_timestamp($item, $timestamp=FALSE){
		if(!isset($item['timestamp'])){ $item['timestamp'] = ($timestamp != FALSE ? $timestamp : microtime(TRUE)); }
		return $item;
	}
	private function _set_id($item, $id=FALSE){
		if(!isset($item['timestamp'])){ $item = $this->_set_timestamp($item); }
		if(!isset($item['id'])){ $item['id'] = ($id != FALSE ? $id : md5($item['timestamp']) ); }
		return $item;
	}
	private function _add_user($item, $user=NULL){
		if(!isset($item['user'])){
			if($user != NULL){
				$item['user'] = $user;
			}
			elseif( class_exists('Heracles') ){
				$item['user'] = Heracles::get_user_id();
			}
		}
		return $item;
	}
	
	/** MAIN ACTIONS **/
	function open($file=NULL){
		$file = $this->_get_file_url($file);
		if($file){
			$data = file_get_contents($file);
			$this->db = json_decode($data, TRUE);
			return $this->db;
		} else { return FALSE; }
	}
	function save($file=NULL, $PRETTY_PRINT=FALSE){
		$file = $this->_get_file_url($file);
		if($file){
			if($PRETTY_PRINT != FALSE){
				if(defined('JSON_PRETTY_PRINT')){ $data = json_encode($this->db, JSON_PRETTY_PRINT); }
				elseif(class_exists('JSONplus')){ $data = JSONplus::encode($this->db); }
				else{ $data = json_encode($this->db); }
			} else{ $data = json_encode($this->db); }
			return file_put_contents($file, $data);
		} else { return FALSE; }
	}
	
	/** ITEM HANDLING **/
	function get_item_ids(){
		$list = array();
		foreach($this->db as $i=>$o){
			if(isset($o['id'])){ $list[] = $o['id']; }
		}
		return $list;
	}
	function get_item($id){
		foreach($this->db as $i=>$o){
			if(isset($o['id']) && $o['id'] == $id){ return $o; }
		}
		return FALSE;
	}
	function get_item_element($id, $name, $error_result=FALSE){
		$item = $this->get_item($id);
		if(is_array($item) && isset($item[$name])){
			return $item[$name];
		}
		return $error_result;
	}
	function update_item_element($id, $name, $value){
		foreach($this->db as $i=>$o){
			if(isset($o['id']) && $o['id'] == $id){
				$this->db[$i][$name] = $value;
				return TRUE;
			}
		}
		return FALSE;
	}
	function add_raw_item($item=array()){
		if(!is_array($item)){ $item = array(); }
		/*fix*/ $item = $this->_set_id($item);
		/*fix*/ $item = $this->_add_user($item);
		$this->db[] = $item;
		return $item['id'];
	}
	function add_item_stage($id, $stage=array(), $mode="bot"){
		if(!is_array($stage)){ $stage = array(); }
		/*fix*/ $stage = $this->_set_timestamp($stage);
		/*fix*/ $stage = $this->_add_user($stage);
		if(!isset($stage['mode'])){ $stage['mode'] = $mode; }
		
		foreach($this->db as $i=>$o){
			if(isset($o['id']) && $o['id'] == $id){
				if(!isset($this->db[$i]["stage"])){ $this->db[$i]["stage"] = array(); }
				$this->db[$i]["stage"][] = $stage;
			}
		}
	}
	
	
	function import($data){
		if(is_string($data)){ $data = json_decode($data, TRUE); }
		
		$pid = $this->add_raw_item(array('type'=>'import'));
		
		if(!is_array($data)){ return FALSE; }
		foreach($data as $i=>$o){
			$cid = $this->add_raw_item( array('parent'=>$pid,'type'=>'entry') );
			$this->add_item_stage($cid, array('add'=>$o), 'import');
			$this->update_item_element($pid, 'children', array_merge($this->get_item_element($pid, 'children', array()), array($cid)) );
			$this->update_item_element($cid, 'status', 'open');
		}
		$this->update_item_element($pid, 'status', 'succes');
	}
	
	function compile_item($id, $base=array()){}
}

/*TESTING*/
$EC = new JSON_EntryCache(dirname(__FILE__).'/cache.json');

print '<pre>';
$EC->import(file_get_contents('test.json'));
print_r($EC);

$EC->save();

if(class_exists('JSONplus')){ print JSONplus::encode($EC->db); } else { print json_encode($EC->db, JSON_PRETTY_PRINT); }
print '</pre>';

?>