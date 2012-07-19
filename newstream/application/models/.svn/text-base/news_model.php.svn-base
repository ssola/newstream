<?php
class News_model extends CI_Model {
	public function __construct() {
		parent::__construct();
	}

	public function itemExists($url) {
		if ( !empty ( $url ) ) {
			$where = array("url" => $url);
			$result = $this->mongo_db->where($where)->get("news");
			if ( $result ) {
				return true;
			}
		}
		
		return false;
	}
	
	public function saveItem($data) {
		if ( !empty ( $data ) ) {
			if ( $this->mongo_db->insert("news", $data ) ) {
				return true;
			}
		}
		
		return false;
	}
}