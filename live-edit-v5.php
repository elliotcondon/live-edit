<?php 

class live_edit {
	
	var $settings;
	
	
	/*
	*  __construct
	*
	*  description
	*
	*  @type	function
	*  @date	3/04/2014
	*  @since	5.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function __construct() {
		
		// vars
		$this->settings = array(
			
			// basic
			'name'			=> __('Live Edit', 'live-edit'),
			'version'		=> '2.1.4',
						
			// urls
			'basename'		=> plugin_basename( __FILE__ ),
			'path'			=> plugin_dir_path( __FILE__ ),
			'dir'			=> plugin_dir_url( __FILE__ ),
			
			// options
			'panel_width'	=> get_option('live_edit_panel_width', 600)
		);
		
		
		// set text domain
		load_plugin_textdomain('live-edit', false, basename(dirname(__FILE__)).'/lang' );
		
		
		// actions
		add_action('init', array($this,'_init'));
	}
	
	
	/*
	*  wp_init
	*
	*  description
	*
	*  @type	function
	*  @date	3/04/2014
	*  @since	5.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function _init() {
		
		// must be logged in
		if( !is_user_logged_in() ) {
			
			return;
			
		}
		
		
		// scripst
		wp_register_script( 'live-edit-admin', $this->settings['dir'] . '/js/functions.admin.js', false, $this->settings['version'] );
		wp_register_script( 'live-edit-front', $this->settings['dir'] . '/js/functions.front.js', false, $this->settings['version'] );
		wp_register_style( 'live-edit-admin', $this->settings['dir'] . '/css/style.admin.css', false, $this->settings['version'] );
		wp_register_style( 'live-edit-front', $this->settings['dir'] . '/css/style.front.css', false, $this->settings['version'] );
		
		
		// actions (admin)
		add_action('admin_head', array($this,'admin_head'));
		add_action('admin_menu', array($this,'admin_menu'));
		
		
		// actions (front)
		add_action('wp_enqueue_scripts', array($this,'wp_enqueue_scripts'));
		//add_action('wp_head', array($this,'wp_head'));
		add_action('wp_footer', array($this,'wp_footer'));
		
		
		// actions (ajax)
		add_action('wp_ajax_live_edit_update_width', array($this, 'ajax_update_width'));
	
		
	}
	
	
	/*
	*  admin_head
	*
	*  description
	*
	*  @type	function
	*  @date	3/04/2014
	*  @since	5.0.0
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/
	
	function admin_head() {
		
		echo '<style type="text/css">#menu-settings a[href="options-general.php?page=live-edit-panel"] { display:none; }</style>';
		
	}
	
	
	/*
	*  admin_menu
	*
	*  description
	*
	*  @type	function
	*  @date	3/04/2014
	*  @since	5.0.0
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/
	
	function admin_menu() {
		
		$slug = add_options_page(__("Live Edit Panel",'live-edit'), __("Live Edit Panel",'live-edit'), 'edit_posts', 'live-edit-panel', array($this, 'panel_view'));
		
		// actions
		add_action("load-{$slug}", array($this,'panel_load'));
				
	}
	
	
	/*
	*  admin_load
	*
	*  description
	*
	*  @type	function
	*  @date	3/04/2014
	*  @since	5.0.0
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/
	
	function panel_load() {
		
		acf_form_head();
		
		
		// enqueue scripts
		wp_enqueue_script('live-edit-admin');
		wp_enqueue_style('live-edit-admin');
		
	}
	
	
	/*
	*  panel_view
	*
	*  description
	*
	*  @type	function
	*  @date	3/04/2014
	*  @since	5.0.0
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/
	
	function panel_view() {
		
		// vars
		$options = wp_parse_args($_GET, array(
			'fields'	=> '',
			'post_id'	=> 0,
			'updated'	=> 0,
			'nonce'		=> '',
		));
		
		
		// validate
		if( !$options['post_id'] ) {
		
			wp_die( "Error: No post_id parameter found" );
			
		}
		
		if( !$options['fields'] ) {
		
			wp_die( "Error: No fields parameter found" );
			
		}
		
		
		if( !wp_verify_nonce($options['nonce'], 'live_edit_nonce') ) {
		
			wp_die( "Error: Access Denied" );
			
		}
		
		
		// loop through and load all fields as objects
		$fields = explode(',', $options['fields']);
		
		
		// form args
		$args = array(
			'post_id'				=> $options['post_id'],
			'post_title'			=> in_array('post_title', $fields),
			'post_content'			=> in_array('post_content', $fields),
			'post_excerpt'			=> in_array('post_excerpt', $fields),
			'html_before_fields'	=> '<div class="form-title"><h2>' . __('Live Edit', 'live-edit') . '</h2><ul class="acf-hl"><li><a href="#" class="button button-close">' . __('Close Panel', 'live-edit') . '</a></li><li><span class="spinner"></span><input type="submit" value="' . __('Update', 'live-edit') . '" class="button button-primary"></li></ul></div>',
			'html_after_fields'		=> '<p class="credits">' . __('Powered by', 'live-edit') . ' <a href="http://wordpress.org/plugins/live-edit/" target="_blank">' . __('Live Edit', 'live-edit') . '</a></p>'
		);
		
		
		// remove title / content
		$fields = array_diff($fields, array('post_title', 'post_content', 'post_excerpt'));
		$args['fields'] = $fields;
		
		
		// create form
		acf_form( $args );

		// find editable flexible layouts
		$flex_fields = array();

		foreach( $fields as $field ) {

			if( substr( $field, 0, 5 ) === "flex-") {

				$field = str_replace( 'flex-', '', $field );
				$flex_fields[] = $field;
			}
		}

		// remove all other flexible layouts
		if( !empty( $flex_fields ) )  {

			$flex_fields = json_encode( $flex_fields ); ?>

			<script type="text/javascript">
				(function($){

					// loop through all flexible layouts loaded in DOM
					$( 'div.layout' ).each(function(){


						var fieldName = $(this).attr('data-layout');
						
						// remove layouts not included in editable fields
						if( $.inArray(fieldName, <?php echo $flex_fields; ?>) == -1 ) {
							$(this).css({'position':'absolute', 'z-index':'-1'});
						}


					});

				})(jQuery);

			</script>

		<?php }

		
		
		if( $options['updated'] === 'true' ) {
			
			?>
			<script type="text/javascript">
			(function($){
			
			
			// validate parent
			if( !parent || !parent.live_edit ) {
			
				return;
				
			}
			
			
			// update the div
			parent.live_edit.sync();
			
			
			})(jQuery);
			</script>
			<?php
			
		}
		
	}
	
	
	/*
	*  wp_enqueue_scripts
	*
	*  description
	*
	*  @type	function
	*  @date	3/04/2014
	*  @since	5.0.0
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/
	
	function wp_enqueue_scripts() {
		
		wp_enqueue_script(array(
			'jquery',
			'jquery-ui-core',
			'jquery-ui-widget',
			'jquery-ui-mouse',
			'jquery-ui-resizable',
			'live-edit-front'
		));
		
		wp_enqueue_style('live-edit-front');

	}

	
	/*
	*  wp_footer
	*
	*  description
	*
	*  @type	function
	*  @date	3/04/2014
	*  @since	5.0.0
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/
	
	function wp_footer() {
		
		// vars
		$o = array(
			'ajaxurl'		=> admin_url( 'admin-ajax.php' ),
			'panel_url'		=> admin_url( 'options-general.php?page=live-edit-panel' ),
			'panel_width'	=> $this->settings['panel_width'],
			'nonce'			=> wp_create_nonce('live_edit_nonce')
		);
		
		
		?>
		<script type="text/javascript">
		(function($) {
		
			live_edit.o = <?php echo json_encode( $o ); ?>;
		
		})(jQuery);	
		</script>
		<div id="live-edit-panel">
			<div id="live-edit-iframe-cover"></div>
			<iframe id="live-edit-iframe"></iframe>
		</div>
		<?php
		
	}
	
	
	/*
	*  ajax_update_width
	*
	*  description
	*
	*  @type	function
	*  @date	4/04/2014
	*  @since	5.0.0
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/
	
	function ajax_update_width() {
		
		// vars
		$options = wp_parse_args($_POST, array(
			'width' 	=> 600,
			'nonce'		=> '',
		));
				
		
		// validate
		if( ! wp_verify_nonce($options['nonce'], 'live_edit_nonce') ) {
		
			wp_send_json_error();
			
		}
		
		
		// update option
		update_option( 'live_edit_panel_width', $options['width'] );
		
		
		// success
		wp_send_json_success();
	}
	
}

new live_edit();


/*
*   live_edit 
*
*  description
*
*  @type	function
*  @date	3/04/2014
*  @since	5.0.0
*
*  @param	$post_id (int)
*  @return	$post_id (int)
*/

function live_edit( $fields = false, $post_id = false ) {
	
	// validate fields
	if( !$fields ) {
	
		return false;
		
	}
	
	
	// filter post_id
	$post_id = acf_get_valid_post_id( $post_id );
	
	
	// turn array into string
	if( is_array($fields) )
	{
		$fields = implode(',', $fields);
	}
	
	
	// remove any white spaces from $fields
	$fields = str_replace(' ', '', $fields);

	
	// build atts
	acf_esc_attr_e(array(
		'data-live-edit-id'			=> $post_id . '-' . str_replace(',', '-', $fields),
		'data-live-edit-fields'		=> $fields,
		'data-live-edit-post_id'	=> $post_id
	));
	
}

?>