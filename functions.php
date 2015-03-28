<?php
/*********************************************************************************************

Theme functions file.

Contents:

    1. GLOBAL VARS & TIMBER SET UP
    2. WORDPRESS SETUP FUNCTIONS
    3. ADMIN FUNCTIONS
    4. CUSTOM FUNCTIONS

*********************************************************************************************/

/*
*   Global Variables
*   Set up global variables for the site url and the assets folder url to reduce calls
*/

$GLOBALS['home'] = home_url() . '/';
$GLOBALS['assets'] = '/assets/';
$GLOBALS['current_user'] = wp_get_current_user();

// Set up Timber

Timber::$dirname = 'templates';

add_filter('get_twig', 'add_to_twig');
add_filter( 'timber_context', 'add_to_context' );

function add_to_context( $data ) {
    $user = new TimberUser();

    $data['home'] = home_url() . '/';
    $data['assets'] = '/assets/';
    $data['wp_title'] = TimberHelper::function_wrapper( 'wp_title', array( '|', true, 'right' ) );
    $data['menu'] = new TimberMenu();
    $data['user'] = (!empty($user->id)) ? $user : false;
    $data['profile_url'] = TimberHelper::function_wrapper('get_edit_user_link', $user->id);
    $data['logout_url'] = TimberHelper::function_wrapper('wp_logout_url', $_SERVER['REQUEST_URI']);

    return $data;
}

function add_to_twig($twig) {
    // this is where you can add your own fuctions to twig
    $twig->addFilter('js_tag', new Twig_Filter_Function('js_tag'));
    $twig->addFilter('css_tag', new Twig_Filter_Function('css_tag'));
    $twig->addFilter('pre', new Twig_Filter_Function('pre_tag'));
    return $twig;
}

function js_tag($text) {
    $str = '<script src="'.$GLOBALS['assets'].'js/'.$text.'"></script>';
    return $str;
}

function css_tag($text) {
    $str = '<link rel="stylesheet" href="'.$GLOBALS['assets'].'css/'.$text.'" type="text/css" media="all">';
    return $str;
}

function pre_tag($text) {
    $str = '<pre>'.$text.'</pre>';
    return $str;
}

/********************************************************************************************
*   1. WORDPRESS SETUP FUNCTIONS
*********************************************************************************************/

function update_post_terms ($post_id) {
    if ( $parent = wp_is_post_revision( $post_id ) )
        $post_id = $parent;
    $post = get_post( $post_id );
    if ( $post->post_type != 'post' )
        return;

    // add a tag
    // wp_set_post_terms( $post_id, 'new tag', 'post_tag', true );

    // add a category
    $categories = wp_get_post_categories( $post_id );
    $newcat    = get_term_by( 'name', 'Blog', 'category' );

    array_push( $categories, $newcat->term_id );
    wp_set_post_categories( $post_id, $categories );
}
add_action( 'wp_insert_post', 'update_post_terms' );

function fffunction_wp_setup() {
    // This theme styles the visual editor with editor-style.css to match the theme style.
    add_editor_style();
    // Adds RSS feed links to <head> for posts and comments.
    add_theme_support( 'automatic-feed-links' );
    // This theme uses wp_nav_menu() in one location.
    register_nav_menu( 'primary', 'Primary Menu' );
}
add_action( 'after_setup_theme', 'fffunction_wp_setup' );

/*
Enqueues scripts and styles for front-end.
*/
function fffunction_wp_scripts_styles() {
    global $wp_styles;

    /*
    Adds JavaScript to pages with the comment form to support sites with threaded comments (when in use).
    */
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) )
        wp_enqueue_script( 'comment-reply' );

}
add_action( 'wp_enqueue_scripts', 'fffunction_wp_scripts_styles' );

/*
Creates a nicely formatted and more specific title element text for output in head of document, based on current view.
*/
function fffunction_wp_wp_title( $title, $sep ) {
    global $paged, $page;

    if ( is_feed() )
        return $title;

    // Add the site name.
    $title .= get_bloginfo( 'name' );

    // Add the site description for the home/front page.
    $site_description = get_bloginfo( 'description', 'display' );
    if ( $site_description && ( is_home() || is_front_page() ) )
        $title = "$title $sep $site_description";

    // Add a page number if necessary.
    if ( $paged >= 2 || $page >= 2 )
        $title = "$title $sep " . sprintf('Page %s', max( $paged, $page ) );

    return $title;
}
add_filter( 'wp_title', 'fffunction_wp_wp_title', 10, 2 );

