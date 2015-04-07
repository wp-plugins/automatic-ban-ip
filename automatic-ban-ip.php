<?php
/**
Plugin Name: Automatic Ban IP
Plugin Tag: ban, ip, automatic, spam, comments, firewall, block
Description: <p>Block IP addresses which are suspicious and try to post on your blog spam comments.</p><p>This plugin need that you create an account on the Honey Pot Project (https://www.projecthoneypot.org, free api) or that you install the Spam Captcha plugin.</p><p>In addition, if you want to geolocate the spammers your may create an account on (http://ipinfodb.com/, free api). Thus, you may display a world map with the concentration of spammers.</p><p>Spammers may be blocked either by PHP based restrictions (i.e. Wordpress generates a 403 page for such identified users) or by Apache based restriction (using Deny from in .htaccess file).</p><p>The Apache restriction is far more efficient when hundreds of hosts sent you spams in few minutes.</p>
Version: 1.0.4
Framework: SL_Framework
Author: SedLex
Author URI: http://www.sedlex.fr/
Author Email: sedlex@sedlex.fr
Framework Email: sedlex@sedlex.fr
Plugin URI: http://wordpress.org/plugins/automatic-ban-ip/
License: GPL3
*/

//Including the framework in order to make the plugin work

require_once('core.php') ; 

/** ====================================================================================================================================================
* This class has to be extended from the pluginSedLex class which is defined in the framework
*/
class automatic_ban_ip extends pluginSedLex {
	
	/** ====================================================================================================================================================
	* Plugin initialization
	* 
	* @return void
	*/
	static $instance = false;

	protected function _init() {
		global $wpdb ; 

		// Name of the plugin (Please modify)
		$this->pluginName = 'Automatic Ban IP' ; 
		
		// The structure of the SQL table if needed (for instance, 'id_post mediumint(9) NOT NULL, short_url TEXT DEFAULT '', UNIQUE KEY id_post (id_post)') 
		$this->tableSQL = "id mediumint(9) NOT NULL AUTO_INCREMENT, count mediumint(9) NOT NULL, ip VARCHAR(100), reason TEXT, geolocate_state TEXT,  time DATETIME, UNIQUE KEY id (id)" ; 
		// The name of the SQL table (Do no modify except if you know what you do)
		$this->table_name = $wpdb->prefix . "pluginSL_" . get_class() ; 

		//Configuration of callbacks, shortcode, ... (Please modify)
		// For instance, see 
		//	- add_shortcode (http://codex.wordpress.org/Function_Reference/add_shortcode)
		//	- add_action 
		//		- http://codex.wordpress.org/Function_Reference/add_action
		//		- http://codex.wordpress.org/Plugin_API/Action_Reference
		//	- add_filter 
		//		- http://codex.wordpress.org/Function_Reference/add_filter
		//		- http://codex.wordpress.org/Plugin_API/Filter_Reference
		// Be aware that the second argument should be of the form of array($this,"the_function")
		// For instance add_action( "wp_ajax_foo",  array($this,"bar")) : this function will call the method 'bar' when the ajax action 'foo' is called
		
		
		// Important variables initialisation (Do not modify)
		$this->path = __FILE__ ; 
		$this->pluginID = get_class() ; 
		
		$this->testIfBlocked() ; 
		
		add_action('comment_post', array(&$this, 'detect_spammeur'), 1000);

		// activation and deactivation functions (Do not modify)
		register_activation_hook(__FILE__, array($this,'install'));
		register_deactivation_hook(__FILE__, array($this,'deactivate'));
		register_uninstall_hook(__FILE__, array('automatic_ban_ip','uninstall_removedata'));
	}
	
	/** ====================================================================================================================================================
	* In order to uninstall the plugin, few things are to be done ... 
	* (do not modify this function)
	* 
	* @return void
	*/
	
