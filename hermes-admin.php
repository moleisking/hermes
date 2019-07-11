<?php
 /**
  * @author Scott Johnston
  * @license https://www.gnu.org/licenses/gpl-3.0.html
  * @package Hermes
  * @version 1.0.0
 */
 
defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

class HermesAdmin{		

    private $option_group =  'hermes-config-group';

	public function __construct(){
        add_action( 'admin_menu', array($this,'add_menu') );
        add_action( 'admin_init', array($this, 'register_configure_parameters') );
        add_action( 'wp_loaded', array($this,'post_handler'));	
    }
    
    function register_configure_parameters() {   
        register_setting( $this->option_group, 'email_notification', array('boolean', 'email notifications',null ,false , '0') );      
        register_setting( $this->option_group, 'icon_size', array('string', 'message icon width',null ,false , '48rem') );  
        register_setting( $this->option_group, 'textarea_length', array('string', 'message textarea width',null ,false , 150) );
        register_setting( $this->option_group, 'textarea_rows', array('integer', 'message textarea rows',null ,false , 3) );  
        register_setting( $this->option_group, 'whatsapp_field_name', array('string', 'whatsapp field name',null ,false , 'custom_field_whatsapp') );  
        register_setting( $this->option_group, 'skype_field_name', array('string', 'Skype field name',null ,false , 'custom_field_skype') );  
        register_setting( $this->option_group, 'wechat_field_name', array('string', 'WeChat field name',null ,false , 'custom_field_wechat') );
        register_setting( $this->option_group, 'max_message_count', array('integer', 'Max amount of messages per day per user',null ,false , 10) );  
        register_setting( $this->option_group, 'api_enable', array('boolean', 'Allow hermes API for GET and POST that return JSON',null ,false , true) );                  
    }    

	function add_menu() {
        $menu_title = 'hermes-info-page';
        $capability = 'manage_options';
		add_menu_page( 'Info', 'Hermes', $capability, $menu_title, array($this, 'add_info_page'), 'dashicons-email', 4 );        
        add_submenu_page( $menu_title, 'Hermes Look and Feel', 'Configuration', $capability, 'hermes-configuration-page' , array($this, 'add_configuration_page') );	        				
	}

	public function add_info_page(){
        $plugin_data = get_plugin_data( plugin_dir_path(__FILE__).'hermes.php') ;
        echo "<h1>".$plugin_data["Name"]." Info</h1>";       
		echo "<p>".$plugin_data["Description"]."</p>";        
        ?>
        <h2>Checklist</h2>
        <ol>
            <li>By default Skype, Wechat and WhatsApp use custom_field_skype, custom_field_wechat and custom_field_whatsapp. Check that these fields are set correctly.</li> 
            <li>For Email a 3rd party emailer that allows "wp_mail()" to function is required.</li>
        </ol>       
        <h2>Examples</h2>
        <ul>
            <li><code>Parameters {to}=userId, {subject}='email subject', {path}='path to add between default base url and userId'</code></li> 
            <li><code>[hermes_chat to='1']</code></li> 
            <li><code>[hermes_enemy to='1']</code></li>             
            <li><code>[hermes_friend to='1']</code></li> 
            <li><code>[hermes_inbox path='users']</code></li> 
            <li><code>[hermes_email to='1' subject='News']</code></li> 
            <li><code>[hermes_help] </code></li> 
            <li><code>[hermes_skype to='1'] </code></li> 
            <li><code>[hermes_wechat to='1']  </code></li> 
            <li><code>[hermes_whatsapp to='1'] </code></li>  
            <li><code>&lt;div class='hermesFloatButton'&gt;[hermes_whatsapp to='1']&lt;/div&gt; </code></li>  
            <li><h3>Horizontal spacing of shortcodes</h3>
                <code>
                    &lt;div style ='display: inline-block;'&gt;<br> 
                    &nbsp;&nbsp;&lt;div style ='margin:5px;float:left;'>[hermes_chat to='1']&lt;/div&gt;<br>
                    &nbsp;&nbsp;&lt;div style ='margin:5px;float:left;'>[hermes_skype to='1']&lt;/div&gt;<br>
                    &nbsp;&nbsp;&lt;div style ='margin:5px;float:left;'&gt;[hermes_email to='1' subject='News' message='Hello!']&lt;/div&gt;<br> 
                    &nbsp;&nbsp;&lt;div style ='margin:5px;float:left;'>[hermes_help]&lt;/div&gt;<br> 
                    &nbsp;&nbsp;&lt;div style ='margin:5px;float:left;'>[hermes_skype to='1']&lt;/div&gt;<br>
                    &nbsp;&nbsp;&lt;div style ='margin:5px;float:left;'>[hermes_wechat to='1']&lt;/div&gt;<br> 
                    &nbsp;&nbsp;&lt;div style ='margin:5px;float:left;'>[hermes_whatsapp to='1']&lt;/div&gt;<br> 
                    &nbsp;&lt;/div&gt;
                </code>
            </li> 
        </ul>        
        <h2>Plugin</h2>
        <ul>        
            <li>Version:<?php echo $plugin_data["Version"];  ?></li> 
            <li>URL: <a href='<?php echo $plugin_data["PluginURI"];  ?>'><?php echo $plugin_data["Name"] ?></a></li>
        </ul>
        <?php 
       
	}
    
