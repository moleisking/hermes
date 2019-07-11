<?php
 /**
  * @author Scott Johnston
  * @license https://www.gnu.org/licenses/gpl-3.0.html
  * @package Hermes
  * @version 1.0.0
 */
 
//defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );
class HermesShortcode{	

	public function __construct(){		
		add_action('init', array($this,'registerHermesShortcodes')); 				
		add_action('wp_enqueue_scripts', array($this,'__script_and_style'));
		add_action('wp_ajax_post_chat_read', array($this,'post_chat_read'));
		add_action('wp_ajax_post_chat_send', array($this,'post_chat_send'));
		add_action('wp_ajax_post_email', array($this,'post_email'));		
		add_action('wp_ajax_post_enemy', array($this,'post_enemy'));	
		add_action('wp_ajax_post_friend', array($this,'post_friend'));			
	}

	public function __script_and_style(){
		wp_register_script('hermesScript', plugins_url( '/js/hermes.js', __FILE__ ), array('jquery','jquery-form'), '1.0',	true);
		wp_enqueue_script('hermesScript');
		wp_localize_script('hermesScript','ajax_object',array( 'ajax_url' => admin_url("admin-ajax.php")));

		wp_register_style('hermesStyle', plugins_url( '/css/hermes.css', __FILE__ ), array(), '1.0',	'all');
		wp_enqueue_style('hermesStyle');

		wp_register_style('w3Style', plugins_url( '/css/w3.css', __FILE__ ), array(), '1.0',	'all');
		wp_enqueue_style('w3Style');
	}

	public function registerHermesShortcodes( $atts ) {	
		add_shortcode( 'hermes_chat', array($this ,'shortcode_chat' ) );	
		add_shortcode( 'hermes_email', array($this ,'shortcode_email' ) );
		add_shortcode( 'hermes_enemy', array($this ,'shortcode_enemy' ) );	
		add_shortcode( 'hermes_friend', array($this ,'shortcode_friend' ) );
		add_shortcode( 'hermes_help', array($this ,'shortcode_help' ) );
		add_shortcode( 'hermes_inbox', array($this ,'shortcode_inbox' ) );
		add_shortcode( 'hermes_unread', array($this ,'shortcode_unread' ) );
		add_shortcode( 'hermes_skype', array($this ,'shortcode_skype' ) );
		add_shortcode( 'hermes_wechat', array($this ,'shortcode_wechat' ) );
		add_shortcode( 'hermes_whatsapp', array($this ,'shortcode_whatsapp' ) );					
	}	

	public function post_chat_read(){
		if(is_user_logged_in() && isset($_POST['toId'])){

			global $wpdb;
			$wpdb->update( $wpdb->base_prefix.'messages', 
				array( 
					'state' =>  'read',
				),
				array( 	
					'senderId' => filter_var ($_POST['toId'], FILTER_SANITIZE_NUMBER_INT),				
					'receiverId' => get_current_user_id(), 			
				) 
			);				
		} 	
	}

	public function post_chat_send(){		
		if (is_user_logged_in() && isset($_POST['toId']) && isset($_POST['txtChat']) && 
			!$this->isSpam() && !$this->areEnemies($_POST['toId']) ) {	

			$toId =  filter_var ($_POST['toId'], FILTER_SANITIZE_NUMBER_INT);	
			$text = filter_var ($_POST['txtChat'], FILTER_SANITIZE_SPECIAL_CHARS); 	

			$this->insertMessage( $toId, $text, 'chat');
			
			$email_notification = !empty(get_option('email_notification')) ? get_option('email_notification') : '0'; 
			if($email_notification == '1'){
				//Email notifications 
				$toEmail = get_userdata($toId)->user_email;
				$from = wp_get_current_user(); // Depreciated:get_currentuserinfo();
				$fromEmail = $from->user_email; //wp_get_current_user()->user_email;
				$headers = "\r\n"." From: ".$fromAlias." <'.$fromEmail.'>" ;
				$subject = "Message from ".$fromAlias." - ".get_current_site();
				$text = "Read message at ".get_site_url();
				wp_mail($toEmail, $subject, $text."<br>".$headers, $headers );			
			} 			
		} 
	}

