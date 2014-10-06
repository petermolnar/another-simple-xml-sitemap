<?php
/**
 * Based on "Google XML Sitemap" -  https://github.com/corvannoorloos/google-xml-sitemap


 * Plugin Name: Another Simple XML Sitemap
 * Description: (If you have more than 50 000 posts, better to use plugins like "Better WordPress Google XML Sitemaps" or etc..) After activation, this plugin automatically adds the sitemap (yoursite.com/sitemap.xml), also, this plugin writes the sitemap url in your robots.txt file, which is a good for search engines  (ON-OFF under settings).
 * contributors: selnomeria 
 * Original Author: Cor van Noorloos
 * @license http://www.opensource.org/licenses/gpl-license.php GPL v2.0 (or later)
 * Version: 1.1
 */
  
  
register_activation_hook( __FILE__, 'my_asxs_plugin_activatee' );
function my_asxs_plugin_activatee() 
{
	//insert "OFF" while installed
	update_option('asxs_sitemap_swith','off');
	
	//modify robots.txt
	$rob_sitmp_line='Sitemap: '.home_url()."/sitemap.xml\r\n\r\n";
	$rob_location=ABSPATH.'/robots.txt';
	
	if (!file_exists($rob_location))	{file_put_contents($rob_location,$rob_sitmp_line);}
	else 
	{
		$rb_existing = file_get_contents($rob_location);
		
		//if ROBOTS.TXT exists, but line is not written
		if (!stristr($rb_existing,'Sitemap:'))
		{
			file_put_contents($rob_location, $rb_existing ."\r\n\r\n".$rob_sitmp_line);
		}
	}
}



		
add_action( 'template_redirect', 'asxs_sitemap2' );
function asxs_sitemap2() 
{
	if ( ! preg_match( '/sitemap\.xml$/', $_SERVER['REQUEST_URI'] ) ) {
		return;
	}
	if (get_option('asxs_sitemap_swith') != 'onn' ){
		return;
	}
	
	
	global $wpdb;
	$posts = $wpdb->get_results( "SELECT ID, post_title, post_modified_gmt
		FROM $wpdb->posts
		WHERE post_status = 'publish'
		AND post_password = ''
		ORDER BY post_type DESC, post_modified DESC
		LIMIT 50000" );
	header( "HTTP/1.1 200 OK" );
	header( 'X-Robots-Tag: noindex, follow', true );
	header( 'Content-Type: text/xml' );
	echo '<?xml version="1.0" encoding="' . get_bloginfo( 'charset' ) . '"?>' . "\n";
	echo '<!-- generator="' . home_url( '/' ) . '" -->' . "\n";
	$xml  = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";
	$xml .= "\t<url>" . "\n";
	$xml .= "\t\t<loc>" . home_url( '/' ) . "</loc>\n";
	$xml .= "\t\t<lastmod>" . mysql2date( 'Y-m-d\TH:i:s+00:00', get_lastpostmodified( 'GMT' ), false ) . "</lastmod>\n";
	$xml .= "\t\t<changefreq>" . 'daily' . "</changefreq>\n";
	$xml .= "\t\t<priority>" . '1' . "</priority>\n";
	$xml .= "\t</url>" . "\n";
	foreach ( $posts as $post ) 
	{
		if ( $post->ID == get_option( 'page_on_front' ) )
		  continue;
		if ( ! empty( $post->post_title ) ) 
		{
		  $xml .= "\t<url>\n";
		  $xml .= "\t\t<loc>" . get_permalink( $post->ID ) . "</loc>\n";
		  $xml .= "\t\t<lastmod>" . mysql2date( 'Y-m-d\TH:i:s+00:00', $post->post_modified_gmt, false ) . "</lastmod>\n";
		  $xml .= "\t\t<changefreq>" . 'weekly' . "</changefreq>\n";
		  $xml .= "\t\t<priority>" . '0.8' . "</priority>\n";
		  $xml .= "\t</url>\n";
		}
	}
	$xml .= '</urlset>';
	echo ( "$xml" );
	exit();
}





add_action('admin_menu','asxs_outputt'); function asxs_outputt() { add_submenu_page( 'options-general.php', 'Another Simple XML Sitemap', 'Another Simple XML Sitemap', 'manage_options', 'asxs-sitemaap', 'asxs_output_pagee' ); }
function asxs_output_pagee()
{
	if (!empty($_POST['asxs_sitemap_onoff']))
	{
		update_option('asxs_sitemap_swith',$_POST['asxs_sitemap_onoff']);
	}
	$optvalue = get_option('asxs_sitemap_swith');
	$onoff2 = 	$optvalue == 'off' ? 'checked' : '';
	$onoff1 = 	$optvalue == 'onn' || empty($optvalue) ? 'checked' : '';
	?>
	
	<div class="asxs_pg" style="margin:50px 0 0 50px;">
	<form name="" class="" action="" method="POST">
	Sitemap (can be accessed at <b><a href="<?php echo get_bloginfo('url');?>/sitemap.xml"><?php echo get_bloginfo('url');?>/sitemap.xml</a>)<br/>
	ON<input type="radio" name="asxs_sitemap_onoff" value="onn" <?php echo $onoff1;?> /> &nbsp;&nbsp; OFF<input type="radio" name="asxs_sitemap_onoff" value="off"  <?php echo $onoff2;?> />
	<input type="submit" value="UPDATE" />
	</form>
	<?php
}
?>