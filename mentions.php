<?php

/*
Plugin Name: Shepherds Mention Plugin
Description: CUSTOM: This is a custom test plugin for a mentions feature.
Version: 1.0
Author: maburdi
*/


class wp_mention_plugin
{
    public static function initialize() {

        add_filter( 'preprocess_comment', array ( 'wp_mention_plugin', 'wpmp_mod_comment' ) );
        add_action( 'wp_set_comment_status', array ( 'wp_mention_plugin', 'wpmp_approved' ), 10, 2 );
        add_action( 'wp_insert_comment', array ( 'wp_mention_plugin', 'wpmp_no_approve' ), 10, 2 );


        // These are for AJAX autocomplete front-end
        add_action( 'wp_ajax_custom_mention', array('wp_mention_plugin', 'wpmp_fetch_users') );

        // Front-end code
        wp_enqueue_script('mention-script', plugins_url( 'js/plugin.js', __FILE__ ), array('jquery'));
        wp_enqueue_style( 'mention-style', plugins_url( 'js/plugin.css', __FILE__ ) );
    }

    public static function wpmp_fetch_users() {
        $search = $_POST['search'];
        $users = new WP_User_Query( array(
            'search'         => esc_attr( $search ).'*',
            'search_columns' => array(
                'display_name',
                'user_nicename',
            ),
            'fields'         => array(
                'display_name',
                'user_nicename',
            )
        ) );
        $users_found = json_encode($users->get_results());

        echo $users_found;

        wp_die(); // this is required to terminate immediately and return a proper response
    }

    public static function wpmp_mod_comment( $comment ) {

        $replacement = "<a class='comment-mention' href='/author/$1/' rel='nofollow'>@$1</a>";

        $comment['comment_content'] = preg_replace( "/@\[([\w ]+)\]/", $replacement, $comment['comment_content'] );

        return $comment;
    }


    private static function wpmp_send_mail( $comment ) {
        $post_title    = get_the_title($comment->comment_post_ID);
        $the_related_comment_url = get_comment_link( $comment->comment_ID );

        // we get the mentions here
        $the_comment = $comment->comment_content;
        $pattern     = "/@([\w ]+)/";

        // if we find a match of the mention pattern
        if ( preg_match_all( $pattern, $the_comment, $match ) ) {

            // remove all @s from comment author names to effectively
            // generate appropriate email addresses of authors mentioned
            $author_emails = array();
            foreach ( $match[1] as $m ) {
                array_push($author_emails, self::wpmp_gen_email($m));
            }

            // ensure at least one valid comment author is mentioned before sending email
            if ( ! is_null( $author_emails ) ) {
                $subj = '[' . get_bloginfo( 'name' ) . '] Someone mentioned you in a comment';

                $email_body = "<p>It's great to be noticed! Check it out: <a href='". $the_related_comment_url . "'>" . $post_title . "</a></p>";

                $headers = array('Content-Type: text/html; charset=UTF-8');


                wp_mail( $author_emails, $subj, $email_body, $headers );

            }
        }
    }


    public static function wpmp_gen_email( $name ) {
        global $wpdb;

        $name  = sanitize_text_field( $name );
        $query = "SELECT comment_author_email FROM {$wpdb->comments} WHERE comment_author = %s ";

        $prepare_email_address = $wpdb->prepare( $query, $name );
        $email_address         = $wpdb->get_var( $prepare_email_address );

        return $email_address;
    }

    public static function wpmp_approved( $comment_id, $status ) {
        $comment = get_comment( $comment_id, OBJECT );
        ( $comment && $status == 'approve' ? self::wpmp_send_mail( $comment ) : null );
    }

    public static function wpmp_no_approve( $comment_id, $comment_object ) {
        ( wp_get_comment_status( $comment_id ) == 'approved' ? self::wpmp_send_mail( $comment_object ) : null );
    }
}


$wp_mention_plugin = new wp_mention_plugin;
$wp_mention_plugin->initialize();