	static public function uninstall_removedata () {
		global $wpdb ;
		// DELETE OPTIONS
		delete_option('automatic_ban_ip'.'_options') ;
		if (is_multisite()) {
			delete_site_option('automatic_ban_ip'.'_options') ;
		}
		
		// DELETE SQL
		if (function_exists('is_multisite') && is_multisite()){
			$old_blog = $wpdb->blogid;
			$old_prefix = $wpdb->prefix ; 
			// Get all blog ids
			$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM ".$wpdb->blogs));
			foreach ($blogids as $blog_id) {
				switch_to_blog($blog_id);
				$wpdb->query("DROP TABLE ".str_replace($old_prefix, $wpdb->prefix, $wpdb->prefix . "pluginSL_" . 'automatic_ban_ip')) ; 
			}
			switch_to_blog($old_blog);
		} else {
			$wpdb->query("DROP TABLE ".$wpdb->prefix . "pluginSL_" . 'automatic_ban_ip' ) ; 
		}
		
		// DELETE FILES if needed
		//SLFramework_Utils::rm_rec(WP_CONTENT_DIR."/sedlex/my-plugin/"); 
		$plugins_all = 	get_plugins() ; 
		$nb_SL = 0 ; 	
		foreach($plugins_all as $url => $pa) {
			$info = pluginSedlex::get_plugins_data(WP_PLUGIN_DIR."/".$url);
			if ($info['Framework_Email']=="sedlex@sedlex.fr"){
				$nb_SL++ ; 
			}
		}
		if ($nb_SL==1) {
			SLFramework_Utils::rm_rec(WP_CONTENT_DIR."/sedlex/"); 
		}
	}
	
	/**====================================================================================================================================================
	* Function called when the plugin is activated
	* For instance, you can do stuff regarding the update of the format of the database if needed
	* If you do not need this function, you may delete it.
	*
	* @return void
	*/
	
	public function _update() {
		SLFramework_Debug::log(get_class(), "Update the plugin." , 4) ; 
	}
	
	/**====================================================================================================================================================
	* Function called to return a number of notification of this plugin
	* This number will be displayed in the admin menu
	*
	* @return int the number of notifications available
	*/
	 
	public function _notify() {
		return 0 ; 
	}
	
	
	/** ====================================================================================================================================================
	* Init javascript for the public side
	* If you want to load a script, please type :
	* 	<code>wp_enqueue_script( 'jsapi', 'https://www.google.com/jsapi');</code> or 
	*	<code>wp_enqueue_script('automatic_ban_ip_script', plugins_url('/script.js', __FILE__));</code>
	*	<code>$this->add_inline_js($js_text);</code>
	*	<code>$this->add_js($js_url_file);</code>
	*
	* @return void
	*/
	
	function _public_js_load() {	
		return ; 
	}
	
	/** ====================================================================================================================================================
	* Init css for the public side
	* If you want to load a style sheet, please type :
	*	<code>$this->add_inline_css($css_text);</code>
	*	<code>$this->add_css($css_url_file);</code>
	*
	* @return void
	*/
	
	function _public_css_load() {	
		return ; 
	}
	
	/** ====================================================================================================================================================
	* Init javascript for the admin side
	* If you want to load a script, please type :
	* 	<code>wp_enqueue_script( 'jsapi', 'https://www.google.com/jsapi');</code> or 
	*	<code>wp_enqueue_script('automatic_ban_ip_script', plugins_url('/script.js', __FILE__));</code>
	*	<code>$this->add_inline_js($js_text);</code>
	*	<code>$this->add_js($js_url_file);</code>
	*
	* @return void
	*/
	
