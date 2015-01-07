<?php
/*
Plugin Name: Another Simple XML Sitemap
Description:  Add a sitemap at YOURSITE.COM/sitemap.xml [Supports unlimited (+50000) posts;  sitemap is Plain, without any styles..Enjoy!] (VIEW other MUST-HAVE PLUGINS : http://codesphpjs.blogspot.com/2014/10/must-have-wordpress-plugins.html ).
This plugin writes the sitemap url in your robots.txt file, which is a good for search engines.
contributors: selnomeria 
Original Author: ( Based on "Google XML Sitemap" -  https://github.com/corvannoorloos/google-xml-sitemap )
@license http://www.opensource.org/licenses/gpl-license.php GPL v2.0 (or later)
Version: 1.1
*/

  
register_activation_hook( __FILE__, 'my_asxs_plugin_activatee' );
function my_asxs_plugin_activatee() {
	//update robots.txt
		$rob_sitmp_line	='Sitemap: '.home_url()."/sitemap.xml\r\n\r\n";
		$robot_path	=ABSPATH.'/robots.txt';
			if (!file_exists($robot_path))	{file_put_contents($robot_path, $rob_sitmp_line);}
			else {	$current_content = file_get_contents($robot_path);
					if (!stristr($current_content,'Sitemap:'))	{file_put_contents($robot_path, $current_content ."\r\n\r\n".$rob_sitmp_line);}
			}
}

		
add_action( 'init', 'asxs_sitemap2' );
function asxs_sitemap2() {
	$Index_Sitemap_url 		= home_url( '/sitemap.xml',$scheme = relative);
	$Normal_Sitemap_urltype	= home_url( '/sitemap_part_',$scheme = relative);
	$Initial_S		=($Index_Sitemap_url == $_SERVER['REQUEST_URI'])														? 	true : false;
	$Typical_S		=(stristr($_SERVER['REQUEST_URI'],$Normal_Sitemap_urltype) && strstr($_SERVER['REQUEST_URI'],'.xml') )	? 	true : false;

	if ($Initial_S || $Typical_S){
		global $wpdb;
		header( "HTTP/1.1 200 OK" );header( 'X-Robots-Tag: noindex, follow', true );header( 'Content-Type: text/xml' );
		echo 	'<?xml version="1.0" encoding="' . get_bloginfo( 'charset' ) . '"?>' . '<!-- generator="' . home_url( '/' ) . ' ('.basename(__FILE__).')" -->' . "\n";
		$default= 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"';
		
		
		
		//============================INDEX SITEMAP==============================
		if ($Initial_S){
			$posts = $wpdb->get_results( "SELECT ID, post_title, post_modified_gmt	FROM $wpdb->posts WHERE post_status = 'publish'	AND post_password = '' ORDER BY post_type DESC, post_modified DESC" );
			$xml= '<sitemapindex '.$default.'>' . "\n";
				for ($i=1; $i<(count($posts)/50000)+1; $i++) {$xml .='<sitemap><loc>'.home_url().'/sitemap_part_'.$i.'.xml</loc></sitemap>';}
			die($xml.'</sitemapindex>');
		}

		//============================TYPICAL SITEMAP==============================
		if ($Typical_S){
			$partNumber= str_replace(array($Normal_Sitemap_urltype,'.xml') , '',  $_SERVER['REQUEST_URI']);
					//##################### include "homepage" link ########################
					$xml.= ($partNumber ==1 ) ? "\t<url>" . "\n".		"\t\t<loc>" . home_url( '/' ) . "</loc>\n".	"\t\t<lastmod>" . mysql2date( 'Y-m-d\TH:i:s+00:00', get_lastpostmodified( 'GMT' ), false ) . "</lastmod>\n". "\t\t<changefreq>" . 'daily' . "</changefreq>\n". "\t\t<priority>" . '1' . "</priority>\n". "\t</url>" . "\n"		: '';
					// #####################################################################
			$posts = $wpdb->get_results( "SELECT ID, post_title, post_modified_gmt	FROM $wpdb->posts WHERE post_status = 'publish'	AND post_password = '' ORDER BY post_type DESC, post_modified DESC LIMIT 50000 OFFSET ". ($partNumber-1) * 50000);
			$xml=  '<urlset '.$default.'>' . "\n"; 
				foreach ( $posts as $post ) {
					if ( $post->ID != get_option( 'page_on_front' ) ){
						if ( ! empty( $post->post_title ) ) {
						$xml .=
						"\t<url>\n".
							"\t\t<loc>" . get_permalink( $post->ID ) . "</loc>\n".
							"\t\t<lastmod>" . mysql2date( 'Y-m-d\TH:i:s+00:00', $post->post_modified_gmt, false ) . "</lastmod>\n". //i.e. 2015-01-07T07:47:10+00:00
							"\t\t<changefreq>" . 'weekly' . "</changefreq>\n".
							"\t\t<priority>" . '0.8' . "</priority>\n".
						"\t</url>\n";
						}
					}
				}
			$xml .= '</urlset>';
			die($xml);
		}
	}
	elseif ($Index_Sitemap_url.'/' ==$_SERVER['REQUEST_URI']){ #if mistakenly  "sitemap.xml/" was opened
			header("Cache-Control: no-store, no-cache, must-revalidate");header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");header("HTTP/1.1 301 Moved Permanently");	header("Location: ".  $Index_Sitemap_url ) or die('another simple xml sitemap cant redirect. error_734');
	}
}
?>