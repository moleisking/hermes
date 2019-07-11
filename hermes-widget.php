<?php
 /**
  * @author Scott Johnston
  * @license https://www.gnu.org/licenses/gpl-3.0.html
  * @package Hermes
  * @version 1.0.0
 */
 
defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

class HermesWidget extends WP_Widget{

	public function __construct(){
		parent::__construct(
			'HermesWidget',
			'Hermes Widget', 
			array('description' => 'A shortcode and widget to send messages to a user by id.'));	
	}	

	public function form( $instance ) {	
		isset( $instance[ 'toId' ] ) ? $toId = $instance[ 'toId' ] : $toId = 1;	
		isset( $instance[ 'type' ] ) ? $type = $instance[ 'type' ] : $type = 'skype';
		isset( $instance[ 'subject' ] ) ? $subject = $instance[ 'subject' ] : $subject = 'subject';
		
		//echo "form:".$type.",".$toId;
		?>
		<div>
			<label for="<?php echo $this->get_field_id( 'toId' ); ?>"><?php _e( 'ToUserID' ); ?></label> <br>
			<input id="<?php echo $this->get_field_id( 'toId' ); ?>" name="<?php echo $this->get_field_name( 'toId' ); ?>" type="text" value="<?php echo esc_attr( $toId ); ?>"  width ="100%" /><br>	
			<label>Type</label><br>                
			<select id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name( 'type' ); ?>"  value = '<?php  echo esc_attr($type); ?>' width ="100%">
				<option value='email' <?php if($type == 'email'): ?> selected='selected'<?php endif; ?>>Email</option>
				<option value='skype' <?php if($type == 'skype'): ?> selected='selected'<?php endif; ?>>Skype</option>
				<option value='whatsapp' <?php if($type == 'whatsapp'): ?> selected='selected'<?php endif; ?>>WhatsApp</option>
				<option value='wechat' <?php if($type == 'wechat'): ?> selected='selected'<?php endif; ?>>WeChat</option>                  
			</select><br>
			<label for="<?php echo $this->get_field_id( 'subject' ); ?>"><?php _e( 'Subject' ); ?></label> <br>
			<input id="<?php echo $this->get_field_id( 'subject' ); ?>" name="<?php echo $this->get_field_name( 'subject' ); ?>" type="text" value="<?php echo esc_attr( $subject ); ?>"  width ="100%" /><br>
		</div>
		<?php 
	}

	public function update( $new_instance, $old_instance ) {
        $instance = array();
		$instance['toId'] = strip_tags( $new_instance['toId'] );
		$instance['type'] = strip_tags( $new_instance['type'] );
		$instance['subject'] = strip_tags( $new_instance['subject'] );
		return $instance;
    }
	
	public function widget( $args, $instance ) {
		extract( $args );
		$toId = apply_filters( 'toId', $instance['toId'] );		
		$type = apply_filters( 'type', $instance['type'] );	
		$subject = apply_filters( 'subject', $instance['subject'] );
		
		if ($type == 'email'){
			echo do_shortcode("[hermes_email to='".$toId."' subject='".$subject."']");
		} else if ($type == 'skype'){
			echo do_shortcode("[hermes_skype to='".$toId."']"); 
		} else if ($type == 'whatsapp'){
			echo do_shortcode("[hermes_whatsapp to='".$toId."']"); 
		} else if ($type == 'wechat'){
			echo do_shortcode("[hermes_wechat to='".$toId."']"); 
		}			
	}
}

function hermes_widget_init(){
	register_widget( 'HermesWidget' );
}
add_action('widgets_init','hermes_widget_init');
?>