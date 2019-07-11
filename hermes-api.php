<?php
/**
  * @author Scott Johnston
  * @license https://www.gnu.org/licenses/gpl-3.0.html
  * @package Hermes
  * @version 1.0.0
 */

class HermesAPI extends WP_REST_Controller{
	
	public function __construct(){}
	
	public function register_routes() {			
		$namespace = 'hermes/v1';	
		register_rest_route( $namespace, '/conversation/list/(?P<id>\d+)', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( $this, 'jsonConversationList'),
		));		
		register_rest_route( $namespace, '/message/create', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'jsonMessageCreate'),			
		));
		register_rest_route( $namespace, '/message/list', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'jsonMessageList'),
		));	
		register_rest_route( $namespace, '/ping', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( $this, 'jsonPing'),			
		));
		register_rest_route( $namespace, '/relationship/create', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'jsonRelationshipCreate'),			
		));
		register_rest_route( $namespace, '/relationship/list/(?P<id>\d+)', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( $this, 'jsonRelationshipList'),
		));
	}		

	public function jsonConversationList(WP_REST_Request $request  ) {	
	
		//Check if student get call is enabled	
		$message_enable = !empty(get_option('json_enable')) ? get_option('json_enable') : true; 

		//User Id
		$id =  filter_var ($request['id'], FILTER_SANITIZE_NUMBER_INT) ;		
		
		//Conversations
		global $wpdb;
		$selectConversations = "SELECT * FROM ".$wpdb->base_prefix."messages".
		" WHERE receiverId = ".$id." OR senderId = ".$id. 
		" GROUP BY (receiverId + senderId)";
		$conversations = $wpdb->get_results($selectConversations);	

		$templates = array();		

		//Get user profiles
		for ($i=0; $i < sizeof($conversations); $i++ ) {

			$template = new stdClass();

			$otherId = 0;
			if($conversations[$i]->senderId == $id){
				$otherId = $conversations[$i]->receiverId;
			} else {
				$otherId = $conversations[$i]->senderId;
			}

			//get custom_field_avatar
			$custom_field_avatar_post_id = get_user_meta($otherId, 'custom_field_avatar', true) ;
			$custom_field_avatar_post = get_post($custom_field_avatar_post_id) ;
			$template->avatar = $this->urlToUri($custom_field_avatar_post->guid); 	

			//get gravatar if no custom_field_avatar
			if ($custom_field_avatar_post_id <= 0){
				$avatar_url = get_avatar_url($otherId, 128);
				$template->avatar =$this->urlToUri($avatar_url);
			}

			//Get name 			
			$template->id = $otherId;
			$template->name = get_user_meta($otherId, "first_name", true) + " " + get_user_meta($otherId, "last_name", true);
			$template->alias = get_userdata($otherId)->display_name;				

			//Add user to object
			$template->message = $conversations[$i];
			array_push( $templates, $template); 
		}

		header('Content-Type: application/json');
		return new WP_REST_Response($templates, 200 );
	}	

	public function jsonMessageCreate( WP_REST_Request $request ) {	

		//Check if teacher get call is enabled	
		$message_enable = !empty(get_option('json_enable')) ? get_option('json_enable') : true; 

		//Parameters
		$receiverId = filter_var ( $request->get_param( 'receiverId' ) , FILTER_SANITIZE_NUMBER_INT);
		$senderId = filter_var ( $request->get_param( 'senderId' ) , FILTER_SANITIZE_NUMBER_INT);
		$text = filter_var ( $request->get_param( 'text' ) , FILTER_SANITIZE_EMAIL);
			
		if($receiverId && $senderId && $text){
			//Insert message
			global $wpdb;
			$wpdb->insert( $wpdb->base_prefix.'messages', 
				array( 	
					'senderId' => $senderId,	
					'receiverId' => $receiverId,									
					'text' =>  $text,	
				) 
			);	

			//Build response		
			$response = new stdClass();
			$response->type = 'success';
			$response->message = 'HermesAPI create message success'; 

			header('Accept: application/json'); 
			header('Accept-Charset: utf-8');
			header('Access-Control-Allow-Origin: *');
			header('Content-Type: application/json');	
			
			return new WP_REST_Response($response, 200 );
		} else {
			$response = new stdClass();
			$response->type = 'error';
			$response->message = 'HermesAPI create message error'; 
			
			header('Content-Type: application/json');
			return new WP_REST_Response( $response, 400 );
		}
		
	}	

	public function jsonMessageList(WP_REST_Request $request  ) {	
	
		//Check if student get call is enabled	
		$message_enable = !empty(get_option('json_enable')) ? get_option('json_enable') : true; 

		//User Id
		$senderId =  filter_var ($request['senderId'], FILTER_SANITIZE_NUMBER_INT) ;	
		$receiverId =  filter_var ($request['receiverId'], FILTER_SANITIZE_NUMBER_INT) ;			
		
		//Messages
		global $wpdb;
		$selectMessages = "SELECT * FROM ".$wpdb->base_prefix."messages". 
		" WHERE (receiverId = ".$receiverId." AND senderId = ".$senderId.")".
		" OR (receiverId = ".$senderId." AND senderId = ".$receiverId.")".
		" ORDER BY id DESC";

		$messages = $wpdb->get_results($selectMessages);	

		header('Content-Type: application/json');
		return new WP_REST_Response($messages, 200 );
	}	

	public function jsonPing(WP_REST_Request $request ) {	

		$response = new stdClass();
		$response->type = 'Success';
		$response->message = 'HermesAPI ping success'; 
		
		header('Content-Type: application/json');
		return new WP_REST_Response( $response, 200 );
	}

	public function jsonRelationshipCreate( WP_REST_Request $request ) {

		//Check if teacher get call is enabled	
		$relationship_enable = !empty(get_option('json_enable')) ? get_option('json_enable') : true; 

		//parameters
		$receiverId = filter_var ( $request->get_param( 'receiverId' ) , FILTER_SANITIZE_NUMBER_INT);
		$senderId = filter_var ( $request->get_param( 'senderId' ) , FILTER_SANITIZE_NUMBER_INT);		
		$type = filter_var ( $request->get_param( 'type' ) , FILTER_SANITIZE_EMAIL);		
		
		//Insert appointment
		global $wpdb;
		$wpdb->insert( $wpdb->base_prefix.'relationship', 
			array( 		
				'receiverId' => $receiverId,					
				'senderId' => $senderId,
				'type' =>  $type,				
			) 
		);	

		//Build response
		header('Content-Type: application/json');
		$response = new stdClass();
		$response->type = 'jsonRelationship';
		$response->message = 'Success'; 

		return new WP_REST_Response($response, 200 );
	}	

	public function jsonRelationshipList(WP_REST_Request $request  ) {	
	
		//Check if student get call is enabled	
		$relationship_enable = !empty(get_option('json_enable')) ? get_option('json_enable') : true; 
		
		//User Id
		$id =  filter_var ($request['id'], FILTER_SANITIZE_NUMBER_INT) ;	

		//Messages
		global $wpdb;
		$selectRelationships = "SELECT * FROM ".$wpdb->base_prefix."relationships WHERE receiverId = ".$$id." OR senderId = ".$id;
		$relationships = $wpdb->get_results($selectRelationships);			

		header('Content-Type: application/json');
		return new WP_REST_Response($relationships, 200 );
	}

	public function urlToUri($url){		
		return 'data:image/png;base64,'.base64_encode(file_get_contents($url));	
	}	
}

function prefix_register_hermes_routes(){
	$controller = new HermesAPI();
	$controller->register_routes();
	
	/*remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
	add_filter( 'rest_pre_serve_request', function( $value ) {	
		//$origin = get_http_origin();
		//$my_sites = array( 'http://localhost:8000/', );	
		//if ( in_array( $origin, $my_sites ) ) {
		//	header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
		//} else {
		//	header( 'Access-Control-Allow-Origin: ' . esc_url_raw( site_url() ) );
		//}
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
		header( 'Access-Control-Allow-Credentials: true' );
		header( 'Access-Control-Allow-Headers: Authorization, Origin, X-Requested-With, Content-Type, Accept' );
		//header('Content-Type: application/json');
		return $value;
		
	});*/
}
add_action('rest_api_init', 'prefix_register_hermes_routes', 15);
?>