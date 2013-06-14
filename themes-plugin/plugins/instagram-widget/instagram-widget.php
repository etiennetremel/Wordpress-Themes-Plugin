<?php
/*
Plugin Name: Instagram Widget
Version: 0.1
Description: Widget add Instagram Feed
Author: Etienne Tremel
*/

if ( ! class_exists( 'Instagram_Widget' ) ) {
    class Instagram_Widget {
        public function __construct() {
            /* INIT WIDGET */
            add_action( 'widgets_init', array( $this, 'instagram_widget_init' ) );
        }

        public function instagram_widget_init() {
            register_widget( 'Instagram_Widget_Constructor' );
        }
    }
}

if ( ! class_exists( 'Instagram_Widget_Constructor' ) ) {
    class Instagram_Widget_Constructor extends WP_Widget {
        function Instagram_Widget_Constructor() {
            $widget_ops = array(
                'classname'        => 'instagram-widget',
                'description'      => __( 'Add Instagram feed' )
            );

            parent::__construct( 'instagram-widget', __( 'Instagram Widget' ), $widget_ops );

            add_action( 'wp_enqueue_scripts', array( &$this, "enqueue_frontend_scripts" ) );
        }

        function enqueue_frontend_scripts() {
            wp_register_script(
                'instagram-widget_script',
                TP_PLUGIN_DIRECTORY_WWW . '/' . basename( dirname( __FILE__ ) ) . '/assets/frontend.js',
                array( 'jquery' )
            );
            wp_register_style( 'instagram-widget_style', TP_PLUGIN_DIRECTORY_WWW . '/' . basename( dirname( __FILE__ ) ) . '/assets/frontend.css' );

            wp_enqueue_script( 'instagram-widget_script' );
            wp_enqueue_style( 'instagram-widget_style' );
        }

        function widget( $args, $instance ) {
            extract( $args );

            $title          = $instance['title'];
            $username       = $instance['username'];
            $hashtag        = $instance['hashtag'];
            $slide_images   = $instance['slide_images'];
            $limit_number   = $instance['limit_number'];
            $access_token   = $instance['access_token'];

            if ( ( empty( $hashtag ) && empty( $username ) ) || ! $limit_number || !$access_token)
                return;


            //Check if feed already in transient (wordpress cache system)
            if ( get_transient( 'instagram_feed' ) === false ) {

                if ( empty( $username ) ) {
                    $hashtag = ( strpos( $hashtag, '#' ) === false ) ? $hashtag : substr( $hashtag, 1 );
                    $instagram_url = 'https://api.instagram.com/v1/tags/' . $hashtag . '/media/recent?access_token=' . $access_token;
                } else {
                    $instagram_url_user = 'https://api.instagram.com/v1/users/search?q=' . $username . '&access_token=' . $access_token;
                    $user = json_decode( file_get_contents( $instagram_url_user ) );
                    if ( $user->data[0]->id )
                        $user_id = $user->data[0]->id;
                    else
                        return;

                    $instagram_url = 'https://api.instagram.com/v1/users/' . $user_id . '/media/recent?access_token=' . $access_token;
                }

                $instagram_feed = json_decode( file_get_contents( $instagram_url ) );

                //Check if any error:
                if ( $instagram_feed->meta->code == 400 )
                    return;

                foreach ( $instagram_feed->data as $image ) {
                    $feed[] = array(
                        'id'              => $image->id,
                        'created_at'      => $image->created_time,
                        'link'            => $image->link,
                        'user'            => $image->user->username,
                        'image_thumbnail' => $image->images->thumbnail->url,
                        'image'           => $image->images->standard_resolution->url
                    );
                }

                set_transient( 'instagram_feed', $feed, 60*15 ); //Store the feed for 15 minutes

            } else {
                //Get feed from database instead of doing a request and reach the limitation
                $feed = get_transient( 'instagram_feed' );
            }

            echo $before_widget;

            if ( sizeof( $feed ) > 0 ):
                $limit_number = ( sizeof( $feed ) > $limit_number ) ? $limit_number : sizeof( $feed );
                ?>
                <?php if ( ! empty( $title ) ) : ?>
                    <?php echo $before_title . $title . $after_title; ?>
                <?php endif; ?>
                <?php if ( $slide_images ) : ?>
                <div class="slide">
                <?php endif; ?>
                    <ul class="feed">
                        <?php for ( $i = 0; $i < $limit_number; $i++ ) : ?>
                            <li>
                                <div class="thumbnail"><a href="<?php echo $feed[$i]['link']; ?>" target="_blank"><img src="<?php echo $feed[$i]['image_thumbnail']; ?>" /></a></div>
                                <span class="date"><?php echo $this->timeAgo( $feed[$i]['created_at'] ); ?></span>
                            </li>
                        <?php endfor; ?>
                    </ul>
                <?php if ( $slide_images ) : ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <p>No images found for <?php echo empty( $username ) ? '#' . $hashtag : 'username ' . $username; ?></p>
            <?php endif;

            echo $after_widget;
        }

        private function timeAgo( $time ) {
            $right_now = time();

            if ( ! $time )
                return;

            $diff = abs( $right_now - $time );

            $second = 1;
            $minute = $second * 60;
            $hour = $minute * 60;
            $day = $hour * 24;
            $week = $day * 7;

            if ( $diff < $second * 2 )
                return "right now";

            if ( $diff < $minute )
                return floor( $diff / $second ) . " secondes ago";

            if ( $diff < $minute * 2 )
                return "about 1 minute ago";

            if ( $diff < $hour )
                return floor( $diff / $minute ) . " minutes ago";

            if ( $diff < $hour * 2 )
            return "about 1 hour ago";

            if ( $diff < $day )
            return floor( $diff / $hour ) . " hours ago";

            if ( $diff > $day && $diff < $day * 2 )
                return "yesterday";

            if ( $diff < $day * 365 )
                return floor( $diff / $day) . " days ago";
            else
                return "over a year ago";
        }

        function form( $instance ) {
            $instance = wp_parse_args( (array) $instance, array( 'username' => '', 'hashtag' => '', 'access_token' => '', 'limit_number' => '', 'slide_images' => '' ) );

            $title          = esc_attr( isset( $instance['title'] ) ? $instance['title'] : '' );
            $username       = esc_attr( isset( $instance['username'] ) ? $instance['username'] : '' );
            $hashtag        = esc_attr( isset( $instance['hashtag'] ) ? $instance['hashtag'] : '' );
            $slide_images   = esc_attr( isset( $instance['slide_images'] ) ? $instance['slide_images'] : '' );
            $access_token   = esc_attr( isset( $instance['access_token'] ) ? $instance['access_token'] : '' );
            $limit_number   = esc_attr( isset( $instance['limit_number'] ) ? $instance['limit_number'] : '5' );
            ?>
            <p>
                <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ) ?></label> <em>(not visible if empty)</em>
                <input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $title; ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'username' ); ?>"><?php _e( 'Username:' ) ?></label>
                <input type="text" class="widefat" id="<?php echo $this->get_field_id( 'username' ); ?>" name="<?php echo $this->get_field_name( 'username' ); ?>" value="<?php echo $username; ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'hashtag' ); ?>"><?php _e( 'Hashtag:' ) ?></label>
                <input type="text" class="widefat" id="<?php echo $this->get_field_id( 'hashtag' ); ?>" name="<?php echo $this->get_field_name( 'hashtag' ); ?>" value="<?php echo $hashtag; ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'limit_number' ); ?>"><?php _e( 'Limit to:' ) ?></label> <em>(default: 5)</em>
                <input type="text" class="widefat" id="<?php echo $this->get_field_id( 'limit_number' ); ?>" name="<?php echo $this->get_field_name( 'limit_number' ); ?>" value="<?php echo $limit_number; ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'access_token' ); ?>"><?php _e( 'Access Token:' ) ?></label>
                <input type="text" class="widefat" id="<?php echo $this->get_field_id( 'access_token' ); ?>" name="<?php echo $this->get_field_name( 'access_token' ); ?>" value="<?php echo $access_token; ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'slide_images' ); ?>"><input type="checkbox" name="<?php echo $this->get_field_name( 'slide_images' ); ?>" <?php echo ( $slide_images ) ? 'checked="checked"': ''; ?> /><?php _e( 'Slide Images' ) ?></label>
            </p>
            <?php
        }

        function update( $new_instance, $old_instance ) {
            $instance = $old_instance;

            delete_transient( 'instagram_feed' );

            $instance['username']         = strip_tags( $new_instance['username'] );
            $instance['hashtag']          = strip_tags( $new_instance['hashtag'] );
            $instance['title']            = strip_tags( $new_instance['title'] );
            $instance['slide_images']     = strip_tags( $new_instance['slide_images'] );
            $instance['limit_number']     = intval( $new_instance['limit_number'] );
            $instance['access_token']     = strip_tags( $new_instance['access_token'] );
            return $instance;
        }
    }
}

?>