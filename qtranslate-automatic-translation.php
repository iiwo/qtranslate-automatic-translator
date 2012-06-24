<?php
/*
Plugin Name: QTranslate Automatic Translation Support
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Provides automatic translation to QTranslate
Version: 0.1
Author: Silvano Buback
Author URI: http://www.dentalmasterclub.com
License: GPL2
*/

if(!defined('WP_ADMIN')) {
    return;
}

require_once dirname( __FILE__ ) . '/translation.php';

add_action('admin_menu',                'qtransauto_admin_add_page' );
add_action('admin_init',                'qtransauto_admin_init' );
add_filter('admin_footer',              'qtransauto_add_javascript_languages_script' );

if ($_GET['error']) {
    add_action('admin_notices', 'qtransauto_admin_notice');
}

function qtransauto_admin_init(){
    register_setting( 'qtransauto_options', 'qtransauto', 'qtransauto_validate' );
    add_settings_section('default', 'Main Settings', 'qtransauto_section_text', 'qtransauto');
    add_settings_field('client_id', 'Client ID', 'qtransauto_clientid_gen', 'qtransauto');
    add_settings_field('client_secret', 'Client Secret', 'qtransauto_clientsecret_gen', 'qtransauto');

    // if( 'post.php' != $hook )
        // return;
    // die(plugins_url('assets/translator_editor.js', __FILE__));
    wp_enqueue_script( 'qtransauto_script', plugins_url('assets/translator_editor.js', __FILE__), array('jquery'), '1.0', true );
}

function qtransauto_add_javascript_languages_script() {
    global $q_config;
    echo('<script type="text/javascript">');
    echo('var qtransauto_languages = ' . json_encode($q_config['enabled_languages']) . ';');
    echo('</script>');

    // global $wp_scripts;
    // var_dump($wp_scripts);
    // die('fim');
}

function qtransauto_admin_add_page() {
    add_options_page('Automatic Translation', 'Translation', 'manage_options', 'qtransauto', 'qtransauto_options_page');
}

function qtransauto_options_page() {
?>
    <div class='wrap'>
        <div id="icon-options-general" class="icon32"><br></div>
        <h2>QTranslate - Automatic Translation</h2>
        <p>
            Microsoft Credentials is need for automatic translation.
            <br>More info on
            <a href="http://msdn.microsoft.com/en-us/library/hh454950.aspx" target="_blank">http://msdn.microsoft.com/en-us/library/hh454950.aspx</a>
        </p>
        <form action="options.php" method="post">
            <?php settings_fields('qtransauto_options'); ?>
            <?php do_settings_sections('qtransauto'); ?>

            <p>
                <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
            </p>
        </form>
    </div>

<?php
}

function qtransauto_admin_notice(){
        echo '<div class="error"><p>Error on validate credentials: ' . $_GET['error'] . '</p></div>';
}

function qtransauto_section_text() {
    echo '';
}

function qtransauto_clientid_gen() {
    $options = get_option('qtransauto');
    echo "<input id='clientid' name='qtransauto[client_id]' size='40' type='text' value='{$options['client_id']}' />";
}

function qtransauto_clientsecret_gen() {
    $options = get_option('qtransauto');
    echo "<input id='clientsecret' name='qtransauto[client_secret]' size='40' type='text' value='{$options['client_secret']}' />";
}

function qtransauto_validate($input) {
    $options = get_option('qtransauto');
    $options['client_id'] = trim($input['client_id']);
    $options['client_secret'] = trim($input['client_secret']);
    
    //Create the Translator Object.
    try {
        $translatorObj = new HTTPTranslator($options['client_id'], $options['client_secret']);
        $trad = $translatorObj->translate("en", "en", "only_for_test");
    } catch (Exception $e) {
        $goback = add_query_arg( 'error', urlencode($e->getMessage()), wp_get_referer() ); 
        wp_redirect( $goback );
        exit();
    }
    
    # success. Remove error message
    $_REQUEST['_wp_http_referer'] = add_query_arg( 'error', false, wp_get_referer() );
    return $options;
}

?>



