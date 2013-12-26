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

if (!defined('WP_ADMIN')) {
    return;
}

require_once dirname(__FILE__) . '/lib/MicrosoftTranslator/AccessTokenAuthentication.php';
require_once dirname(__FILE__) . '/lib/MicrosoftTranslator/HTTPTranslator.php';
require_once dirname(__FILE__) . '/lib/MicrosoftTranslator/Translate.php';

add_action('admin_menu', 'qtransauto_admin_add_page');
add_action('admin_init', 'qtransauto_admin_init');
add_filter('admin_footer', 'qtransauto_add_javascript_languages_script');

if ($_GET['error']) {
    add_action('admin_notices', 'qtransauto_admin_notice');
}

function qtransauto_admin_init() {
    register_setting('qtransauto_options', 'qtransauto', 'qtransauto_validate');
    add_settings_section('default', 'Main Settings', 'qtransauto_section_text', 'qtransauto');
    add_settings_field('client_id', 'Client ID', 'qtransauto_clientid_gen', 'qtransauto');
    add_settings_field('client_secret', 'Client Secret', 'qtransauto_clientsecret_gen', 'qtransauto');
}

function qtransauto_add_javascript_languages_script() {
    global $q_config;
    echo('<script type="text/javascript">');
    echo('var qtransauto_languages = ' . json_encode($q_config['enabled_languages']) . ';');
    echo('</script>');
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

function qtransauto_admin_notice() {
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
        $config = array('clientID' => $options['client_id'], 'clientSecret' => $options['client_secret']);
        $translator = new \MicrosoftTranslator\Translate($config);
        $translator->translate("only_for_test", "en", "en");
    } catch (Exception $e) {
        $goback = add_query_arg('error', urlencode($e->getMessage()), wp_get_referer());
        wp_redirect($goback);
        exit();
    }

    # success. Remove error message
    $_REQUEST['_wp_http_referer'] = add_query_arg('error', false, wp_get_referer());
    return $options;
}

function qtransauto_update_translations($data, $postarr) {
    global $q_config;
    $def_lang = $q_config['default_language'];
    
    $options = get_option('qtransauto');
    $config = array('clientID' => $options['client_id'], 'clientSecret' => $options['client_secret']);
    $translator = new \MicrosoftTranslator\Translate($config);
    
    $post_content = $data['post_content'];
    
    $arr = qtrans_split($post_content);
    foreach ($arr as $key => $value)
        if ($key != $def_lang)
            $arr[$key] = $translator->translate($arr[$def_lang], $key, $def_lang);            
    $data['post_content'] = qtrans_join($arr);

    return $data;
}

//uncomment if you want to autotranslate on each save/update 
//add_filter( 'wp_insert_post_data' , 'qtransauto_update_translations' , '99', 2 );

function qtransauto_translate($content, $all = true, $only = nil) {
    global $q_config;
    $def_lang = $q_config['default_language'];

    $options = get_option('qtransauto');
    $config = array('clientID' => $options['client_id'], 'clientSecret' => $options['client_secret']);
    $translator = new \MicrosoftTranslator\Translate($config);

    $arr = qtrans_split($content);
    foreach ($arr as $key => $value)
        if ($key != $def_lang)
            $arr[$key] = $translator->translate($arr[$def_lang], $key, $def_lang);    
    $content = qtrans_join($arr);

    return $content;
}

function qtransauto_translate_ajax() {
    echo qtransauto_translate($_POST['qtransauto_content']);
    die();
}

add_action('wp_ajax_qtransauto_translate_ajax', 'qtransauto_translate_ajax');

function qtransauto_add_translate_all_button($context) {   
    $title = 'Translate All';
    //this works for symlinked plugins too - plugins_url() doesn't
    $plugin_url = WP_PLUGIN_URL . '/' . basename( __DIR__ );
    $context .= "<a title='{$title}' id='qtransauto_translate_all' href='#'><img id='qtranslate_preloader_icon' src='" . $plugin_url . '/assets/translate_preloader.gif' . "' style='display:none;' /><img id='qtranslate_all_icon'  src='" . $plugin_url.'/assets/translate_icon.png' . "'/>{$title}</a>";
    return $context;
}

add_action('media_buttons_context', 'qtransauto_add_translate_all_button');

function qtransauto_bind_translate_all() {
    ?>
    <script type="text/javascript" >
	    var QTRANSAUTO = {
			increment_translations: function() {
				this.translations_in_progress = this.translations_in_progress + 1;
			},
			decrement_translations: function() {
				this.translations_in_progress = this.translations_in_progress - 1;
				if (this.translations_in_progress < 0) {
					this.translations_in_progress = 0;
				}
			},
	    	translations_in_progress: 0
	    };
	
    	function qtransauto_translate_element(element, callback) {
			var element_id = element.attr('id');
			
            // translation parameters
            var data = {
                action: 'qtransauto_translate_ajax',
                qtransauto_content: element.val()
            };
			
    		callback = typeof callback !== 'undefined' ? callback : function(response) {
                jQuery('#' + element_id).val(response);
                var splitted = qtrans_split(response);
                jQuery.each(splitted, function(key, value) {
                    jQuery('#' + element_id + "_" + key).val(value);
                });
    		};
			
			QTRANSAUTO.increment_translations();
			jQuery.post(ajaxurl, data, function(response) {
				callback(response);
				QTRANSAUTO.decrement_translations();
				if (QTRANSAUTO.translations_in_progress == 0) {
	                jQuery('#qtranslate_preloader_icon').hide();
	            	jQuery('#qtranslate_all_icon').show();
			    }
			});
    	}

        //translate titles
        var data_title = {
            action: 'qtransauto_translate_ajax',
            qtransauto_content: jQuery("#title").attr("value")
        };
	
        jQuery(document).ready(function($) {
            jQuery('#qtransauto_translate_all').on('click', function() {

                qtransauto_translate_element(jQuery("#content"), function(response) {
                    jQuery("#content").attr("value", response);
                    jQuery("#content").text(response);
                    var splitted = qtrans_split(response);
                    var lang = qtrans_get_active_language();
                    var value_for_selected = splitted[lang];
                    jQuery("#qtrans_textarea_content").attr("value", value_for_selected);
                    tinyMCE.activeEditor.setContent(value_for_selected)
                });
				
				qtransauto_translate_element(jQuery("#title"));
				
				jQuery("#qtransauto_translate_all" ).trigger( "translation_started" );
            });
        });
    </script>
    <?php
}

add_action('admin_footer', 'qtransauto_bind_translate_all');
?>