/*
Registers our main widget area and the front page widget areas.
*/
function fffunction_wp_widgets_init () {
    register_sidebar( array(
        'name'          => 'Main Sidebar',
        'id'            => 'sidebar-1',
        'description'   => 'Appears on posts and pages except the optional Front Page template, which has its own widgets',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3>',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'fffunction_wp_widgets_init' );


/* Extends the default WordPress body class to denote:
1. Using a full-width layout, when no active widgets in the sidebar or full-width template.
2. Front Page template: thumbnail in use and number of sidebars for widget areas.
3. White or empty background color to change the layout and spacing.
4. Custom fonts enabled.
5. Single or multiple authors.
*/
function fffunction_wp_body_class ( $classes ) {
    $background_color = get_background_color();

    if (is_page_template('page-templates/front-page.php')) {
        $classes[] = 'template-front-page';
        if (has_post_thumbnail())
            $classes[] = 'has-post-thumbnail';
        if (is_active_sidebar( 'sidebar-2' ) && is_active_sidebar( 'sidebar-3' ))
            $classes[] = 'two-sidebars';
    }

    return $classes;
}
add_filter('body_class', 'fffunction_wp_body_class');

// Excerpts for Pages
add_post_type_support('page', 'excerpt');

// Set default values for the upload media box
function default_post_images () {
    update_option('image_default_align', 'none' );
    update_option('image_default_link_type', 'none' );
    update_option('image_default_size', 'Large' ); // edit if needs be
}
add_action('after_setup_theme', 'default_post_images');

// Redirects empty search page to search.php rather than index.php
function my_request_filter ($query_vars) {
    if( isset( $_GET['s'] ) && empty( $_GET['s'] ) ) {
        $query_vars['s'] = " ";
    }
    return $query_vars;
}
add_filter('request', 'my_request_filter');



/********************************************************************************************************
*   2. ADMIN FUNCTIONS
*   Used to customise the admin area
*********************************************************************************************************/

/*
*   Remove Menu Items
*   Hides left menu items - uncomment to hide.
*/
function remove_menu_items() {
    global $menu;
    $restricted = array(
    //  __('Dashboard'),
    //  __('Posts'),
    //  __('Media'),
    //  __('Links'),
    //  __('Pages'),
    //  __('Appearance'),
    //  __('Tools'),
    //  __('Users'),
    //  __('Settings'),
    //  __('Comments'),
    //  __('Plugins')
    );
    end ($menu);
    while (prev($menu)) {
        $value = explode(' ',$menu[key($menu)][0]);
        if (in_array($value[0] != NULL?$value[0]:"" , $restricted)) {
            unset($menu[key($menu)]);
        }
    }
}
add_action('admin_menu', 'remove_menu_items');

/* Remove Dashboard Widgets */
function remove_dashboard_widgets () {
    global $wp_meta_boxes;
    unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
    unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
    unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);
    unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);
    unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
}
add_action('wp_dashboard_setup', 'remove_dashboard_widgets');

// Remove that junk from my wp_head
remove_action('wp_head', 'rsd_link'); // Removes the Really Simple Discovery link
remove_action('wp_head', 'feed_links', 2); // Removes the RSS feeds remember to add post feed maunally (if required) to header.php
remove_action('wp_head', 'wp_generator'); // Removes the WordPress version
remove_action('wp_head', 'index_rel_link'); // Removes the index page link
remove_action('wp_head', 'feed_links_extra', 3); // Removes all other RSS links
remove_action('wp_head', 'wlwmanifest_link'); // Removes the Windows Live Writer link
remove_action('wp_head', 'start_post_rel_link', 10, 0); // Removes the random post link
remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0); // Removes the shortlink
remove_action('wp_head', 'parent_post_rel_link', 10, 0); // Removes the parent post link
remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0); // Removes the next and previous post links
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head'); // Post relational links

/********************************************************************************************
*   3. CUSTOM FUNCTIONS
*   Used for repetitive tasks in the theme
*********************************************************************************************/

function get_top_level_term ($term, $taxonomy) {
    if ($term->parent == 0) return $term;
    $parent = get_term($term->parent, $taxonomy);
    return get_top_level_term($parent, $taxonomy);
}
