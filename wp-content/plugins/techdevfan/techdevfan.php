<?php
/**
* Mobile apps solutions for wordpress.
*
* @category  CategoryName
* @package   TechDevFan
* @author    admin <mwshubham@gmail.com>
* @copyright 2017-2018 TechDevFan.com
* @license   GPL-2.0+ https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html
* @link      CONF_Author_Link
*
* Plugin Name: TechDevFan
* Plugin URI: http://www.techdevfan.com
* Description: Mobile apps solutions for wordpress.
* Version: 1.0.0
* Author: Admin
* Author URI: http://www.techdevfan.com
* License: GPL2
*/


$welcome_message = "Welcome to TechDevFan";

add_action('rest_api_init', 'tdf_add_custom_post_data');
/**
* Adds Custom post data to api's
*
* @return void
*/
function tdf_add_custom_post_data()
{
    // Adding featured image 'thumbnail' for each post
    register_rest_field(
        'post',
        'featured_image_full',
        array(
        'get_callback' => 'get_featured_image_full',
        'update_callback' => null,
        'schema' => null
        )
    );

    register_rest_field(
        'post',
        'featured_image_thumb_standard',
        array(
        'get_callback' => 'get_featured_image_thumb_standard',
        'update_callback' => null,
        'schema' => null
        )
    );
}

/**
* Adds Custom post data to api's
*
* @param post    $post           post object
* @param field   $field_name     field_name
* @param Request $request        request
*
* @return featured image of the post
*/
function get_featured_image_thumb_standard($post, $field_name, $request)
{
    return wp_get_attachment_image_src($post['featured_media'], 'thumb-standard', false)[0];
}



/**
* Adds Custom post data to api's
*
* @param post    $post           post object
* @param field   $field_name     field_name
* @param Request $request        request
*
* @return featured image of the post
*/
function get_featured_image_full($post, $field_name, $request)
{
    return wp_get_attachment_image_src($post['featured_media'], 'full', false)[0];
}


/**
* Filter a response to include only a subset of response fields
*
* @param array $response response
* @param array $fields   fields
*
* @return filtered_data
*/
function filter_response_by_fields($response, $fields)
{
    if (empty($fields) || 0 === count($fields)) {
        return $response;
    }

    $filtered_data = array();
    foreach ($fields as $field) {
        if (isset($response->data[ $field ])) {
            $filtered_data[ $field ] = $response->data[ $field ];
        }
    }

    return $filtered_data;
}


/**
* Filter a response to include only a subset of response fields
*
* @param array $response response
* @param array $object   object
* @param array $request  request
*
* @return response
*/
function tdf_custom_json_fields($response, $object, $request)
{
    $fields = $request->get_param('fields');
    if ($fields) {
        $fields = ! empty($fields) ? explode(',', $fields) : array();
        $filtered_response = filter_response_by_fields($response, $fields);
        return $filtered_response;
    }
    return rest_ensure_response($response);
}

/**
* Url: https://developer.wordpress.org/reference/functions/add_filter/
*/
add_filter('rest_prepare_post', 'tdf_custom_json_fields', 12, 3);
add_filter('rest_prepare_category', 'tdf_custom_json_fields', 12, 3);
add_filter('rest_prepare_page', 'tdf_custom_json_fields', 12, 3);
/*no such filter found*/
// add_filter('rest_prepare_tag', 'tdf_custom_json_fields', 12, 3);





add_action(
    'rest_api_init',
    function () {
        register_rest_route(
            'techdevfan/v1',
            '/favoritePost',
            array(
            'methods' => 'GET',
            'callback' => 'get_favorite_post_data',
            )
        );
    }
);

/**
* Grab latest post title by an author!
*
* @param array $data Options for the function.
* @return array favorite post based on request $data
*/
function get_favorite_post_data(WP_REST_Request $request)
{


    $responseData = array();
    /* Argument might create problem as number post work for the current page only */
    $args = array(
    "numberposts"   => 10,
    "include"       => $request['postIds']
    );

    $post_arr = get_posts($args);

    foreach ($post_arr as $post) {
        $title["rendered"] = $post -> post_title;
        $content["rendered"] = $post -> post_content;
    // $excerpt["rendered"] = $post -> post_excerpt;
        $excerpt["rendered"] = get_excerpt_by_id($post -> ID);


        $categories_arr = get_the_category($post -> ID);
        $categoryIds = array();
        foreach ($categories_arr as $category) {
            array_push($categoryIds, $category->term_id);
        }

        $tagIds = array();
        $post_tags = get_the_tags($post -> ID);
        if ($post_tags) {
            foreach ($post_tags as $tag) {
                array_push($tagIds, $tag->term_id);
            }
        }


        array_push($responseData, array(
        "id" => $post -> ID,
        "date" => $post -> post_date,
        "title" => $title,
        "content" => $content,
        "excerpt" => $excerpt,
        "author" => $post -> post_author,
        "link" => null,
        "featured_image_full" => get_post_thumbnail_id($post),
        "featured_image_thumb_standard" => wp_get_attachment_image_src(get_post_thumbnail_id($post), 'thumbnail', true)[0],
        "categories" => $categoryIds,
        "tags" => $tagIds,
        "type" => get_post_type($post -> ID),
        "status" => get_post_status($post -> ID)
        ));
    }

    return $responseData;
}

/**
* get excerpt by id
*
* @param string $post_id id of the post
* @return the excerpt of the post or create a new one from the content of the post
*/

