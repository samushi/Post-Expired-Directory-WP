<?php  

class PostUtility{
	public static $domain;
	public static $directory_slug;
	
	public function __construct(){}

	public static function _postExpiratorExpireType($opts) {
		if (empty($opts)) return false;

		extract($opts);
		if (!isset($name)) return false;
		if (!isset($id)) $id 				= $name;
		if (!isset($disabled)) $disabled 	= false;
		if (!isset($onchange)) $onchange 	= '';
		if (!isset($type)) 	   $type 		= '';

		$rv = array();
		$rv[] = '<select name="'.$name.'" id="'.$id.'"'.($disabled == true ? ' disabled="disabled"' : '').' onchange="'.$onchange.'">';
			$rv[] = '<option value="draft" '. ($selected == 'draft' ? 'selected="selected"' : '') . '>'.__('Draft',self::$domain).'</option>';
			$rv[] = '<option value="delete" '. ($selected == 'delete' ? 'selected="selected"' : '') . '>'.__('Delete',self::$domain).'</option>';
			$rv[] = '<option value="private" '. ($selected == 'private' ? 'selected="selected"' : '') . '>'.__('Private',self::$domain).'</option>';
		$rv[] = '</select>';
		return implode("<br/>/n",$rv);
	}

	public static function _scheduleExpiratorEvent($id,$ts,$opts) {

		if (wp_next_scheduled('DevpostExpiratorExpire',array($id)) !== false) {
			wp_clear_scheduled_hook('DevpostExpiratorExpire',array($id)); //Remove any existing hooks
		}
		
		wp_schedule_single_event($ts,'DevpostExpiratorExpire',array($id)); 

		// Update Post Meta
	    update_post_meta($id, '_Devexpiration-date', $ts);
	    update_post_meta($id, '_Devexpiration-date-options', $opts);
		update_post_meta($id, '_Devexpiration-date-status','saved');
	}

	public static function _unscheduleExpiratorEvent($id) {
	   
		delete_post_meta($id, '_Devexpiration-date'); 
		delete_post_meta($id, '_Devexpiration-date-options');

		// Delete Scheduled Expiration
		if (wp_next_scheduled('postExpiratorExpire',array($id)) !== false) {
			wp_clear_scheduled_hook('DevpostExpiratorExpire',array($id)); //Remove any existing hooks
		}
		update_post_meta($id, '_Devexpiration-date-status','saved');
	}
}


?>