	function _admin_js_load() {	
		global $wpdb ; 
		if (trim($this->get_param('geolocate_key'))!=""){
			
			$this->add_js(plugin_dir_url("/").'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'js/jquery-jvectormap-1.2.2.min.js') ; 
			$this->add_js(plugin_dir_url("/").'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'js/jquery-jvectormap-world-mill-en.js') ; 
			
			ob_start();
				$results = $wpdb->get_results("SELECT geolocate, count(*) as nombre FROM ".$this->table_name." GROUP BY geolocate") ; 
				echo "\r\nvar gdpDataSpammer = {\r\n" ; 
				$first = true;
				foreach ($results as $r){
					if (!$first){
						echo ", " ; 
					}
					$rus = @unserialize($r->geolocate) ; 
					if (is_array($rus)){
						$first = false ; 
						echo '"'.$rus['countryCode'].'":'.$r->nombre ; 
					}
				}
				echo "};\r\n" ; 
			?>
				jQuery(function(){
					jQuery('#geolocate_show_world_spammer').vectorMap({
						map: 'world_mill_en',
						backgroundColor: '#A1A1A1',
						series: {
						    regions: [{
						      values: gdpDataSpammer,
							  hoverOpacity: 0.7,
    						  hoverColor: false,
						      scale: ['#FFCCCC', '#FF5050'],
						      normalizeFunction: 'polynomial'
						    }]
						},
						onRegionLabelShow: function(e, el, code){
						    el.html(el.html()+' ('+gdpDataSpammer[code]+')');
						}
					});
				}) ; 
				
			<?php
			
			
				
			$java = ob_get_clean() ; 
			$this->add_inline_js($java) ;
		}	
	}
	
	/** ====================================================================================================================================================
	* Init CSS for the admin side
	*
	* @return void
	*/
	
	function _admin_css_load() {
		$this->add_css(plugin_dir_url("/").'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)) .'css/jquery-jvectormap-1.2.2.css') ; 
	}

	/** ====================================================================================================================================================
	* Called when the content is displayed
	*
	* @param string $content the content which will be displayed
	* @param string $type the type of the article (e.g. post, page, custom_type1, etc.)
	* @param boolean $excerpt if the display is performed during the loop
	* @return string the new content
	*/
	
	function _modify_content($content, $type, $excerpt) {	
		return $content; 
	}
		
	/** ====================================================================================================================================================
	* Add a button in the TinyMCE Editor
	*
	* To add a new button, copy the commented lines a plurality of times (and uncomment them)
	* 
	* @return array of buttons
	*/
	
	function add_tinymce_buttons() {
		$buttons = array() ; 
		//$buttons[] = array(__('title', $this->pluginID), '[tag]', '[/tag]', plugin_dir_url("/").'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)).'img/img_button.png') ; 
		return $buttons ; 
	}
	
	/**====================================================================================================================================================
	* Function to instantiate the class and make it a singleton
	* This function is not supposed to be modified or called (the only call is declared at the end of this file)
	*
	* @return void
	*/
	
	public static function getInstance() {
		if ( !self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	/** ====================================================================================================================================================
	* Define the default option values of the plugin
	* This function is called when the $this->get_param function do not find any value fo the given option
	* Please note that the default return value will define the type of input form: if the default return value is a: 
	* 	- string, the input form will be an input text
	*	- integer, the input form will be an input text accepting only integer
	*	- string beggining with a '*', the input form will be a textarea
	* 	- boolean, the input form will be a checkbox 
	* 
	* @param string $option the name of the option
	* @return variant of the option
	*/
	
	public function get_default_option($option) {
		switch ($option) {
			// Alternative default return values (Please modify)
			case 'honey_key' 		: return "" 		; break ; 
			case 'threat_scrore' 		: return 25 		; break ; 
			
			case 'htaccess' 		: return 0 		; break ; 
			
			case 'geolocate_key'		: return "" ; break ; 
			
		}
		return null ;
	}
	/** ====================================================================================================================================================
	* The admin configuration page
	* This function will be called when you select the plugin in the admin backend 
	*
	* @return void
	*/
	
	public function testIfBlocked() {
		global $wpdb ; 

		$hostname = $_SERVER['REMOTE_ADDR'];
		if (!preg_match("/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/iU", $hostname)) {
			return ; 
		}
		
		$id = $wpdb->get_var("SELECT id FROM ".$this->table_name." WHERE ip='".$hostname."' LIMIT 1") ; 
		
		if ($id!=null){
			// We update 
			$wpdb->query("UPDATE ".$this->table_name." SET count=count+1, time='".date_i18n('Y-m-d H:i:s')."' WHERE id='".$id."'") ; 
			
			// We update the htaccess if needed
			if ($this->get_param('htaccess')>0) {
				$this->updateHtAccess() ; 
			}
			
			// we block
			header('HTTP/1.0 403 Forbidden');
			echo "Go away spammer ! " ; 
			die() ; 
		}

		$result = $this->query($hostname) ; 
		if (isset($result['threat_score'])) {
			if ($result['threat_score']>=$this->get_param('threat_score')) {
				// We create a new entry
				$wpdb->query("INSERT INTO ".$this->table_name." SET ip='".$hostname."', time='".date_i18n('Y-m-d H:i:s')."', count='1', reason='".addslashes(serialize($result))."'".$this->geolocate()) ; 
				
				// We update the htaccess if needed
				if ($this->get_param('htaccess')>0) {
					$this->updateHtAccess() ; 
				}
				
				// We block
		 		header('HTTP/1.0 403 Forbidden');
				echo "Go away spammer ! " ; 
				die() ; 					
			}
		}
	}

	/** ====================================================================================================================================================
	* The admin configuration page
	* This function will be called when you select the plugin in the admin backend 
	*
	* @return void
	*/
	
	public function blockIP($reason="") {
		global $wpdb ; 

		$hostname = $_SERVER['REMOTE_ADDR'];
		if (!preg_match("/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/iU", $hostname)) {
			return ; 
		}
		
		$wpdb->query("INSERT INTO ".$this->table_name." SET ip='".$hostname."', time='".date_i18n('Y-m-d H:i:s')."', count='1', reason='".addslashes(serialize(array('categories'=>$reason)))."'".$this->geolocate()) ; 
		
		// We update the htaccess if needed
		if ($this->get_param('htaccess')>0) {
			$this->updateHtAccess() ; 
		}
		
	}	
	
	/**
	* Update
	*/
	function updateHtAccess() {
		global $wpdb ; 
		$ips = $wpdb->get_results("SELECT ip FROM ".$this->table_name." ORDER BY time DESC LIMIT ".$this->get_param('htaccess')) ; 
		$to_be_added_htaccess = "\n# AutomaticBanIP-Start\n" ; 
		foreach ($ips as $i){
			$to_be_added_htaccess .= "Deny from ".$i->ip."\n" ; 
		}
		$to_be_added_htaccess .= "# AutomaticBanIP-End\n" ; 

	
		if (is_file(ABSPATH.".htaccess")) {
			$old_content = @file_get_contents(ABSPATH.".htaccess") ; 
			// remove old entries
			$old_content = preg_replace("/\n# AutomaticBanIP-Start(.*)# AutomaticBanIP-End\n/iUs", "", $old_content) ;
			$old_content = explode("# BEGIN WordPress", $old_content, 2) ; 
			if (count($old_content)!=1) {
				$new_content = $old_content[0] ;
				$new_content .= $to_be_added_htaccess ;
				$new_content .= "# BEGIN WordPress" ;
				$new_content .= $old_content[1] ;
			} else {
				$old_content = explode("<IfModule mod_rewrite.c>", $old_content[0], 2) ; 
				if (count($old_content)!=1) {
					$new_content = $old_content[0] ;
					$new_content .= $to_be_added_htaccess ;
					$new_content .= "<IfModule mod_rewrite.c>" ;
					$new_content .= $old_content[1] ;
				} else {						
					$new_content = $old_content[0] ;
					$new_content .= $to_be_added_htaccess ;
				}
			}
			@file_put_contents(ABSPATH.".htaccess", $new_content) ; 
		} else {
			@file_put_contents(ABSPATH.".htaccess", $to_be_added_htaccess) ; 
		}
	}

	/**
	* Query
	*
	* Performs a DNS lookup to obtain information about the IP address.
	*
	* @access public
	* @param string $ip_address IPv4 address to check
	* @return array results from query
	*/
	function query($ip_address) {
		
		if (trim($this->get_param("honey_key"))!=""){
			// Validates the IP format
			if (preg_match("/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/iU", $ip_address)) {
				// Flips the script, err, IP address
				$octets = explode('.', $ip_address);
				krsort($octets);
				$reversed_ip = implode('.', $octets);
				// Performs the query
				$results = @dns_get_record($this->get_param("honey_key") . '.' . $reversed_ip . '.dnsbl.httpbl.org', DNS_A);
				// Processes the results
				if (isset($results[0]['ip'])) {
					$results = explode('.', $results[0]['ip']);
					if ($results[0] == 127) {
						$results = array(
							'last_activity' => $results[1],
							'threat_score' => $results[2],
							'categories' => $results[3],
						);
						// Creates an array of categories
						switch ($results['categories']){
							case 0:
								$categories = array('Search Engine');
								break;
							case 1:
								$categories = array('Suspicious');
								break;
							case 2:
								$categories = array('Harvester');
								break;
							case 3:
								$categories = array('Suspicious', 'Harvester');
								break;
							case 4:
								$categories = array('Comment Spammer');
								break;
							case 5:
								$categories = array('Suspicious', 'Comment Spammer');
								break;
							case 6:
								$categories = array('Harvester', 'Comment Spammer');
								break;
							case 7:
								$categories = array('Suspicious', 'Harvester', 'Comment Spammer');
								break;
							default:
								$categories = array('Reserved for Future Use');
								break;
						}
						$results['categories'] = $categories;
						return $results;
					}
				}
			} else {
				return array('error' => 'Invalid IP address.');
			}
		}
		return false;
	}
	
	/** ====================================================================================================================================================
	* The admin configuration page
	* This function will be called when you select the plugin in the admin backend 
	*
	* @return void
	*/
	
	public function configuration_page() {
		global $wpdb;
		global $blog_id ; 
		
		SLFramework_Debug::log(get_class(), "Print the configuration page." , 4) ; 

		?>
		<div class="plugin-titleSL">
			<h2><?php echo $this->pluginName ?></h2>
		</div>
		<div class="plugin-contentSL">			
			<?php
			//===============================================================================================
			// After this comment, you may modify whatever you want
			
			// We check rights
			$this->check_folder_rights( array(array(WP_CONTENT_DIR."/sedlex/test/", "rwx")) ) ;
			
			$tabs = new SLFramework_Tabs() ; 
			
			ob_start() ; 
				if ($this->get_param('geolocate_key')!="") {
					$box = new SLFramework_Box (sprintf(__("Geolocalisation of Banned Spammers", $this->pluginID), $this->get_param('local_keep_detailed_info')), "<div id='geolocate_show_world_spammer' style='margin:0px auto;width:800px;height:500px;'></div>") ; 
					echo $box->flush() ; 
				}
				
				$maxnb = 20 ; 
				$table = new SLFramework_Table(0, $maxnb, true, true) ; 
				
				// on construit le filtre pour la requete
				$filter = explode(" ", $table->current_filter()) ; 
																
				$count = $wpdb->get_var("SELECT count(*) FROM ".$this->table_name." WHERE ip like '%".str_replace("'","",$table->current_filter())."%' OR reason like '%".str_replace("'","",$table->current_filter())."%' OR time like '%".str_replace("'","",$table->current_filter())."%' OR geolocate like '%".str_replace("'","",$table->current_filter())."%'") ;
				$table->set_nb_all_Items($count) ; 
				
				$table->title(array(__('Blocked IP', $this->pluginID), __('Reason for the blocking', $this->pluginID), __('Geolocation', $this->pluginID), __('Date', $this->pluginID)) ) ; 

				// We order the posts page according to the choice of the user
				$order = " ORDER BY " ; 
				if ($table->current_ordercolumn()==1) {
					$order .= "ip" ;  
				} elseif ($table->current_ordercolumn()==2) {
					$order .= "reason" ;  
				} elseif ($table->current_ordercolumn()==3) {
					$order .= "geolocate" ;  
				} else { 
					$order .= "time" ;  
				}				
				if ($table->current_orderdir()=="DESC") {
					$order .= " DESC" ;  
				} else { 
					$order .= " ASC" ;  
				}
				
				$limit = "" ; 
				if ($count>$maxnb) {
					$limit =  " LIMIT ".(($table->current_page()-1)*$maxnb).",".$maxnb ; 
				}
				
				$results = $wpdb->get_results("SELECT ip, reason, geolocate, time FROM ".$this->table_name." WHERE ip like '%".str_replace("'","",$table->current_filter())."%' OR reason like '%".str_replace("'","",$table->current_filter())."%' OR time like '%".str_replace("'","",$table->current_filter())."%' OR geolocate like '%".str_replace("'","",$table->current_filter())."%'".$order.$limit) ; 
										
				$ligne=0 ; 
				foreach ($results as $r) {
					$ligne++ ; 
					
					$cel1 = new adminCell("<p><b>".$r->ip."</p>") ; 
					ob_start() ; 
						$reason = @unserialize($r->reason) ; 
						if (is_array($reason)) {
							$reason2 = "" ; 
							foreach($reason as $k=>$rea) {
								if (is_array($rea)) {
									$rea = implode(" - ", $rea) ; 
								} 
								$reason2 .= "<p>$k: $rea</p>" ; 
							}
							$reason = $reason2 ; 
						} else {
							$reason = "<p>".$r->reason."</p>" ; 
						}
						echo $reason ; 
					$cel2 = new adminCell(ob_get_clean()) ; 
					
					$geo = @unserialize($r->geolocate) ; 
					if (is_array($geo)) {
						$geolocate = $geo['countryName'] ; 
					} else {
						$geolocate = $r->geolocate ; 
					}
					$cel3 = new adminCell("<p>".$geolocate."</p>") ; 				
					$cel4 = new adminCell("<p>".$r->time."</p>") ; 				
				
					$table->add_line(array($cel1, $cel2, $cel3, $cel4), $ligne) ; 
				}
				echo $table->flush() ; 
				
				
				
				
			$tabs->add_tab(__('Ban IP',  $this->pluginID), ob_get_clean()) ; 	

			ob_start() ; 
				$params = new SLFramework_Parameters($this, "tab-parameters") ; 
				$params->add_title(__('HoneyPot Projet',  $this->pluginID)) ; 
				$params->add_param('honey_key', sprintf(__('Your API key for the %s:',  $this->pluginID), "Honey Pot Project")) ; 
				$params->add_comment(sprintf(__("Get your API key on %s",  $this->pluginID), "<a href='https://www.projecthoneypot.org/'>Honey Pot Project</a>")) ; 
				$params->add_param('threat_score', sprintf(__('The minimum threat score to block:',  $this->pluginID), "Honey Pot Project")) ; 
				$params->add_comment(sprintf(__("Default value is %s",  $this->pluginID), "<code>25</code>")) ; 
				
				$params->add_title(__('Block via .htaccess',  $this->pluginID)) ; 
				$params->add_param('htaccess', sprintf(__('Number of IP should be added to the %s file:',  $this->pluginID), "Honey Pot Project")) ; 
				$params->add_comment(sprintf(__("If this number is %s, thus no IP is added",  $this->pluginID), "<code>0</code>")) ; 
				
				$params->add_title(__('Geolocate spammer',  $this->pluginID)) ; 
				$params->add_param('geolocate_key', sprintf(__('If you want to geolocate spammer on a map, please enter you %s key:',  $this->pluginID), "<code>IPInfoDb</code>")) ; 
				
				
				if ($this->get_param('geolocate_key')==""){
					$params->add_comment(sprintf(__("You have to create your own key on %s.", $this->pluginID), "<a href='http://www.ipinfodb.com/ip_location_api.php'>IPInfoDb</a>")) ; 
				} else {
					$geo = $this->geolocate("", false) ; 
					if (is_array($geo)){
						$params->add_comment(__("You API key appears to be correct.", $this->pluginID)) ; 				
						$params->add_comment(sprintf(__("Your server appears to be located in %s (your server's IP is %s).", $this->pluginID), "<code>".ucfirst(strtolower($geo['countryName']))."</code>", "<code>".$_SERVER['REMOTE_ADDR']."</code>")) ; 				
					} else {
						$params->add_comment(__("There was a problem while contacting the server.", $this->pluginID)) ; 				
						if (is_string($geo)){
							$params->add_comment("<code>".$geo."</code>") ; 				
						}
					}
				}
				# Automatic_Ban-IP
				$params->flush() ; 
				
			$tabs->add_tab(__('Parameters',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_param.png") ; 	

			ob_start() ;
				echo "<p>".__('This plugin may block spammer and suspicious host that connect to your server.', $this->pluginID)."</p>" ;
			$howto1 = new SLFramework_Box (__("Purpose of that plugin", $this->pluginID), ob_get_clean()) ; 
			ob_start() ;
				echo "<p>".sprintf(__('Spammers may be blocked either by PHP based restrictions (i.e. Wordpress generates a 403 page for such identified users) or by Apache based restriction (using %s in %s file).', $this->pluginID), "<code>Deny from</code>", "<code>.htaccess</code>")."</p>" ;
				echo "<p>".__('The Apache restriction is far more efficient when hundreds of hosts sent you spams in few minutes.', $this->pluginID)."</p>" ;
				echo "<p>".__('Nevertheless the Apache restrictions method does not apply for all configurations.', $this->pluginID)."</p>" ;
			$howto2 = new SLFramework_Box (__("Method to block spammers", $this->pluginID), ob_get_clean()) ; 
			ob_start() ;
				echo "<p>".sprintf(__('Spammers may be detected either by using the %s database or by using the %s plugin (need to be installed and configured accordingly).', $this->pluginID), "<code>HoneyPot Project</code>", "<code>Spam Captcha</code>")."</p>" ;
			$howto3 = new SLFramework_Box (__("Method to detect spammers", $this->pluginID), ob_get_clean()) ; 
			ob_start() ;
				 echo $howto1->flush() ; 
				 echo $howto2->flush() ; 
				 echo $howto3->flush() ; 
			$tabs->add_tab(__('How To',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_how.png") ; 				

			
			$frmk = new coreSLframework() ;  
			if (((is_multisite())&&($blog_id == 1))||(!is_multisite())||($frmk->get_param('global_allow_translation_by_blogs'))) {
				ob_start() ; 
					$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
					$trans = new SLFramework_Translation($this->pluginID, $plugin) ; 
					$trans->enable_translation() ; 
				$tabs->add_tab(__('Manage translations',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_trad.png") ; 	
			}

			ob_start() ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new SLFramework_Feedback($plugin, $this->pluginID) ; 
				$trans->enable_feedback() ; 
			$tabs->add_tab(__('Give feedback',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_mail.png") ; 	
			
			ob_start() ; 
				// A list of plugin slug to be excluded
				$exlude = array('wp-pirate-search') ; 
				// Replace sedLex by your own author name
				$trans = new SLFramework_OtherPlugins("sedLex", $exlude) ; 
				$trans->list_plugins() ; 
			$tabs->add_tab(__('Other plugins',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_plug.png") ; 	
			
			echo $tabs->flush() ; 
			
			
			// Before this comment, you may modify whatever you want
			//===============================================================================================
			?>
			<?php echo $this->signature ; ?>
		</div>
		<?php
	}
	
	/** ====================================================================================================================================================
	* Convert the on-time token into a session token
	*
	* @return string the token (or false in case of error)
	*/
	
	function geolocate($ip=null, $serialize_for_database=true) {
		$geolocate_data = "" ; 
		if (trim($this->get_param('geolocate_key'))!="") {
			// Si l'IP n'est pas forcé, cela veut dire que l'on regarde l'IP du client qui fait la requête.
			if (is_null($ip)){
				$ip = $_SERVER['REMOTE_ADDR'];
				if (!preg_match("/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/iU", $ip)) {
					if (!$serialize_for_database) {
						return "IP not correct" ; 
					}
					return "" ; 
				}
				$ip = "&ip=".$ip ; 
			} else {
				if ($ip!="") {
					$ip = "&ip=".$ip ; 
				}
			}
			if ($this->get_param('geolocate_key')!="") {
				$result_geo_xml = @file_get_contents("http://api.ipinfodb.com/v3/ip-city/?key=".trim($this->get_param('geolocate_key'))."&format=xml".$ip) ; 
								
				if ($result_geo_xml!==false){
					
					if (!preg_match("/<\?xml/ui", $result_geo_xml)){
						$result_geo_xml = "<".""."?".""."xml version='1.0'?>".$result_geo_xml ; 
					}
					
					$result_geo_xml = @simplexml_load_string($result_geo_xml); 
					if ($result_geo_xml!==false){
						
						$geolocate_data['countryCode'] = (string)$result_geo_xml->countryCode ; 
						$geolocate_data['countryName'] = (string)$result_geo_xml->countryName ; 
						
						// On le prepare pour la BDD
						if ($serialize_for_database){
							$geolocate_data = ", geolocate='".esc_sql(@serialize($geolocate_data))."'" ; 
						}
					} else {
						if (!$serialize_for_database){
							$error = error_get_last();
							return $error['message'] ; 
						}
					}
				} else {
					if (!$serialize_for_database){
						$error = error_get_last();
						return $error['message'] ; 
					}
				}
			}
		}
		return $geolocate_data ; 
	}
}

$automatic_ban_ip = automatic_ban_ip::getInstance();

?>