<?php
/**
 * Created by PhpStorm.
 * User: kobi
 * Date: 21/04/2017
 * Time: 12:05 PM
 */
class paybillng_activate_plugin
{
    public static function plugin_activated() {

        include 'paybillng_pages_to_add.php';
        $page_definitions = paybillng_pages_to_add::paybillng_pages();

        foreach ( $page_definitions as $slug => $page ) {
            $query = new WP_Query( 'pagename=' . $slug );
            if ( ! $query->have_posts() ) {
                $post_id = wp_insert_post(
                    array(
                        'post_content'   => $page['content'],
                        'post_name'      => $slug,
                        'post_title'     => $page['title'],
                        'post_type'      => 'page',
                        'ping_status'    => 'closed',
                        'comment_status' => 'closed',
                        'menu_order' => 0

                    )
                );

                if( !$post_id )
                    wp_die('Error creating template page');
                else
                    update_post_meta( $post_id, '_wp_page_template', 'pay-bill-loading-template.php' );

            }
        }
    }

}

$activate_plugin = new paybillng_activate_plugin();