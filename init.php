<?php
/*
Plugin Name:  Static Newsticker
Plugin URI:   https://wordpress.org/plugins/static-news-ticker
Description:  An easy to use, slick and flexible news ticker in the style of the BBC News page ticker
Version:      2.0.0
Author:       nath4n
Author URI:   http://thinknesia.com
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  wporg
Domain Path:  /languages
*/

add_action('wp_enqueue_scripts','static_newsticker_enqueue', 11);
if (!function_exists('static_newsticker_enqueue'))   {
	function static_newsticker_enqueue(){	
		wp_enqueue_style( 'style-marquee', PLUGINS_URL('style-marquee.css', __FILE__ ));		
		wp_enqueue_script( 'jquery' );		
	}
}

add_action('wp_footer','static_newsticker_script', 11);
if (!function_exists('static_newsticker_script'))   {
	function static_newsticker_script(){
		$settings = maybe_unserialize(get_option('news-ticker-settings'));
		?>
		<script type="text/javascript">
		var list = jQuery('.news ul');
		var totalWidth = 0;
		var items = jQuery('.news li').each(function(){
		  totalWidth += jQuery(this).outerWidth();
		});

		function scrub() {
		  // Note that we added a wrapper. That's so the entire list scrubs in,
		  // and you don't have to deal with it individually.
		  list.animate({
			// We animate the left all the way past negative so text travels from
			// right to left and we read left to right.
			left: -totalWidth
		  }, {
			easing: 'linear',
			// We adjust speed here. Default is 20ms per pixel.
			duration: totalWidth * <?php echo esc_html($settings['speed']); ?>,
			complete: function(){
			  // When the animation is done, we just move the element back to its
			  // starting position, which is off-screen to the right.
			  jQuery(this).css({ left: '100%' });
			  // Run scrub again.
			  scrub();
			}
		  });
		}
		// Start the scrub
		scrub();
				
		list.hover(function () { 
			list.stop();
		}, function () {
			scrub();
		});
		</script>
		<?php	
	}
}

add_action('wp_footer', 'show_static_newsticker');
if (!function_exists('show_static_newsticker'))   {
	function show_static_newsticker() {
		$settings = maybe_unserialize(get_option('news-ticker-settings'));
		$post_count = $settings['post-count'];
		$news = new wp_Query(array('post_type'=>'post',
							'posts_per_page'=>$post_count));
		?>
		<div class='news'>
			<header><?php echo esc_html($settings['title']); ?></header>
			<div class="wrapper">
				<ul>
				  <?php while( $news->have_posts() ) : $news->the_post(); ?>
					<li>
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</li>
				  <?php endwhile; ?>
				</ul>
			</div>
		</div>
		<?php
	}
}

if ( is_admin() ){
	/* Call the html code */
	add_action('admin_menu', 'static_newsticker_admin_menu');	
	if (!function_exists('static_newsticker_admin_menu'))   {
		function static_newsticker_admin_menu() {
			add_options_page('Static Newsticker', 
			'Static Newsticker', 
			'administrator',
			'static-news-ticker', 
			'static_newsticker_setting_page');
		}
	}
}

/* Runs when plugin is activated */
register_activation_hook(__FILE__,'static_newsticker_install');
if (!function_exists('static_newsticker_install'))   {
	function static_newsticker_install() {
		$settings = maybe_unserialize(get_option('news-ticker-settings'));
		
		// Set to default
		if (empty($settings['title'])) $settings['title'] = "Headlines";
		if (empty($settings['post-count'])) $settings['post-count'] = 10; 
		if (empty($settings['speed'])) $settings['speed'] = 20;
		
		$sanitize = array(
				   'title',
				   'post-count',
				   'speed'
				   );
		foreach ($sanitize AS $k) {
			$settings[$k] = sanitize_text_field($settings[$k]);
		}
		update_option("news-ticker-settings", maybe_serialize($settings));
	}
}

if (!function_exists('static_newsticker_setting_page'))   {
	function static_newsticker_setting_page() {
		static_newsticker_install();
		// update
		if ( current_user_can( 'manage_options' ) ) {
			if (isset($_POST['update']) && check_admin_referer( 'news-ticker-nonce' )) {    	
				$sanitize = array(
						   'title',
						   'post-count',
						   'speed'
						   );
				foreach ($sanitize AS $k) {
					$_POST[$k] = sanitize_text_field($_POST[$k]);
				}
				update_option("news-ticker-settings", maybe_serialize($_POST));
				$message.="Updated";
			}
		}
		// Rid data
		$settings = maybe_unserialize(get_option('news-ticker-settings'));
		// Add Style
		wp_enqueue_style('prefix-style', plugins_url('style-admin.css', __FILE__));
		?>
		 <div class="wrap">
			<h2>Static Newsticker Configuration</h2>
			<?php if (isset($message)): ?><div class="updated"><p><?php echo $message; ?></p></div><?php endif; ?>
			<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
				<?php wp_nonce_field( 'news-ticker-nonce' ); ?>
				<table class='wp-list-table widefat fixed'>
					<tr>
						<th class="ss-th-width">Title</th>
						<td><input type="text" name="title" value="<?php echo esc_html($settings['title']); ?>" class="ss-field-width" /></td>
					</tr>
					<tr>
						<th class="ss-th-width">Post Count</th>
						<td><input type="number" name="post-count" value="<?php echo esc_html($settings['post-count']); ?>" class="widefat ss-field-width" /></td>					
					</tr>
					<tr>
						<th class="ss-th-width">Marquee Speed</th>
						<td>
							<input type="number" name="speed" value="<?php echo esc_html($settings['speed']); ?>" class="widefat ss-field-width" /><br>
							<span class="description">Marquee Speed. Default is 20ms per pixel</span>
						</td>
					</tr>
				</table>
				<input type='submit' name="update" value='Save Changes' class='button'>
			</form>
		</div>
		<?php
	}
}
?>