    public function add_configuration_page(){	
        ?>

        <h1>Hermes Configure</h1>
            <form method='post' action='options.php'>	
            <?php settings_fields( $this->option_group ); ?>
            <?php do_settings_sections( $this->option_group ); ?>	
                <h2>Look and feel</h2>
                <p>Textarea length<br>
                <input  name="textarea_length" type='number' value="<?php 
                echo (!empty(get_option('textarea_length'))) ? filter_var ( get_option('textarea_length') , FILTER_SANITIZE_EMAIL ) :  150; 
                ?>" placeholder = "textarea length"/></p>

                <p>Textarea rows<br>
                <input  name="textarea_rows" type='number' value="<?php 
                    echo (!empty(get_option('textarea_rows'))) ? get_option('textarea_rows') : 3; 
                ?>" placeholder = "textarea rows"/></p>	
               
               <p>Icon size<br>
                <select name="icon_size" value = <?php (!empty(get_option('icon_size'))) ? get_option('icon_size') : '48rem' ; ?>>
                    <option value="16rem"<?php if(get_option('icon_size') == '16rem'): ?> selected="selected"<?php endif; ?>>16rem</option>
                    <option value="32rem"<?php if(get_option('icon_size') == '32rem'): ?> selected="selected"<?php endif; ?>>32rem</option>
                    <option value="48rem"<?php if(get_option('icon_size') == '48rem'): ?> selected="selected"<?php endif; ?>>48rem</option>
                    <option value="64rem"<?php if(get_option('icon_size') == '64rem'): ?> selected="selected"<?php endif; ?>>64rem</option>
                    <option value="96rem"<?php if(get_option('icon_size') == '96rem'): ?> selected="selected"<?php endif; ?>>96rem</option>
                </select></p>

                <h2>User custom fields</h2>
                <p>WhatsApp<br>
                <input  name="whatsapp_field_name" type='text' value="<?php 
                echo (!empty(get_option('whatsapp_field_name'))) ? filter_var ( get_option('whatsapp_field_name') , FILTER_SANITIZE_EMAIL ) :  "custom_field_whatsapp"; 
                ?>" placeholder = "textarea width"/></p>

                <p>Skype<br>
                <input  name="skype_field_name" type='text' value="<?php 
                echo (!empty(get_option('skype_field_name'))) ? filter_var ( get_option('skype_field_name') , FILTER_SANITIZE_EMAIL ) :  "custom_field_skype"; 
                ?>" placeholder = "Skype field name"/></p>

                <p>WeChat<br>
                <input  name="wechat_field_name" type='text' value="<?php 
                echo (!empty(get_option('wechat_field_name'))) ? filter_var ( get_option('wechat_field_name') , FILTER_SANITIZE_EMAIL ) :  "custom_field_wechat"; 
                ?>" placeholder = "WeChat field name"/></p>

                <h2>Spam protection</h2>
                <p>Max messages per day per user<br>
                <input  name="max_message_count" type='number' value="<?php 
                echo (!empty(get_option('max_message_count'))) ? filter_var ( get_option('max_message_count') , FILTER_SANITIZE_NUMBER_INT ) :  10; 
                ?>" placeholder = "max messages per day per user"/></p>

                <h2>Clean message table</h2>
                <button id='btnCleanMessageTable' name='btnCleanMessageTable' type='submit' class='button'>Clean message table</button>.	

                <h2>Clean relationship table</h2>
                <button id='btnCleanRelationshipTable' name='btnCleanRelationshipTable' type='submit' class='button'>Clean relationship table</button>.	

                <h2>Notifications</h2>
                <p>Email <input  name="email_notification" type='checkbox' value='1' <?php 
                checked( '1', get_option( 'email_notification' ) );              
                ?> /></p>

                <h2>Message Json</h2>
                <p>Hermes JSON API<input  name="api_enable" type='checkbox' value="<?php 
                    echo (!empty(get_option('api_enable'))) ? get_option('api_enable') : "true"; 
                ?>" /></p>	

                <?php submit_button(); ?>			
            </form>

         <?php
    }
    
    public function post_handler(){		
		if (is_user_logged_in() && is_admin() && isset($_POST['btnCleanMessageTable'])) {
            $this->delete_messages();            
        } else if (is_user_logged_in() && is_admin() && isset($_POST['btnCleanRelationshipTable'])) {
            $this->delete_relationships();            
        }
    }

    public function delete_messages(){
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE ".$wpdb->base_prefix.'messages');			
    }
    
    public function delete_relationships(){
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE ".$wpdb->base_prefix.'relationships');			
	}
}

$hermesAdmin = new HermesAdmin;
?>