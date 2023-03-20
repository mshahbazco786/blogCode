<?php 
function enqueue_ajax_scripts() {
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'custom-ajax', get_template_directory_uri() . '/js/custom-ajax.js', array( 'jquery' ), '1.0', true );
    wp_localize_script( 'custom-ajax', 'customAjax', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'custom-ajax-nonce' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'enqueue_ajax_scripts' );


function load_posts() {
    check_ajax_referer( 'custom-ajax-nonce', 'nonce' );

    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 5,
        'paged'          => $_POST['page'],
    );

    $query = new WP_Query( $args );

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            get_template_part( 'content', 'excerpt' ); // change to your own content template part
        }
        wp_reset_postdata();
    }

    die();
}
add_action( 'wp_ajax_load_posts', 'load_posts' );
add_action( 'wp_ajax_nopriv_load_posts', 'load_posts' );

?>
<script>
    jQuery(document).ready(function($) {
    var page = 1;
    var loading = false;
    var $window = $(window);
    var $content = $('.posts');

    function load_posts() {
        $.ajax({
            type       : 'POST',
            data       : {
                action  : 'load_posts',
                nonce   : customAjax.nonce,
                page    : page,
            },
            url        : customAjax.ajaxUrl,
            beforeSend: function() {
                if( ! loading ) {
                    loading = true;
                    $('.load-more').show();
                }
            },
            success    : function( data ) {
                if( data ) {
                    $content.append( data );
                    loading = false;
                    $('.load-more').hide();
                    page++;
                } else {
                    $('.load-more').hide();
                }
            },
            error     : function(xhr, status, error) {
                console.log(xhr.responseText);
            }
        });
    }

    $window.scroll(function() {
        var content_offset = $content.offset();
        if ($window.scrollTop() + $window.height() > content_offset.top + $content.height() && ! loading ) {
            load_posts();
        }
    });

    $('.load-more').click(function() {
        load_posts();
    });
});

</script>
<?php