function get_excerpt_by_id($post_id)
{
    $the_post = get_post($post_id); //Gets post ID
    $the_excerpt = ($the_post ? $the_post->post_content : null); //Gets post_content to be used as a basis for the excerpt
    $excerpt_length = 35; //Sets excerpt length by word count
    $the_excerpt = strip_tags(strip_shortcodes($the_excerpt)); //Strips tags and images
    $words = explode(' ', $the_excerpt, $excerpt_length + 1);

    if (count($words) > $excerpt_length) :
        array_pop($words);
        array_push($words, '…');
        $the_excerpt = implode(' ', $words);
    endif;

    return $the_excerpt;
}


add_action(
    'rest_api_init',
    function () {
        register_rest_route(
            'techdevfan/v1',
            '/config',
            array(
            'methods' => 'GET',
            'callback' => 'get_config_data',
            )
        );
    }
);

/**
* Grab latest post title by an author!
*
* @param array $data Options for the function.
* @return array config data from user
*/
function get_config_data($data)
{
    $responseData = array();
    $responseData["isAdEnabled"] = true;
    $responseData["isHideCategoryWithNoPost"] = true;
    $responseData["isTagsEnabled"] = true;
    $responseData["isShowMinReadTime"] = true;
    $responseData["isShowCustomPages"] = true;
    $responseData["postCount"] = wp_count_posts()->publish;
    return $responseData;
}

/**
* Admin panel related stuff
*/


/**
* @internal never define functions inside callbacks.
* these functions could be run multiple times; this would result in a fatal error.
*/

/**
* custom option and settings
*/
function tdf_settings_init()
{
    register_setting('tdf', 'tdf_options');
    
    add_settings_section(
        'tdf_section_developers',
        __('Configuration', 'tdf'),
        'tdf_section_developers_cb',
        'tdf'
    );
    
    // register a new field in the "tdf_section_developers" section, inside the "tdf" page
    add_settings_field(
        'tdf_field_pill', // as of WP 4.6 this value is used only internally
        // use $args' label_for to populate the id inside the callback
        __('Pill', 'tdf'),
        'tdf_field_pill_cb',
        'tdf',
        'tdf_section_developers',
        [
        'label_for' => 'tdf_field_pill',
        'class' => 'tdf_row',
        'tdf_custom_data' => 'custom',
        ]
    );
}

/**
* register our tdf_settings_init to the admin_init action hook
*/
add_action('admin_init', 'tdf_settings_init');

function tdf_section_developers_cb($args)
{
    ?>
    <p id="<?php echo esc_attr($args['id']); ?>"><?php esc_html_e('Change configuration that can be used in the application here.', 'tdf'); ?></p>
    <?php
}

// pill field cb

// field callbacks can accept an $args parameter, which is an array.
// $args is defined at the add_settings_field() function.
// wordpress has magic interaction with the following keys: label_for, class.
// the "label_for" key value is used for the "for" attribute of the <label>.
// the "class" key value is used for the "class" attribute of the <tr> containing the field.
// you can add custom key value pairs to be used inside your callbacks.
function tdf_field_pill_cb($args)
{
    // get the value of the setting we've registered with register_setting()
    $options = get_option('tdf_options');
    // output the field
    ?>
    <select id="<?php echo esc_attr($args['label_for']); ?>"
        data-custom="<?php echo esc_attr($args['tdf_custom_data']); ?>"
        name="tdf_options[<?php echo esc_attr($args['label_for']); ?>]"
        >
        <option value="red" <?php echo isset($options[ $args['label_for'] ]) ? ( selected($options[ $args['label_for'] ], 'red', false) ) : ( '' ); ?>>
            <?php esc_html_e('red pill', 'tdf'); ?>
        </option>
        <option value="blue" <?php echo isset($options[ $args['label_for'] ]) ? ( selected($options[ $args['label_for'] ], 'blue', false) ) : ( '' ); ?>>
            <?php esc_html_e('blue pill', 'tdf'); ?>
        </option>
    </select>
    <p class="description">
        <?php esc_html_e('You take the blue pill and the story ends. You wake in your bed and you believe whatever you want to believe.', 'tdf'); ?>
    </p>
    <p class="description">
        <?php esc_html_e('You take the red pill and you stay in Wonderland and I show you how deep the rabbit-hole goes.', 'tdf'); ?>
    </p>
    <?php
}

/**
* top level menu
*/
function tdf_options_page()
{
    // add top level menu page
    add_menu_page(
        'TechDevFan Options',
        'TechDevFan',
        'manage_options',
        'tdf',
        'tdf_options_page_html'
    );
}

/**
* register our tdf_options_page to the admin_menu action hook
*/
add_action('admin_menu', 'tdf_options_page');

/**
* top level menu:
* callback functions
*/
function tdf_options_page_html()
{
    // check user capabilities
    if (! current_user_can('manage_options')) {
        return;
    }

// add error/update messages

// check if the user have submitted the settings
// wordpress will add the "settings-updated" $_GET parameter to the url
    if (isset($_GET['settings-updated'])) {
    // add settings saved message with the class of "updated"
        add_settings_error('tdf_messages', 'tdf_message', __('Settings Saved', 'tdf'), 'updated');
    }

// show error/update messages
    settings_errors('tdf_messages');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <!-- <h1>"TEST HEADING"</h1> -->
    <form action="options.php" method="post">
        <?php
        // output security fields for the registered setting "tdf"
        settings_fields('tdf');
        // output setting sections and their fields
        // (sections are registered for "tdf", each field is registered to a specific section)
        do_settings_sections('tdf');
        // output save settings button
        submit_button('Save Settings');
        ?>
    </form>
</div>
<?php
}