	public function post_email(){
		if (is_user_logged_in() && isset($_POST['toId']) && isset($_POST['txtEmail']) &&
			isset($_POST['subject']) && isset($_POST['uniqueId']) &&
			!$this->isSpam() && (!$this->areEnemies($_POST['toId']))) {					
		
			$uniqueId =  filter_var ($_POST['uniqueId'], FILTER_SANITIZE_NUMBER_INT);
			$toId =  filter_var ($_POST['toId'], FILTER_SANITIZE_NUMBER_INT);						
			$text = filter_var ($_POST['txtEmail'], FILTER_SANITIZE_SPECIAL_CHARS); 					
			$subject =  filter_var ($_POST['subject'], FILTER_SANITIZE_SPECIAL_CHARS);
			$toEmail = get_userdata($toId)->user_email; 
			$from = wp_get_current_user(); // Depreciated:get_currentuserinfo();
			$fromEmail = $from->user_email; //wp_get_current_user()->user_email;
			$fromAlias = $from->user_login;
			$headers = "\r\n"." From: ".$fromAlias." <'.$fromEmail.'>" ;

			wp_mail($toEmail, $subject, $text."<br>".$headers, $headers );

			$this->insertMessage($toId, $text, 'email');
		}
	}

	public function post_friend(){
		if (is_user_logged_in() && isset($_POST['toId'])) {	
			$this->insertRelationship($_POST['toId'], 'friend');
		} 
	}

	public function post_enemy(){
		if (is_user_logged_in() && isset($_POST['toId'])) {	
			$this->insertRelationship($_POST['toId'], 'enemy');	
		} 
	}

	public function post_skype(){
		if (is_user_logged_in() && isset($_POST['btnSkype']) && isset($_POST['toId']) && (!$this->isSpam()) && (!$this->areEnemies($_POST['toId'])) ) {			
			
			$toId =  filter_var ($_POST['toId'], FILTER_SANITIZE_NUMBER_INT);			
			$skypeField = !empty(get_option('skype_field_name')) ? get_option('skype_field_name') : 'custom_field_skype'; 
			$toUserSkype = get_userdata($toId)->$skypeField;

			$this->insertMessage( $toId, "Skype button click", 'skype');	

			echo "<script>window.open('skype:".$toUserSkype."?chat','_blank')</script>";			
		} 
	}

	public function post_wechat(){
		if  (is_user_logged_in() && isset($_POST['btnWeChat']) && isset($_POST['toId']) && !$this->isSpam() && (!$this->areEnemies($_POST['toId'])) ) {					
		
			$toId =  filter_var ($_POST['toId'], FILTER_SANITIZE_NUMBER_INT);
			$weChatField = !empty(get_option('wechat_field_name')) ? get_option('wechat_field_name') : 'custom_field_wechat'; 
			$toWeChat = get_userdata($toId)->$weChatField;

			$this->insertMessage( $toId, "WeChat button click", 'wechat');	

			echo "<script>window.open('weixin://dl/chat?".$toWeChat."')</script>";	
		}
	}

	public function post_whatsapp(){ 
		if  (is_user_logged_in() && isset($_POST['btnWhatsApp']) && isset($_POST['toId']) && (!$this->isSpam()) && (!$this->areEnemies($_POST['toId'])) ) {
		
			$toId =  filter_var ($_POST['toId'], FILTER_SANITIZE_NUMBER_INT);			
			$whatsappField = !empty(get_option('whatsapp_field_name')) ? get_option('whatsapp_field_name') : 'custom_field_whatsapp'; 	
			$toWhatsApp = get_userdata($toId)->$whatsappField;	
					
			$this->insertMessage( $toId, "WhatsApp button clicked", 'whatsapp');						
					
			if(preg_match('/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/', $toWhatsApp)){
				echo "<script>window.open('https://chat.whatsapp.com/invite/".$toWhatsApp."','_blank')</script>";
			} else {
				echo "<script>window.open('https://web.whatsapp.com/send?phone=".$toWhatsApp."','_blank')</script>";		
			}			
		} 
	}	
	
