<?php
/*
Plugin Name: Laracodes Post Expired
Description: Wordpress plugin dedicated for directory theme posts
Author: Sami Maxhuni
Version: 1.0.0
Author URI: http://laracodes.com
Text Domain: dev-oferta
*/

if(!class_exists('wp_directory') AND !class_exists('dev_oferta')){

	define('DEVOFERTA_VERSION','1.0.0');
	define('DEVOFERTA_DATEFORMAT',__('l F jS, Y','dev-oferta'));
	define('DEVOFERTA_TIMEFORMAT',__('g:ia','dev-oferta'));
	define('DEVOFERTA_EXPIREDEFAULT','custom');
	define('DEVOFERTA_EXPIREDEFAULTTIME','+5 minutes');
	define('DEVOFERTA_EXPIREDEFAULTYPE','delete');

	register_activation_hook( __FILE__, array( 'dev_oferta', 'plugin_activation' ) );
	register_deactivation_hook( __FILE__, array( 'dev_oferta', 'plugin_deactivation' ) );

	// Require Files
	require_once('debug.php');
	require_once('PostUtility.php');

	class dev_oferta{

		// Global Variables
		public $domain 				    = 'dev-oferta'; // Plugin Text Domain for language
		public $directory_slug 		    = 'directory'; // Default Directory Themes slug menu
		
		/* ==========================================================================
		  Class Constructor
		============================================================================= */
		public function __construct(){
			global $post, $wp_query, $cs_theme_options;

			// Post Utility Class Settings
			PostUtility::$domain 			= $this->domain;
			PostUtility::$directory_slug 	= $this->directory_slug;

			// Getting slug of directory post
			$this->directory_slug();

			// Fire Hooks
			$this->fire_actions();

		}

		/* ==========================================================================
		  Custom Post Directory Slug
		============================================================================= */
		private function directory_slug(){
			global $cs_theme_options;

			if(isset($cs_theme_options['cs_directory_menu_slug']) && !empty($cs_theme_options['cs_directory_menu_slug'])){
				$this->directory_slug = trim($cs_theme_options['cs_directory_menu_slug']);
			}
		}

		/* ==========================================================================
		  Custom Post Directory Column Head Title
		============================================================================= */
		public function create_column_onpost($defaults){
			$defaults['expired_post'] = __('Skadon', $this->domain);
			$defaults['private_post'] = __('Konfirmim', $this->domain);
	    	return $defaults;	
		}

		/* ==========================================================================
		  Custom Post Directory Column Content
		============================================================================= */
		public function create_column_head($column_name, $post_ID){
			global $cs_theme_options;


			// Expired Post Content for Column
			if ($column_name == 'expired_post') {
				$this->expired_free_post_cl_content($post_ID);
			}

			// Private Post Content for Column
			if ($column_name == 'private_post') {
			  	$this->private_post_column_content($post_ID);
			}
		}

		/* ==========================================================================
		  Private Post Content
		============================================================================= */

		public function private_post_column_content($post_ID){
			$status = get_post_status ( $post_ID );
			?>
			<select data-post_id="<?php echo $post_ID; ?>" name="dev-private-post" class="dev-private-post">
			  	<option value="publish" <?php selected( $status, 'publish' ) ?> ><?php _e('Konfirmuar', $this->domain); ?></option>
			  	<option value="private" <?php selected( $status, 'private' ) ?> ><?php _e('Jo Konfirmuar', $this->domain); ?></option>
			</select>
			<?php
		}

		/* ==========================================================================
		  Expired Post Column Content
		============================================================================= */

		public function expired_free_post_cl_content($post_ID){
			if($this->check_itsfree_pkg($post_ID)){
				$ed = get_post_meta($post_ID,'_Devexpiration-date',true);
			    echo "<h4>".($ed ? get_date_from_gmt(gmdate('Y-m-d H:i:s',$ed),get_option('date_format').' '.get_option('time_format')) : __("Nuk posedon datë skadimi",$this->domain))."</h4>";
			}else{
				echo "<h4>".$this->get_nameof_pkg($post_ID, 'name')."</h4>";
			}
		}

		/* ==========================================================================
		  Check Package of POST
		============================================================================= */
		public function check_itsfree_pkg($post_ID){

			// Name of Package
			$directory_pkg_name = get_post_meta( $post_ID, 'cs_directory_pkg_names', true);

			if(isset($directory_pkg_name) && $directory_pkg_name == '0000000000' ){
				return true;
			}else{
				return false;
			}
		}

		/* ==========================================================================
		  Getting Name of Package
		============================================================================= */
		private function get_nameof_pkg($post_ID, $return = 'name'){

			$directory_pkg_name 	= get_post_meta( $post_ID, 'cs_directory_pkg_names', true);
			$cs_packages_options 	= get_option('cs_packages_options');
            
			if(isset($directory_pkg_name) && $directory_pkg_name !== '0000000000' ){
	            if(isset($cs_packages_options) && is_array($cs_packages_options) && count($cs_packages_options)>0){
					foreach($cs_packages_options as $package_key=>$package){
						if(isset($package_key) && $package_key <> ''){
									
							$package_id 	= $package['package_id'];
							$package_title 	= $package['package_title'];

							if($package_id <> '' && $package_title <> ''){
								if(isset($directory_pkg_name) && $package_id==$directory_pkg_name){
									switch ($return) {
										case 'name':
											return $package_title;
										break;
										case 'id':
											return $package_id;
										break;
										default:
											return $package_title;
										break;
									}
								}
							}
						}
					}
				}
			}else{
				return false;
			}
		}

		/* ==========================================================================
		  When fired new post
		============================================================================= */
		public function on_post_fired($post, $wp_error = false){

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

			if($post['post_type'] == $this->directory_slug) {
				//$post['post_content'] = $post['post_content']." <br /> Copyright TEXT";
			}
			return $post;
		}

		/* ==========================================================================
		  Add Metaboxes on Directory Custom Post
		============================================================================= */
		public function create_post_meta(){
			global $post;

			if($post && $this->check_itsfree_pkg($post->ID)){
				add_meta_box('dev_post_expired', __('Mbaron Afati', $this->domain), array(&$this, 'post_meta_content'), $this->directory_slug, 'side', 'core');
			}
		}

		/* ==========================================================================
		  Post Meta Content
		============================================================================= */
		public function post_meta_content(){
			global $post;
			// Get default month
			$expirationdatets 	= get_post_meta($post->ID,'_Devexpiration-date',true);
			$firstsave 			= get_post_meta($post->ID,'_Devexpiration-date-status',true);
			$default 			= '';
			$expireType 		= '';
			$defaults 			= get_option('DevexpirationdateDefaults'.ucfirst($post->post_type));


			if (empty($expirationdatets)) {
				$default = get_option('DevexpirationdateDefaultDate', DEVOFERTA_EXPIREDEFAULT);

				if ($default == 'null') {
					$defaultmonth 	=	date_i18n('m');
					$defaultday 	=	date_i18n('d');
					$defaulthour 	=	date_i18n('H');
					$defaultyear 	=	date_i18n('Y');
					$defaultminute 	= 	date_i18n('i');

				} elseif ($default == 'custom') {

					$custom = get_option('DevexpirationdateDefaultDateCustom', DEVOFERTA_EXPIREDEFAULTTIME);

					if ($custom === false) $ts = time();
					else {
						$tz = get_option('timezone_string');
						if ( $tz ) date_default_timezone_set( $tz );
						
						$ts = time() + (strtotime($custom) - time());
						
						if ( $tz ) date_default_timezone_set('UTC');
					}
					$defaultmonth 	=	get_date_from_gmt(gmdate('Y-m-d H:i:s',$ts),'m');
					$defaultday 	=	get_date_from_gmt(gmdate('Y-m-d H:i:s',$ts),'d');
					$defaultyear 	=	get_date_from_gmt(gmdate('Y-m-d H:i:s',$ts),'Y');;
					$defaulthour 	=	get_date_from_gmt(gmdate('Y-m-d H:i:s',$ts),'H');
					$defaultminute 	=	get_date_from_gmt(gmdate('Y-m-d H:i:s',$ts),'i');
				} 

				$enabled = '';
				$disabled = ' disabled="disabled"';

				if (isset($defaults['expireType'])) {
					$expireType = $defaults['expireType'];
				}

				if (isset($defaults['autoEnable']) && ($firstsave !== 'saved') && ($defaults['autoEnable'] === true || $defaults['autoEnable'] == 1)) { 
					$enabled = ' checked="checked"'; 
					$disabled='';
				} 


			} else {
				$defaultmonth 	=	get_date_from_gmt(gmdate('Y-m-d H:i:s',$expirationdatets),'m');
				$defaultday 	=	get_date_from_gmt(gmdate('Y-m-d H:i:s',$expirationdatets),'d');
				$defaultyear 	=	get_date_from_gmt(gmdate('Y-m-d H:i:s',$expirationdatets),'Y');
				$defaulthour 	=	get_date_from_gmt(gmdate('Y-m-d H:i:s',$expirationdatets),'H');
				$defaultminute 	=	get_date_from_gmt(gmdate('Y-m-d H:i:s',$expirationdatets),'i');
				$enabled 		= 	' checked="checked"';
				$disabled 		= 	'';
				$opts 			= 	get_post_meta($post->ID,'_Devexpiration-date-options',DEVOFERTA_EXPIREDEFAULTYPE);

				if (isset($opts['expireType'])) {
		           	$expireType = $opts['expireType'];
				}
			}

			$rv = array();
			$rv[] = '<p><input type="checkbox" name="enable-expirationdate" id="enable-expirationdate" value="checked"'.$enabled.' />';
			$rv[] = '<label for="enable-expirationdate">'.__('Aktivizo Skadimin e Postit',$this->domain).'</label></p>';

			if ($default == 'publish') {
				$rv[] = '<em>'.__('Publikimi i datës/kohës do të përdoret si vlera e skadimit',$this->domain).'</em><br/>';
			} else {
				$rv[] = '<table><tr>';
				$rv[] = '<th style="text-align: left;">'.__('Viti', $this->domain).'</th>';
				$rv[] = '<th style="text-align: left;">'.__('Muajë',$this->domain).'</th>';
				$rv[] = '<th style="text-align: left;">'.__('Ditë', $this->domain).'</th>';
				$rv[] = '</tr><tr>';
				$rv[] = '<td>';	
				$rv[] = '<select name="expirationdate_year" id="expirationdate_year"'.$disabled.'>';
				$currentyear = date('Y');
			
				if ($defaultyear < $currentyear) $currentyear = $defaultyear;

				for($i = $currentyear; $i < $currentyear + 8; $i++) {
					if ($i == $defaultyear)
						$selected = ' selected="selected"';
					else
						$selected = '';
					$rv[] = '<option'.$selected.'>'.($i).'</option>';
				}
				$rv[] = '</select>';
				$rv[] = '</td><td>';
				$rv[] = '<select name="expirationdate_month" id="expirationdate_month"'.$disabled.'>';

				for($i = 1; $i <= 12; $i++) {
					if ($defaultmonth == date_i18n('m',mktime(0, 0, 0, $i, 1, date_i18n('Y'))))
						$selected = ' selected="selected"';
					else
						$selected = '';
					$rv[] = '<option value="'.date_i18n('m',mktime(0, 0, 0, $i, 1, date_i18n('Y'))).'"'.$selected.'>'.date_i18n('F',mktime(0, 0, 0, $i, 1, date_i18n('Y'))).'</option>';
				}

				$rv[] = '</select>';	 
				$rv[] = '</td><td>';
				$rv[] = '<input type="text" id="expirationdate_day" name="expirationdate_day" value="'.$defaultday.'" size="2"'.$disabled.' />,';
				$rv[] = '</td></tr><tr>';
				$rv[] = '<th style="text-align: left;"></th>';
				$rv[] = '<th style="text-align: left;">'.__('Orë', $this->domain).'('.date_i18n('T',mktime(0, 0, 0, $i, 1, date_i18n('Y'))).')</th>';
				$rv[] = '<th style="text-align: left;">'.__('Minutë', $this->domain).'</th>';
				$rv[] = '</tr><tr>';
				$rv[] = '<td>@</td><td>';
			 	$rv[] = '<select name="expirationdate_hour" id="expirationdate_hour"'.$disabled.'>';

				for($i = 1; $i <= 24; $i++) {
					if ($defaulthour == date_i18n('H',mktime($i, 0, 0, date_i18n('n'), date_i18n('j'), date_i18n('Y'))))
						$selected = ' selected="selected"';
					else
						$selected = '';
					$rv[] = '<option value="'.date_i18n('H',mktime($i, 0, 0, date_i18n('n'), date_i18n('j'), date_i18n('Y'))).'"'.$selected.'>'.date_i18n('H',mktime($i, 0, 0, date_i18n('n'), date_i18n('j'), date_i18n('Y'))).'</option>';
				}

				$rv[] = '</select></td><td>';
				$rv[] = '<input type="text" id="expirationdate_minute" name="expirationdate_minute" value="'.$defaultminute.'" size="2"'.$disabled.' />';
				$rv[] = '</td></tr></table>';
			}
			$rv[] = '<input type="hidden" name="Devexpirationdate_formcheck" value="true" />';
			echo implode("\n",$rv);

			echo '<br/>'.__('Si të skadojë', $this->domain).': ';
			echo PostUtility::_postExpiratorExpireType(array('type' => $post->post_type, 'name'=>'expirationdate_expiretype','selected'=>$expireType,'disabled'=>$disabled));
			echo '<br/>';

			echo '<div id="expirationdate_ajax_result"></div>';
		}

		function expirationdate_update_post_meta($id) {
			// don't run the echo if this is an auto save
			if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
				return;

			// don't run the echo if the function is called for saving revision.
		        $posttype = get_post_type($id);
			if ( $posttype == 'revision' )
				return;

			if (!isset($_POST['Devexpirationdate_formcheck']))
				return;

			if (isset($_POST['enable-expirationdate'])) {
		        	$default = get_option('DevexpirationdateDefaultDate',DEVOFERTA_EXPIREDEFAULT);
				if ($default == 'publish') {
				        $month 	 = intval($_POST['mm']);
			       		$day 	 = intval($_POST['jj']);
		        		$year 	 = intval($_POST['aa']);
		        		$hour 	 = intval($_POST['hh']);
				        $minute  = intval($_POST['mn']);
				} else {
				        $month	 = intval($_POST['expirationdate_month']);
		       			$day 	 = intval($_POST['expirationdate_day']);
				        $year 	 = intval($_POST['expirationdate_year']);
			       		$hour 	 = intval($_POST['expirationdate_hour']);
		        		$minute  = intval($_POST['expirationdate_minute']);
				}

				$opts = array();
				$ts = get_gmt_from_date("$year-$month-$day $hour:$minute:0",'U');

				// Schedule/Update Expiration
				$opts['expireType'] = $_POST['expirationdate_expiretype'];
				$opts['id'] = $id;
			
				PostUtility::_scheduleExpiratorEvent($id,$ts,$opts);
			} else {
				PostUtility::_unscheduleExpiratorEvent($id);
			}
		}

		/* ==========================================================================
		  The new expiration function, to work with single scheduled events.
		============================================================================= */
		function postExpiratorExpire($id) {

			if (empty($id)) { 
				return false;
			}

			if (is_null(get_post($id))) {
				return false;
			}

			$postoptions = get_post_meta($id,'_Devexpiration-date-options',DEVOFERTA_EXPIREDEFAULTYPE);
			extract($postoptions);

			// Check for default expire only if not passed in
			if (empty($expireType)) {
				$posttype = get_post_type($id);
				
				if ($posttype == 'post') {
					$expireType = strtolower(get_option('DevexpirationdateExpiredPostStatus', DEVOFERTA_EXPIREDEFAULTYPE));
				}elseif($posttype == $this->directory_slug){
					$expireType = strtolower(get_option('DevexpirationdateExpiredCustomPostStatus', DEVOFERTA_EXPIREDEFAULTYPE));
				} else {
					$expireType = apply_filters('Devpostexpirator_custom_posttype_expire', $expireType, $posttype); //hook to set defaults for custom post types
				}
			}

			// Remove KSES - wp_cron runs as an unauthenticated user, which will by default trigger kses filtering,
			// even if the post was published by a admin user.  It is fairly safe here to remove the filter call since
			// we are only changing the post status/meta information and not touching the content.
			kses_remove_filters();

			// Do Work
			if ($expireType == 'draft') {
				if (wp_update_post(array('ID' => $id, 'post_status' => 'draft')) == 0) {
					// Faild
				} else {
					// Processed
				}
			} elseif ($expireType == 'private') {
				if (wp_update_post(array('ID' => $id, 'post_status' => 'private')) == 0) {
					// Faild
				} else {
					// Proccesed
				}
			} elseif ($expireType == 'delete') {
				if (wp_delete_post($id) === false) {
					// Faild
				} else {
					// Proccesed
				}
			}
		}

		/* ==========================================================================
		  Post Expirate Default Dates
		============================================================================= */
		// public function defaultPostDate(){
		// 	global $wpdb;

		// 	// Check for current version, if not exists, run activation
		// 	$version = get_option('DevpostexpiratorVersion');
		// 	if ($version === false) { //not installed, run default activation
		// 		update_option('DevpostexpiratorVersion',DEVOFERTA_VERSION);
		// 	} else {

		// 		if (version_compare($version,'1.0.0') == -1) {
		// 			global $wpdb;

		// 			// Schedule Events/Migrate Config
		// 			$results = $wpdb->get_results($wpdb->prepare('select post_id, meta_value from ' . $wpdb->postmeta . ' as postmeta, '.$wpdb->posts.' as posts where postmeta.post_id = posts.ID AND postmeta.meta_key = %s AND postmeta.meta_value >= %d','Devexpiration-date',time()));

		// 			foreach ($results as $result) {
		// 				wp_schedule_single_event($result->meta_value,'DevpostExpiratorExpire',array($result->post_id));
		// 				$opts = array();
		// 				$opts['id'] = $result->post_id;
		// 				$posttype = get_post_type($result->post_id);
			        	
		// 				if($posttype == $this->directory_slug){
		// 					$opts['expireType'] = strtolower(get_option('DevexpirationdateExpiredCustomPost','Delete'));
		// 				}

		// 				update_post_meta($result->post_id,'_Devexpiration-date-options',$opts);
		// 			}

		// 			// update meta key to new format
		// 			$wpdb->query($wpdb->prepare("UPDATE $wpdb->postmeta SET meta_key = %s WHERE meta_key = %s",'_Devexpiration-date','Devexpiration-date'));

		// 			// migrate defaults
		// 			$custompostdefault = get_option('DevexpirationdateExpiredCustomPostStatus');
		// 			if ($postdefault) update_option('DevexpirationdateExpiredCustomPostStatus',array('expireType' => $custompostdefault));

		// 			delete_option('DevexpirationdateCronSchedule');
		// 			delete_option('DevexpirationdateAutoEnabled');
		// 			delete_option('DevexpirationdateExpiredPageStatus');
		// 			delete_option('DevexpirationdateExpiredPostStatus');
		// 		}
		// 	}
		// }

		/* ==========================================================================
		  Fire Actions/Filter Hooks
		============================================================================= */
		private function fire_actions(){
			// Add column on custom post
			add_filter('manage_'.$this->directory_slug.'_posts_columns', array(&$this, 'create_column_onpost'), 10, 2);
			add_action('manage_'.$this->directory_slug.'_posts_custom_column', array(&$this, 'create_column_head'), 10, 2);

			// When insert new post
			add_action('wp_insert_post_data', array(&$this, 'on_post_fired') ,10,2); 

			// Remove Toolbar from Front End
			add_filter('show_admin_bar', '__return_false');

			// Insert JS files on Admin
			add_action( 'admin_init', array(&$this, 'js_register') );
			add_action( 'admin_print_scripts', array(&$this, 'js_include') );

			// Change Status of Post
			add_action('wp_ajax_dev_oferta_ajax', array(&$this, 'post_status_ajax'));

			// Add Post Meta
			add_action ('add_meta_boxes',array(&$this, 'create_post_meta'));

			// Call when post saved
			add_action('save_post', array(&$this, 'expirationdate_update_post_meta'));

			// Single Event
			add_action('DevpostExpiratorExpire',array(&$this, 'postExpiratorExpire'));

			// Activate Default Dates
			//add_action('admin_init', array(&$this, 'defaultPostDate'));
			
		}

		/* ==========================================================================
		  Register on Admin Panel Javascript
		============================================================================= */
		public function js_register(){
			// Js register
			wp_register_script( 'dev-oferta-js', plugins_url( '/js/init.js', __FILE__ ), array('jquery') );

			//Css register
			wp_register_style( 'dev-oferta-css', plugins_url( '/css/init.css', __FILE__ ) );
		}

		/* ==========================================================================
		  Include JS files
		============================================================================= */
		public function js_include(){
			// Include Javascript file
			wp_enqueue_script( 'dev-oferta-js' );
			wp_localize_script( 'dev-oferta-js', 'dev_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

			// Include Style file
			wp_enqueue_style( 'dev-oferta-css' );
		}

		/* ==========================================================================
		  Post Status Change Ajax
		============================================================================= */
		public function post_status_ajax(){
			if(isset($_POST['post_id']) && isset($_POST['status'])){
				
				global $wpdb;
				$post_ID = $_POST['post_id'];
				$status  = $_POST['status'];

				if ( ! $post = get_post( $post_ID ) ) return;

					
				$wpdb->update( $wpdb->posts, array( 'post_status' => $status ), array( 'ID' => $post->ID ) );

				clean_post_cache( $post->ID );
					
				$old_status = $post->post_status;
				$post->post_status = $status;
				wp_transition_post_status( 'publish', $old_status, $post );

				echo "ok";
				die();
			}
		}

		/* ==========================================================================
		  Get Post Time ago
		============================================================================= */
		private function get_post_time_ago($post_ID){
			$date = get_post_time('G', true, $post_ID);
 
		 
			// Array of time period chunks
			$chunks = array(
				array( 60 * 60 * 24 * 365 , __( 'year', $this->domain ), __( 'years', $this->domain ) ),
				array( 60 * 60 * 24 * 30 , __( 'month', $this->domain ), __( 'months', $this->domain ) ),
				array( 60 * 60 * 24 * 7, __( 'week', $this->domain ), __( 'weeks', $this->domain ) ),
				array( 60 * 60 * 24 , __( 'day', $this->domain ), __( 'days', $this->domain ) ),
				array( 60 * 60 , __( 'hour', $this->domain ), __( 'hours', $this->domain ) ),
				array( 60 , __( 'minute', $this->domain ), __( 'minutes', $this->domain ) ),
				array( 1, __( 'second', $this->domain ), __( 'seconds', $this->domain ) )
			);
		 
			if ( !is_numeric( $date ) ) {
				$time_chunks = explode( ':', str_replace( ' ', ':', $date ) );
				$date_chunks = explode( '-', str_replace( ' ', '-', $date ) );
				$date = gmmktime( (int)$time_chunks[1], (int)$time_chunks[2], (int)$time_chunks[3], (int)$date_chunks[1], (int)$date_chunks[2], (int)$date_chunks[0] );
			}
		 
			$current_time = current_time( 'mysql', $gmt = 0 );
			$newer_date = strtotime( $current_time );
		 
			// Difference in seconds
			$since = $newer_date - $date;
		 
			// Something went wrong with date calculation and we ended up with a negative date.
			if ( 0 > $since )
				return __( 'sometime', $this->domain );
		 
			/**
			 * We only want to output one chunks of time here, eg:
			 * x years
			 * xx months
			 * so there's only one bit of calculation below:
			 */
		 
			//Step one: the first chunk
			for ( $i = 0, $j = count($chunks); $i < $j; $i++) {
				$seconds = $chunks[$i][0];
		 
				// Finding the biggest chunk (if the chunk fits, break)
				if ( ( $count = floor($since / $seconds) ) != 0 )
					break;
			}
		 
			// Set output var
			$output = ( 1 == $count ) ? '1 '. $chunks[$i][1] : $count . ' ' . $chunks[$i][2];
		 
		 
			if ( !(int)trim($output) ){
				$output = '0 ' . __( 'seconds', $this->domain );
			}
		 
			$output .= __(' ago', $this->domain);
		 
			return $output;
		}

		/* ==========================================================================
		  When plugin activated
		============================================================================= */
	    public static function plugin_activation(){	
	    	global $current_blog;

	    	$debug = new DevpostExpiratorDebug();
			$debug->save(array('message' => 'Plugin Activated'));

			add_option( 'dev_oferta_plugin_activation', 'installed' );
			add_option( 'dev_oferta', '1' );
			add_option( 'DevexpirationdateDefaultsDirectory', array('autoEnable' => true, 'expireType' => 'delete') );

			if (get_option('DevexpirationdateDefaultDateFormat') === false)	update_option('DevexpirationdateDefaultDateFormat',DEVOFERTA_DATEFORMAT);
			if (get_option('DevexpirationdateDefaultTimeFormat') === false)	update_option('DevexpirationdateDefaultTimeFormat',DEVOFERTA_TIMEFORMAT);
			if (get_option('DevexpirationdateDefaultDate') === false)		update_option('DevexpirationdateDefaultDate',DEVOFERTA_EXPIREDEFAULT);
			if (get_option('DevexpirationdateDefaultVersion') === false)	update_option('DevexpirationdateDefaultVersion',DEVOFERTA_VERSION);

	    } 		    

	    /* ==========================================================================
	      When plugin deactivated
	    ============================================================================= */
		public static function plugin_deactivation(){
			global $current_blog;
			
			$debug = new DevpostExpiratorDebug();
			$debug->save(array('message' => "Plugin Deactivated"));

	        delete_option( 'dev_oferta_plugin_activation');
			delete_option( 'dev_oferta'); 

			delete_option('DevexpirationdateDefaultDateFormat');
			delete_option('DevexpirationdateDefaultTimeFormat');
			delete_option('DevexpirationdateCronSchedule');
			delete_option('DevexpirationdateDefaultDate');
			delete_option('DevexpirationdateDefaultDateCustom');
			delete_option('DevexpirationdateAutoEnabled');
			delete_option('DevexpirationdateDefaultsPage');
			delete_option('DevexpirationdateDefaultsPost');
			delete_option('DevpostexpiratorVersion');
			delete_option('DevexpirationdateDefaultVersion');
			delete_option('DevexpirationdateDefaultsDirectory' ); // For Custom Post Directory
			## what about custom post types? - how to cleanup?
			if (is_multisite()){
				wp_clear_scheduled_hook('Devexpirationdate_delete_'.$current_blog->blog_id);
			}else{
				wp_clear_scheduled_hook('Devexpirationdate_delete');
			}
	    } 
	}

	// instantiate the plugin class
	$wp_dev_oferta = new dev_oferta();	

}