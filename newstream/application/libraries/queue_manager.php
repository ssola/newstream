<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Library to handle queue job tasks
 * @author ssola
 */

class Queue_manager {
	private $ci;
	private $queue_collection = "queue_tasks";
	private $priorities = array(
		"high" => 1, "medium" => 5, "low" => 10
	);
	private $params_allowed = array(
		"task", "created", "priority", "params", "execute"
	);
	private static $processes;
	
	public function __construct() {
		$this->ci =& get_instance();
	}
	
	public function addToQueue($data) {
		if ( !empty ( $data ) ) {
			if ( $this->validateInput($data) ) {
				$data['status'] = "created";
				if ( $this->saveToQueue($data) ) {
					return true;
				}
			}
		}
		
		return false;
	}
	
	public function getProcess() {
		$where = array(
			 "status" => array(
				"\$in" => array(
						"created", "executing"
				)
			)
		);		
		
		$tasks = $this->ci->mongo_db->where($where)->order_by(array("priority" => "ASC"))->get($this->queue_collection);
		if ( $tasks ) {
			return $tasks;
		}
		
		return false;
	}
	
	public function retainTask($id) {
		if ( !empty ( $id ) ) {
			$where = array("_id" => new MongoID($id));
			$set = array(
				"status" => "processing",
				"retained" => time()
			);
			
			if( $this->ci->mongo_db->where($where)->set($set)->update($this->queue_collection ) ) {
				return true;
			}
		}
		
		return false;
	}
	
	public function releaseTask ( $id ) {
		if ( !empty ( $id ) ) {
			$where = array("_id" => new MongoID($id));
			$set = array(
				"status" => "completed",
				"completed" => time()
			);
			
			if( $this->ci->mongo_db->where($where)->set($set)->update($this->queue_collection ) ) {
				return true;
			}
		}
		
		return false;
	}
	
	public function existsTask($name, $uid, $difference) {
		$time = 0;
		if ( !empty ( $uid ) && !empty ( $difference ) ) {
			$time = time() + $difference;
			
			$where = array(
				"task" => $name,
				"execute" => array(
						"\$gt" => $time 
					),
				 "params" => array(
				 		"uid" => $uid
					), 
				 "status" => array(
						"\$in" => array(
								"created", "executing"
						)
					)
				);
				
			$result = $this->ci->mongo_db->where($where)->get($this->queue_collection);
			if ( $result ) {
				return true;
			}
		}
		
		return false;
	}
	
	public function process($task) {
		if ( !empty ( $task ) ) {
			$instance = self::$processes[$task['task']];
			if ( $instance ) {
				if ( is_callable($instance ) ) {
					return call_user_func($instance, $task['params']);
				}
			}
		}
	}
	
	public function addProcess($name, $instance ) {
		if ( !empty ( $name ) && is_callable($instance) ) {
			self::$processes[$name] = $instance;
			return true;
		}
		
		return false;
	}
	
	private function validateInput($data) {
		foreach ( $data as $key => $value ) {
			if ( !in_array($key, $this->params_allowed) ) {
				return false;
			}
		}
		
		return true;
	}
	
	private function saveToQueue($data) {
		if ( !empty ( $data ) ) {
			if  ( empty ( $data['execute'] ) ) {
				$data['execute'] = time();
			}
			
			$data['priority'] = $this->priorities[$data['priority']];
			
			if ( $response= $this->ci->mongo_db->insert($this->queue_collection,$data) ) {
				return true;
			}
		}
		
		return false;
	}
}