<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* array filter for finding users who have subscribed to GlotPress projects
*/
class GPNotifyFilterUsers {

	protected $project_id;
	protected $locale;
	protected $frequency;

	/**
	* filter the array, returning only users subscribed to specified project
	* @param array[stdClass] $users
	* @param int $project_id
	* @return array
	*/
	public function execute($users, $project_id, $locale = false, $frequency = false) {
		$this->project_id = $project_id;
		$this->locale = $locale;
		$this->frequency = $frequency;
		return array_filter($users, array($this, '_filter'));
	}

	/**
	* array_filter() callback, return true if user has subscribed to project
	* @param stdClass $user
	* @return bool
	*/
	public function _filter($user) {
		if( empty($this->frequency) || (!empty($user->projects['frequency']) && in_array($this->frequency, $user->projects['frequency'])) ){
			if( !empty($this->locale) ){
				return !empty($user->projects['waiting'][$this->project_id][$this->locale]);
			}else{
				return !empty($user->projects['waiting'][$this->project_id]) && !is_array($user->projects['waiting'][$this->project_id]);			
			}
		}
	}

}