	public function shortcode_chat( $atts ) {	
		//Get look and feel options
		$icon_width = !empty(get_option('icon_size')) ? get_option('icon_size') : '48rem'; 
		$textarea_rows = !empty(get_option('textarea_rows')) ? get_option('textarea_rows') : 3; 
		$textarea_length = !empty(get_option('textarea_length')) ? get_option('textarea_length') : 150 ; 

		//Get message limit
		$max_message_count = !empty(get_option('max_message_count')) ? get_option('max_message_count') : 10; 

		//Shortcode  parameters are lowercase only
		$atts = shortcode_atts( array(
			'to' => null
		), $atts, 'hermes_chat' );
		$toId = filter_var ($atts['to'], FILTER_SANITIZE_NUMBER_INT); 
						
		//Generate uniqueId id for javascript calls
		$uniqueId = uniqid("MsgPrefix");

		//Build html with action button
		if ( is_user_logged_in()){	
			global $wpdb;
			$select = "SELECT * FROM ".$wpdb->base_prefix."messages". 
				" WHERE (receiverId =".$toId." AND senderId =".get_current_user_id().")". 
                " OR (receiverId =".get_current_user_id()." AND senderId =".$toId.")".
                " ORDER BY ts ASC";		
			$results = $wpdb->get_results($select);
		
			//Build html with action button
			$html = "<div class='w3-container'>".				
				//"<button id='btnModalChat' type='submit' name='btnModalChat' onclick=".'"'."postRead('".home_url()."','".$uniqueId."')".'"'.">".
				//Show or Hide Modal
				"<button id='btnChat' name='btnChat' type='submit' form='frmChatRead".$uniqueId."' onclick=".'"'."showDiv('".$uniqueId."')".'"'.">".	
					"<img src='".plugins_url( '/images/chat.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."'/>".	
				"</button>".
				//"<form id='frmChatRead".$uniqueId."' name='frmChatRead".$uniqueId."' action='".esc_url( $_SERVER['REQUEST_URI'] )."' method='post'>".
				"<form id='frmChatRead".$uniqueId."' name='frmChatRead".$uniqueId."' class='frmChatRead' method='post' action='".admin_url('admin-ajax.php')."'>".
					"<input type='hidden' id='uniqueId' name='uniqueId' value='".$uniqueId."'>".
					"<input type='hidden' id='toId' name='toId' value='".$toId."'>".	
					//Note current user id is stored in post for ajax function calls
					"<input type='hidden' id='fromId' name='fromId' value='".get_current_user_id()."'>".	
					"<input type='hidden' name='action' value='post_chat_read'>".	
				"</form>".				
				//Modal
				"<div id='".$uniqueId."' class='w3-modal' style='display:none'>".
					"<div class='w3-modal-content'>".
						"<div class='w3-container'>";	
							if(sizeof($results) > 0){
								$html .="<br><div id='history' name='history' class='history w3-container' style='overflow-y:scroll; height:20%;' >";
								foreach ($results as $result) {
									if ($result->senderId == get_current_user_id()){
										$html .="<div class='w3-tag w3-round w3-blue w3-right'>".$result->text."</div><br>";
									} else {
										$html .="<div class='w3-tag w3-round w3-green'>".$result->text."</div><br>";
									}	
								}
								$html .="</div>";
							} else {
								$html .="No History";
							}
							
							$html .="<form id='frmChatSend".$uniqueId."' name='frmChatSend".$uniqueId."' class='frmChatSend' method='post' action='".admin_url('admin-ajax.php')."'>".
								"<input type='hidden' id='uniqueId' name='uniqueId' value='".$uniqueId."'>".
								"<input type='hidden' id='toId' name='toId' value='".$toId."'>".
								"<input type='hidden' name='action' value='post_chat_send'>".							
								"<br><textarea id='txtChat' name='txtChat' rows='".$textarea_rows."' maxlength='".$textarea_length."'></textarea>".	
							"</form>".			
							//Error box
							"<div id='txtError' name='txtError' class='w3-row info'>".
								"Daily message limit ".$this->countSpam()."/".$max_message_count.
							"</div>".
							"<div class='w3-row'>".
								//Post message button
								"<div class='w3-half w3-container'>".
									"<button id='btnChatSend' name='btnChatSend' type='submit' form='frmChatSend".$uniqueId."' class='w3-button' onclick=".'"'."hideDiv('".$uniqueId."')".'"'.">".
										"<img src='".plugins_url( '/images/send.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."'/>".	
									"</button>".
								"</div>".
								//Hide modal button	
								"<div class='w3-half w3-container'>".
									"<button id='btnCancel' onclick=".'"'."hideDiv('".$uniqueId."')".'"'.">".
										"<img src='".plugins_url( '/images/cancel.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."'/>".	
									"</button>".
								"</div>".	
							"</div>".
						"</div>".
					"</div>".
				"</div>".		
			"</div>";

			return $html;	

		} else {			
			$html = "<div class='w3-container'>".		
				"<button class='btnEmpty'>".	
					"<img src='".plugins_url( '/images/email.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."' />".	
				"</button>".
			"</div>";

			return $html;	
		}		
	}	
	
	public function shortcode_email( $atts ) {	
		//Get look and feel options
		$icon_width = !empty(get_option('icon_size')) ? get_option('icon_size') : '48rem'; 
		$textarea_rows = !empty(get_option('textarea_rows')) ? get_option('textarea_rows') : 3; 
		$textarea_length = !empty(get_option('textarea_length')) ? get_option('textarea_length') : 150 ; 

		//Get message limit
		$max_message_count = !empty(get_option('max_message_count')) ? get_option('max_message_count') : 10; 

		//Shortcode  parameters are lowercase only
		$atts = shortcode_atts( array(
			'to' => null,	
			'subject' => null
		), $atts, 'hermes_email' );
		$toId = filter_var ($atts['to'], FILTER_SANITIZE_NUMBER_INT);  			
		$subject = filter_var ($atts['subject'], FILTER_SANITIZE_SPECIAL_CHARS); 

		//To check that field exists
		$toEmail = get_userdata($toId)->user_email; 
				
		//Generate uniqueId id for javascript calls
		$uniqueId = uniqid("MsgPrefix");	

		//Get current message count
		$msgCounter = $this->countSpam(); 

		//Build html with action button
		if ( is_user_logged_in() && $toEmail){
			$html = "<div class='w3-container'>".
				//Show or Hide Modal
				"<button id='btnEmail' name='btnEmail' onclick=".'"'."showDiv('".$uniqueId."')".'"'.">".	
					"<img src='".plugins_url( '/images/email.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."'/>".	
				"</button>".
				//Modal
				"<div id='".$uniqueId."' class='w3-modal' style='display:none'>".
					"<div class='w3-modal-content'>".
						"<div class='w3-container'>".						
							"<form id='frmEmail".$uniqueId."' method='post' class='frmEmail' action='".admin_url('admin-ajax.php')."'>".
								"<input type='hidden' id='uniqueId' name='uniqueId' value='".$uniqueId."'>".
								"<input type='hidden' id='toId' name='toId' value='".$toId."'>".									
								"<input type='hidden' id='subject' name='subject' value='".$subject."'>".	
								"<input type='hidden' name='action' value='post_email'>".							
								"<br><textarea id='txtEmail' name='txtEmail' rows='".$textarea_rows."' maxlength='".$textarea_length."'></textarea>".	
							"</form>".
							//Error box
							"<div id='txtError' name='txtError' class='w3-row info'>".
								"Daily message limit ".$msgCounter."/".$max_message_count.
							"</div>".
							"<div class='w3-row'>".
								//Post message button
								"<div class='w3-half w3-container'>".
									"<button id='btnEmailSend' name='btnEmailSend' type='submit' class='w3-button' form='frmEmail".$uniqueId."'>".
										"<img src='".plugins_url( '/images/send.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."'/>".	
									"</button>".
								"</div>".	
								//Hide modal button	
								"<div class='w3-half w3-container'>".
									"<button id='btnCancel' onclick=".'"'."hideDiv('".$uniqueId."')".'"'.">".
										"<img src='".plugins_url( '/images/cancel.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."'/>".	
									"</button>".
								"</div>".	
							"</div>".
						"</div>".
					"</div>".
				"</div>".		
			"</div>";

			return $html;	

		} else {			
			$html = "<div class='w3-container'>".		
				"<button class='btnEmpty'>".	
					"<img src='".plugins_url( '/images/email.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."' />".	
				"</button>".
			"</div>";

			return $html;	
		}		
	}	

	public function shortcode_enemy( $atts ) {		
		//Get look and feel options
		$icon_width = !empty(get_option('icon_size')) ? get_option('icon_size') : '48rem'; 
	
		//Shortcode  parameters are lowercase only	
		$atts = shortcode_atts( array(
			'to' => null
		), $atts, 'hermes_enemy' );
		$toId = filter_var ($atts['to'], FILTER_SANITIZE_NUMBER_INT);  		
			
		//Generate uniqueId id for javascript calls
		$uniqueId = uniqid("MsgPrefix");	
	
		//Build html with action button
		if ( is_user_logged_in()){				
			$html = "<div id='divEnemy' class='w3-container'>".	
				"<button id='btnEnemy' name='btnEnemy' type='submit' form='frmEnemy".$uniqueId."'>".	
					"<img src='".plugins_url( '/images/bug.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."' alt='enemy'/>".	
				"</button>".		
				"<form id='frmEnemy".$uniqueId."' name='frmEnemy' method='post' class='frmEnemy' action='".admin_url('admin-ajax.php')."'>".
					"<input type='hidden' id='uniqueId' name='uniqueId' value='".$uniqueId."'>".
					"<input type='hidden' id='toId' name='toId' value='".$toId."'>".
					"<input type='hidden' name='action' value='post_enemy'>".
				"</form>".	
			"</div>";
				
			return $html;
	
		} else {				
			$html = "<div id='divEnemy' class='w3-container'>".		
				"<button class='btnEmpty'>".	
					"<img src='".plugins_url( '/images/bug.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."' alt='enemy'/>".	
				"</button>".
			"</div>";
	
			return $html;	
		}
	}		
	
	public function shortcode_friend( $atts ) {		
		//Get look and feel options
		$icon_width = !empty(get_option('icon_size')) ? get_option('icon_size') : '48rem'; 
	
		//Shortcode  parameters are lowercase only
		$atts = shortcode_atts( array(
			'to' => null
		), $atts, 'hermes_friend' );
		$toId = filter_var ($atts['to'], FILTER_SANITIZE_NUMBER_INT);  
			
		//Generate uniqueId id for javascript calls
		$uniqueId = uniqid("MsgPrefix");	
	
		//Build html with action button
		if ( is_user_logged_in()){				
			$html = "<div id='divFriend' class='w3-container'>".	
				"<button id='btnFriend' name='btnFriend' type='submit' form='frmFriend".$uniqueId."'>".	
					"<img src='".plugins_url( '/images/support.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."' alt='friend'/>".	
				"</button>".			
				"<form id='frmFriend".$uniqueId."' name='frmFriend' method='post' class='frmFriend' action='".admin_url('admin-ajax.php')."'>".
					"<input type='hidden' id='uniqueId' name='uniqueId' value='".$uniqueId."'>".
					"<input type='hidden' id='toId' name='toId' value='".$toId."'>".
					"<input type='hidden' name='action' value='post_friend'>".
				"</form>".						
			"</div>";
				
			return $html;
	
		} else {
			$html = "<div id='divFriend' class='w3-container'>".		
				"<button class='btnEmpty'>".	
					"<img src='".plugins_url( '/images/support.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."' alt='friend'/>".	
				"</button>".
			"</div>";
	
			return $html;	
		}
	}	

	public function shortcode_help( $atts ) {
		//Get look and feel options
		$icon_width = !empty(get_option('icon_size')) ? get_option('icon_size') : '48rem'; 
		$textarea_rows = !empty(get_option('textarea_rows')) ? get_option('textarea_rows') : 3; 

		//Get field names
		$whatsappField = !empty(get_option('whatsapp_field_name')) ? get_option('whatsapp_field_name') : 'custom_field_whatsapp'; 	
		$skypeField = !empty(get_option('skype_field_name')) ? get_option('skype_field_name') : 'custom_field_skype'; 
		$weChatField = !empty(get_option('wechat_field_name')) ? get_option('wechat_field_name') : 'custom_field_wechat'; 

		//Get message limit
		$max_message_count = !empty(get_option('max_message_count')) ? get_option('max_message_count') : 10; 
			
		//Generate uniqueId id for javascript calls
		$uniqueId = uniqid("MsgPrefix");	

		//Get current message count
		$msgCounter = $this->countSpam(); 

		if ( is_user_logged_in() ) {		
			
			//Get logged user data
			$from = wp_get_current_user(); 
			$fromSkype = $from->$skypeField;
			$fromWeChat = $from->$weChatField; 
			$fromWhatsApp = $from->$whatsappField;	
		
			//Open container
			$html = "<div class='w3-container'>".
				//Show dropdown button
				"<button id='btnHelp' name='btnHelp' onclick=".'"'."showDiv('".$uniqueId."')".'"'.">".	
					"<img src='".plugins_url( '/images/question.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."'/>".	
				"</button>".
				//Dropdown area
				"<div id='".$uniqueId."' class='w3-modal' style='display:none'>".
					"<div class='w3-modal-content'>".
						"<div class='w3-container'>".					
							"<div>".
								"<h3>Test chat buttons</h3>".
								"<a href='skype:".$fromSkype."?chat' target='_blank'>Skype:".$fromSkype."</a><br>".
								"<a href='weixin://dl/chat?".$fromWeChat."' target='_blank'>WeChat:".$fromWeChat."</a><br>".
								"<a href='https://web.whatsapp.com/send?phone=".$fromWhatsApp."' target='_blank'>WhatsApp:".$fromWhatsApp."</a><br>".
							"</div>".
							//Error box
							"<div id='txtError' name='txtError' class='info'>".
								"Daily message limit ".$msgCounter."/".$max_message_count.
							"</div>".
							//Hide modal button	
							"<button id='btnCancel' onclick=".'"'."hideDiv('".$uniqueId."')".'"'.">".
								"<img src='".plugins_url( '/images/cancel.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."'/>".	
							"</button>".				
						"</div>".
					"</div>".
				"</div>".	
			"</div>";			
			
			return $html;

		} else {
			$html = "<div class='w3-container'>".
			//Show dropdown button
			"<button id='btnModal' name='btnModal' onclick=".'"'."showDiv('".$uniqueId."')".'"'.">".	
				"<img src='".plugins_url( '/images/question.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."'/>".	
			"</button>".
			//Dropdown area
			"<div id='".$uniqueId."' class='w3-modal' style='display:none'>".
				"<div class='w3-modal-content'>".
					"<div class='w3-container'>".					
						"User not logged in.".
					"</div>".
				"</div>".	
			"</div>";	

			return $html;
		}
	}	
	
	public function shortcode_inbox( $atts ) {	
		//Get look and feel options
		$icon_width = !empty(get_option('icon_size')) ? get_option('icon_size') : '48rem'; 
	
		//Shortcode parameters are lowercase only
		$atts = shortcode_atts( array(			
			'path' => null
		), $atts, 'hermes_inbox' );
		$toId = filter_var ($atts['to'], FILTER_SANITIZE_NUMBER_INT);
		$path = filter_var ($atts['path'], FILTER_SANITIZE_SPECIAL_CHARS); 			
				
		//Generate uniqueId id for javascript calls
		$uniqueId = uniqid("MsgPrefix");	

		//Get current message count
		$msgCounter = $this->countSpam();

		//Build html with action button
		if ( is_user_logged_in()){	
			global $wpdb;
			$table = $wpdb->base_prefix."messages";
			$select = "SELECT * FROM ".
			"(SELECT MAX(ts) AS ts FROM ".$table.
			" WHERE ".get_current_user_id()." IN (senderId,receiverId)".
			" GROUP BY IF (".get_current_user_id()." = senderId,receiverId,senderId)) AS latest".
			" LEFT JOIN ".$table." USING(ts)";		
			$results = $wpdb->get_results($select);
			
			
			$html .= "<div id='divConversations' class='w3-container'>";					
				if(sizeof($results) > 0){											
					foreach ($results as $result) {		
						$html .= "<div id='divConversation' class='w3-panel'>";																			
						if ( get_current_user_id() != $result->senderId){											
							$html .= "<a href=".get_home_url(null,$path.$result->senderId,null).">".
							"<button id='btnConversation' name='btnConversation'>".	
							"<div class='w3-third'>".get_avatar($result->senderId, $icon_width)."</div>".	
							"<div id='txtConversation' name='txtConversation' class='w3-twothird'>".$this->excerpt($result->text)."</div>".	
							"</button></a>";													
						} else {
							$html .= "<a href=".get_home_url(null,$path.$result->receiverId,null).">".
							"<button id='btnConversation' name='btnConversation'>".	
							"<div class='w3-third'>".get_avatar($result->receiverId, $icon_width)."</div>".
							"<div id='txtConversation' name='txtConversation' class='w3-twothird'>".$this->excerpt($result->text)."</div>".											
							"</button></a>";											
						}
						$html .="</div>";
					}	
				} else {
					$html .="<div class='w3-row'>Empty mailbox</div>";
				}		
			$html .="</div>";

			return $html;	

		} else {			
			$html = "<div class='w3-container'>".		
				"<div class='w3-row'>".	
					"<p>No Logged in</p>".	
				"</div>".
			"</div>";

			return $html;	
		}		
	}	
	
	public function shortcode_skype( $atts ) {			
		//Get look and feel options
		$icon_width = !empty(get_option('icon_size')) ? get_option('icon_size') : '48rem'; 
		$textarea_rows = !empty(get_option('textarea_rows')) ? get_option('textarea_rows') : 3; 
		$skypeField = !empty(get_option('skype_field_name')) ? get_option('skype_field_name') : 'custom_field_skype'; 

		//Shortcode  parameters are lowercase only	
		$atts = shortcode_atts( array(
			'to' => null
		), $atts, 'hermes_skype' );
		$toId = filter_var ($atts['to'], FILTER_SANITIZE_NUMBER_INT); 				

		//To check that field exists
		$toUserSkype = get_userdata($toId)->$skypeField;

		//Generate uniqueId id for javascript calls
		$uniqueId = uniqid("MsgPrefix");	

		//Build html with action button
		if ( is_user_logged_in() && $toUserSkype){	//action='".esc_url( $_SERVER['REQUEST_URI'] )."'".$uniqueId."
			$this->post_skype();	
			$html = "<div id='divMessageBox' class='w3-container'>".	
				"<button id='btnSkype' name='btnSkype' type='submit' form='frmSkype".$uniqueId."'>".	
					"<img src='".plugins_url( '/images/skype.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."'/>".	
				"</button>".	
				"<form id='frmSkype".$uniqueId."' name='frmSkype".$uniqueId."' method='post' action='".esc_url( $_SERVER['REQUEST_URI'] )."'>".
					"<input type='hidden' id='uniqueId' name='uniqueId' value='".$uniqueId."'>".
					"<input type='hidden' id='toId' name='toId' value='".$toId."'>".									
				"</form>".
			"</div>";
			
			return $html;

		} else {
			$html = "<div class='w3-container'>".		
				"<button class='btnEmpty'>".	
					"<img src='".plugins_url( '/images/skype.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."' />".	
				"</button>".
			"</div>";

			return $html;	
		}
	}	

	public function shortcode_unread(){
		global $wpdb;		
		$select = "SELECT COUNT(ts) AS score FROM ".$wpdb->base_prefix."messages".
		" WHERE receiverId=".get_current_user_id()." AND state == 'sent'";	
		$results = $wpdb->get_results($select);
		if((sizeof($results) > 0)){ 
			return $results[0]->score;
		} else {
			return 0;
		}
	}
	
	public function shortcode_wechat( $atts ) {		
		//Get look and feel options
		$icon_width = !empty(get_option('icon_size')) ? get_option('icon_size') : '48rem'; 
		$weChatField = !empty(get_option('wechat_field_name')) ? get_option('wechat_field_name') : 'custom_field_wechat'; 
					
		//Shortcode  parameters are lowercase only
		$atts = shortcode_atts( array(
			'to' => null
		), $atts, 'hermes_wechat' );
		$toId = filter_var ($atts['to'], FILTER_SANITIZE_NUMBER_INT);  

		//To check that field exists
		$toWeChat = get_userdata($toId)->$weChatField;

		//Generate uniqueId id for javascript calls
		$uniqueId = uniqid("MsgPrefix");	
		
		//Build html with action button
		if ( is_user_logged_in() && $toWeChat ){	
			$this->post_wechat();
			$html = "<div id='divMessageBox' class='w3-container'>".	
				"<button id='btnWeChat' name='btnWeChat' type='submit' form='frmWeChat".$uniqueId."'>".	
					"<img src='".plugins_url( '/images/wechat.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."'/>".	
				"</button>".	
				"<form id='frmWeChat".$uniqueId."' name='frmWeChat".$uniqueId."' method='post' action='".esc_url( $_SERVER['REQUEST_URI'] )."'>".
					"<input type='hidden' id='uniqueId' name='uniqueId' value='".$uniqueId."'>".
					"<input type='hidden' id='toId' name='toId' value='".$toId."'>".					
				"</form>".		
			"</div>";			
			
			return $html;
		} else {
			$html = "<div class='w3-container'>".		
				"<button class='btnEmpty'>".	
					"<img src='".plugins_url( '/images/wechat.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."' />".	
				"</button>".
			"</div>";

			return $html;	
		}
	}
	
	public function shortcode_whatsapp( $atts ) {			
		//Get look and feel options
		$icon_width = !empty(get_option('icon_size')) ? get_option('icon_size') : '48rem'; 		
		$whatsappField = !empty(get_option('whatsapp_field_name')) ? get_option('whatsapp_field_name') : 'custom_field_whatsapp'; 	
			
		//Shortcode  parameters are lowercase only	
		$atts = shortcode_atts( array(
			'to' => null
		), $atts, 'hermes_whatsapp' );
		$toId = filter_var ($atts['to'], FILTER_SANITIZE_NUMBER_INT); 		

		//To check that field exists
		$toWhatsApp = get_userdata($toId)->$whatsappField;		

		//Generate uniqueId id for javascript calls
		$uniqueId = uniqid("MsgPrefix");		
		
		//Build html with action button
		if ( is_user_logged_in() && $toWhatsApp ){
			$this->post_whatsapp();
			$html = "<div id='divMessageBox' class='w3-container'>".	
				"<button id='btnWhatsApp' name='btnWhatsApp' type='submit' form='frmWhatsApp".$uniqueId."'>".	
					"<img src='".plugins_url( '/images/whatsapp.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."'/>".	
				"</button>".	
				"<form id='frmWhatsApp".$uniqueId."' name='frmWhatsApp".$uniqueId."' method='post' action='".esc_url( $_SERVER['REQUEST_URI'] )."'>".
					"<input type='hidden' id='uniqueId' name='uniqueId' value='".$uniqueId."'>".
					"<input type='hidden' id='toId' name='toId' value='".$toId."'>".					
				"</form>".
			"</div>";	
		
			return $html;
		} else {
			$html = "<div class='w3-container'>".		
				"<button class='btnEmpty'>".	
					"<img src='".plugins_url( '/images/whatsapp.svg', __FILE__ )."' width='".$icon_width."' height='".$icon_width."' />".	
				"</button>".
			"</div>";

			return $html;	
		}
	}	

	private function areEnemies($receiverId){	
		global $wpdb;		
		$select = "SELECT * FROM ".$wpdb->base_prefix."relationships".
		" WHERE (senderId=".get_current_user_id()." AND receiverId=".$receiverId.") OR". 
		" (senderId=".$receiverId." AND receiverId=".get_current_user_id()." AND type='enemy')";	
		$results = $wpdb->get_results($select);

		if(sizeof($results) == 0){
			return false;
		} else if((sizeof($results) == 1) && ($results[0]->type == 'enemy')){
			return true;
		} else if((sizeof($results) == 2) && (($results[0]->type == 'enemy') || ($results[1]->type == 'enemy'))){
			return true;
		} else {
			return true;
		}
	}	

	private function error($message){
		if (sizeof($message)> 0){
			return "<div id='divError' class='w3-panel w3-red'>".$message."</div>";			
		} else {
			return "<div id='divError'></div>";	
		}		
	}

	public function countSpam(){
		global $wpdb;		
		$select = "SELECT COUNT(ts) AS score FROM ".$wpdb->base_prefix."messages".
		" WHERE senderId=".get_current_user_id()." AND ts >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";	
		$results = $wpdb->get_results($select);
		if((sizeof($results) > 0)){ 
			return $results[0]->score;
		} else {
			return 0;
		}
	}	

	function excerpt($str, $maxLength=50) {
		if(strlen($str) > $maxLength) {
			$excerpt   = substr($str, 0, $maxLength-3);
			$lastSpace = strrpos($excerpt, ' ');
			$excerpt   = substr($excerpt, 0, $lastSpace);
			$excerpt  .= '...';
		} else {
			$excerpt = $str;
		}
		
		return $excerpt;
	}

	public function insertMessage($receiverId, $text, $type){
		global $wpdb;
		$wpdb->insert( $wpdb->base_prefix.'messages', 
			array( 	
				'senderId' => get_current_user_id(),				
				'receiverId' => filter_var ($receiverId, FILTER_SANITIZE_NUMBER_INT),
				'text' =>  filter_var ($text, FILTER_SANITIZE_SPECIAL_CHARS),
				'type' =>  $type,
				'state' =>  'sent',
			) 
		);	
	}

	public function insertRelationship($receiverId, $type){
		global $wpdb;
		//Check for duplicates
		$select = "SELECT * FROM ".$wpdb->base_prefix."relationships".
		" WHERE (senderId=".get_current_user_id()." AND receiverId=".$receiverId.")";	
		$results = $wpdb->get_results($select);
		if(sizeof($results) == 0 || is_null($results)){
			//Insert new relationship
			$wpdb->insert( $wpdb->base_prefix.'relationships', 
				array( 	
					'senderId' => get_current_user_id(),				
					'receiverId' => filter_var ($receiverId, FILTER_SANITIZE_NUMBER_INT),				
					'type' =>  $type,
				) 
			);	
		} else {
			//Update relationship
			$wpdb->update( $wpdb->base_prefix.'relationships', 
				array( 	
					'senderId' => get_current_user_id(),				
					'receiverId' => filter_var ($receiverId, FILTER_SANITIZE_NUMBER_INT),				
					'type' =>  $type,
				) , 
				array('id'=> $results[0]->id )
			);	
		}
	}

	private function isSpam(){
		$max_message_count = !empty(get_option('max_message_count')) ? get_option('max_message_count') : 10; 

		if($this->countSpam() <= $max_message_count){	
			return false;
		} else {					
			return true;
		}
	}	
}	

$hermesShortcode = new HermesShortcode();
?>