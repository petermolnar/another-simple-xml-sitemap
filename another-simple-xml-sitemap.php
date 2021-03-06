<?php
/*
Plugin Name: Another Simple XML Sitemap
Plugin URI: https://github.com/petermolnar/another-simple-xml-sitemap
Description: A dead simple XML sitemap, listing singular() content and home() only.
Contributors: cadeyrn, selnomeria
Author: Peter Molnar <hello@petermolnar.eu>
Author URI: http://petermolnar.eu/
Original Author: selnomeria <tazotodua@gmail.com>
License: GPLv3
Version: 2.1
*/

/*
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 3, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


register_activation_hook( __FILE__, 'my_asxs_plugin_activatee' );
add_action( 'wp', 'asxs_sitemap2' );
define('ASXS_LIMIT', 9999);
define('ASXS_EXPIRE', 300);

function my_asxs_plugin_activatee() {
	//update robots.txt
	$rob_sitmp_line = 'Sitemap: '. home_url() ."/sitemap.xml\r\n\r\n";
	$robot_path = ABSPATH.'/robots.txt';
	if (!file_exists($robot_path)) {
		file_put_contents($robot_path, $rob_sitmp_line);
	}
	else {
		$current_content = file_get_contents($robot_path);
		if (!stristr($current_content,'Sitemap:')) {
			file_put_contents($robot_path, $current_content ."\r\n\r\n".$rob_sitmp_line);
		}
	}
}

/*
 * IF YOU WANT TO MODIFY Permalink, it's better not to modify this page, but
 * modify permalinks with a hook:
 *   add_filter( 'post_link'................
 * for Custom Types, use 'post_type_link'
 */


function asxs_sitemap2() {
	$Index_Sitemap_url = home_url( '/sitemap.xml', $scheme = 'relative' );
	$Normal_Sitemap_urltype = home_url( '/sitemap_part_', $scheme = 'relative' );
	$uri = rtrim($_SERVER['REQUEST_URI'], '/');

	$Index_S = ( $Index_Sitemap_url == $uri ) ? true : false;
	$Normal_S =( stristr($uri,$Normal_Sitemap_urltype) && strstr($uri,'.xml') ) ? true : false;

	if ( ! $Index_S && ! $Normal_S ) {
		return;
	}

	global $wpdb;
	$xml = array();

	header( "HTTP/1.1 200 OK" );
	header( 'X-Robots-Tag: noindex, follow', true );
	header( 'Content-Type: text/xml' );

	echo '<?xml version="1.0" encoding="' . get_bloginfo( 'charset' ) . '"?>' . '<!-- generator="' . home_url( '/' ) . ' ('.basename(__FILE__).')" -->' . "\n";
	$default= 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"';

	/* --- INDEX SITEMAP ---
	 * splits XML per every
	 */
	if ( $Index_S ) {
		$cached_id = 'index';
		if ( $cached = wp_cache_get ( $cached_id, __FUNCTION__ ) )
			die($cached);

		$posts = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_status = 'publish' AND post_password = ''" );
		$xml[] = '<sitemapindex '.$default.'>';
		for ($i=1; $i<(count($posts)/ASXS_LIMIT)+1; $i++) {
			$xml[] = '<sitemap><loc>'.home_url().'/sitemap_part_'. $i .'.xml</loc></sitemap>';
		}
		$xml[] = '</sitemapindex>';

		$xml = join("\n", $xml);
		wp_cache_set ( $cached_id, $xml, __FUNCTION__, ASXS_EXPIRE  );
		die($xml);
	}

	// --- NORMAL SITEMAP ---
	if ($Normal_S) {
		preg_match("#$Normal_Sitemap_urltype([0-9]+)\.xml#si" , $_SERVER['REQUEST_URI'], $new );
		$partNumber = $new[1];

		$cached_id = $new[1];
		if ( $cached = wp_cache_get ( $cached_id, __FUNCTION__ ) )
			die($cached);

		$posts = $wpdb->get_results( "SELECT ID, post_title, post_name, post_modified_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND post_password = '' ORDER BY post_type DESC, post_modified DESC LIMIT ". ASXS_LIMIT ." OFFSET ". ($partNumber-1) * ASXS_LIMIT);
		$frontpage_id = get_option( 'page_on_front' );

		$xml[] =  '<urlset '.$default.'>' . "\n";
		// include home url
		if ($partNumber == 1 ) {
			$xml[] = "\t<url>\n" .
				"\t\t<loc>" . htmlspecialchars(home_url( '/' )) . "</loc>\n" .
				"\t\t<lastmod>" . mysql2date( 'c', get_lastpostmodified( 'GMT' ), false ) . "</lastmod>\n". //i.e. 2015-01-07 07:47:10
				"\t\t<changefreq>" . 'daily' . "</changefreq>\n".
				"\t\t<priority>" . '1' . "</priority>\n".
			"\t</url>\n";
		}
		foreach ( $posts as $post ) {
			$title = empty($post->post_title) ? $post->post_name : $post->post_title;
			if (!empty($title) &&  $post->ID != $frontpage_id ) {
				$xml[] = "\t<url>\n".
					"\t\t<loc>" . htmlspecialchars(get_permalink( $post->ID )) . "</loc>\n".
					"\t\t<lastmod>" . mysql2date( 'c', $post->post_modified_gmt, false ) . "</lastmod>\n". //i.e. 2015-01-07 07:47:10
					"\t\t<changefreq>" . 'weekly' . "</changefreq>\n".
					"\t\t<priority>" . '0.5' . "</priority>\n".
				"\t</url>\n";
			}
		}
		$xml[] = '</urlset>';
		$xml = join("\n", $xml);
		wp_cache_set ( $cached_id, $xml, __FUNCTION__, ASXS_EXPIRE  );
		die($xml);
	}
}
?>
