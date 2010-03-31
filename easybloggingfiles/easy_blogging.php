<?php
if (!class_exists('easy_admin')) {
    class easy_admin {
        //This is where the class variables go, don't forget to use @var to tell what they're for
        /**
        * @var string The options string name for this plugin
        */
        var $optionsName = 'easy_admin_options';
        
        /**
        * @var string $localizationDomain Domain used for localization
        */
        var $localizationDomain = "easy_admin";
        
        /**
        * @var string $pluginurl The path to this plugin
        */ 
        var $thispluginurl = '';
        /**
        * @var string $pluginurlpath The path to this plugin
        */
        var $thispluginpath = '';
        
        /**
        * @desc string $current_url Stores the current page's url
        */
        var $currenturl = '';
        
        /**
        * @desc string $current_url Stores the current page's url
        */
        var $currenturl_with_querystring = '';
            
        /**
        * @var array $options Stores the options for this plugin
        */
        var $options = array();
        
        //Translation helper vars
        var $trans_widget = '';
        var $trans_widgets = '';
        var $trans_item = '';
        var $trans_customize = '';
        
        //Class Functions
        /**
        * PHP 4 Compatible Constructor
        */
        function easy_admin(){$this->__construct();}
        
        /**
        * PHP 5 Constructor
        */        
        function __construct(){
            //Language Setup
            $locale = get_locale();
            $mo = dirname(__FILE__) . "/languages/" . $this->localizationDomain . "-".$locale.".mo";
            load_textdomain($this->localizationDomain, $mo);

            //"Constants" setup
            if (defined('WPMU_PLUGIN_DIR') && strpos(__FILE__,WPMU_PLUGIN_DIR) === false) { //We're not in the WPMU Plugin Directory
                $this->thispluginpath = WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)).'/';
                $this->thispluginurl = WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)).'/';
            } else { //We are in the WPMU Plugin Directory
                $this->thispluginurl = WPMU_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)).'/';
                $this->thispluginurl = WPMU_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)).'/';
            }
            
            $this->currenturl_with_querystring = (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] : "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
            if (strstr($this->currenturl_with_querystring,'?') != '') {
                $urlary = explode('?',$this->currenturl_with_querystring);
                $this->currenturl = $urlary[0];
            } else $this->currenturl = $this->currenturl_with_querystring;
            
            //Initialize the options
            //This is REQUIRED to initialize the options when the plugin is loaded!
            $this->getOptions();
            
            //Translate a few words for later use (to get around the infinite recursion problem with the __ function in replace_text)
            $this->trans_widget = __('Widget', $this->localizationDomain);
            $this->trans_widgets = __('Widgets', $this->localizationDomain);
            $this->trans_item = __('Item', $this->localizationDomain);
            $this->trans_customize = __('Customize Design', $this->localizationDomain);
            
            //Actions        
            add_action("init", array(&$this,"init"),4); //Set to 4 to allow other plugins to run before this one, just in case
            add_action("admin_init", array(&$this,"first_login"), 1); //We want to check if this is the first login before anything else, so we can hijack the files if necessary
            add_action("in_admin_footer", array(&$this,"admin_footer"));

            wp_enqueue_style( 'easy-admin-both-css', $this->thispluginurl.'css/easy.admin.both.css');
        }
        
        /**
        * Initialization function, enqueues necessary JS and CSS files and sets up the Easy Admin area, if necessary
        */
        function init() {
            if (!is_admin()) return; //If we're not in the admin area, this isn't needed
            global $user_ID, $pagenow;
            
            //Check if the user clicked the on/off link in the footer
            if ($_GET['frame']) {
                require_once('frame.php');
                die();
            }
            
            if ($pagenow == 'media-upload.php' || $pagenow == 'admin-ajax.php') {
                return; //We don't want to do a thing if this is the media-upload or admin-ajax page
            } 
            
            if ($_GET['easyadmin']) {
                switch ($_GET['easyadmin']) {
                    case 'on':
                        //echo 'enabling';
                        $this->options['disabled'][$user_ID] = false;
                        break;
                    case 'off':
                    default:
                        //echo 'disabling';
                        $this->options['disabled'][$user_ID] = true;
                        break;
                }
                $this->saveAdminOptions();
            }
            
            ### Remove before releasing, for testing only!
            if ($_GET['start']==1) {
                unset($this->options['disabled'][$user_ID]);
                $this->saveAdminOptions();

                wp_redirect($pagenow);
            }
            
            //Allow other scripts to stop Easy Admin from running
            $do_init = apply_filters('run_easy_admin_head',true);
            if (!$do_init) return;
            
            //The jQuery UI CSS is required for the tabs & the UI State Highlight, so we need to add it here
            wp_enqueue_style( 'jquery-custom-ui-tabs', $this->thispluginurl.'css/jquery.ui.tabs.css');            
            
            if (!$this->options['disabled'][$user_ID]) {
                wp_enqueue_style( 'easy-admin-css', $this->thispluginurl.'css/easy.admin.css'); //Enqueue the easy admin area css
                
                $doing_ajax = false;
                if (defined('DOING_AJAX')) {
                    $doing_ajax = DOING_AJAX;
                }
                
                if ($this->is_dash() && !$doing_ajax) { //If this page isn't in a tab, and it's not the admin ajax page, then hijack it via the admin_init function
                    add_action("admin_init", array(&$this,"admin_area_init"), 4); //We want to fire this before the default plugin init actions, but not at 1, in case another plugin needs to fire first
                    add_action("admin_head", array(&$this,"admin_head"));
                    
                    wp_enqueue_script('jquery-ui-tabs');
                } else if (!$this->is_dash()) { //If we're in a tabbed page
                    add_action('admin_head',array(&$this,'admin_head_resize'));
                    add_action('do_meta_boxes',array(&$this,'kill_meta_boxes'));

                    if ($pagenow == 'widgets.php' || $pagenow == 'themes.php') {
                        add_action('sidebar_admin_page', array(&$this, 'custom_header_addition'));
                        add_filter('gettext', array(&$this, 'replace_text'), 10, 3);
                    }
                }
                //Do this regardless of whether or not we're in a tab
                add_action('admin_head', array(&$this,'admin_head_css'));
                remove_action( 'admin_footer', 'bp_core_admin_bar');
            }
        }
        
        function custom_header_addition() {
            if ($GLOBALS['custom_image_header']) { //Only show this area if the current theme has a custom image header
                echo '<div id="iframe"><iframe id="custom-image-header-php" scrolling="no" src="' . admin_url('themes.php') . '?page=custom-header" style="height: 600px; width: 100%; border: none;" frameborder="0"></iframe></div>';
            }
        }
        
        /**
        * Replaces any instance of the word 'Widget' with 'Item', both for 'Widget', and 'widget'
        */
        function replace_text($transtext, $normtext, $domain) {
            //### Left in because for some reason __ won't work in here
            if ($transtext == $this->trans_widgets) { //The only place 'Widgets' shows up by itself that we care about is in the <h2> tag, so we know what to change it to
                return $this->trans_customize;
            }
            
            $transtext = str_replace($this->trans_widget, $this->trans_item, $transtext);
            $transtext = str_replace(strtolower($this->trans_widget), strtolower($this->trans_item), $transtext);

            return $transtext;
        }

        /**
        * Returns whether or not this is the user's first login (aka - there's nothing in the $this->options['disabled'][$user_ID] variable
        */
        function is_started() {
            global $user_ID;
            return isset($this->options['disabled'][$user_ID]);
        }
        
        /**
        * @desc Adds the necessary JS and CSS to the admin header for the easy admin area
        * The live_resize function was modified from the iResize function here: http://css-tricks.com/snippets/jquery/fit-iframe-to-content/
        * 
        * 
        * This is an example of how to use the easy_admin_tab_options filter:
        * 
        *
        function easy_admin_tab_options($tab_options) {
            $tab_options['disabled'] = "[0,1,2,3,4,5,6,7,8,9,10]";
            $tab_options['load'] = "function (event,ui) {
                jQuery('a[href=\"#' + ui.panel.id + '\"]').html('<span>Quick Start</span>');
                var url = '" . admin_url('?wizard-step=0') . "';
                jQuery('#' + ui.panel.id + ' iframe').attr('src',url);
            }";
            return $tab_options;
        }
        */
        function admin_head() {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function(){
                    var anchor = jQuery(document).attr('location').hash; // the anchor in the URL
                    var index = jQuery('#easy_admin_tabs li a').index(jQuery(anchor)); // in tab index of the anchor in the URL
                    if (index < 0) { index = 0; }
                    jQuery('#easy_admin_tabs').tabs({
                        <?php
                        $tab_options = apply_filters('easy_admin_tab_options', array('selected'=>index));
                        $i=0;
                        foreach ($tab_options as $key=>$value) {
                            if ($i>0) {
                                echo ",\r\n";
                            } else $i = 1;
                            echo "'$key': $value";
                        }
                        ?>
                        }); // select the tab

                    jQuery('#easy_admin_tabs').bind('tabsshow', function(event, ui) { // change the url anchor when we click on a tab
                        document.location.hash = jQuery('#easy_admin_tabs li a[href="#' + ui.panel.id + '"]').attr('id');
                        jQuery( 'html, body' ).animate( { scrollTop: 0 }, 0 );
                    });
                });
            </script>
            <?php
        }
        
        /**
        * The jQuery iframe resizing function that this function is based on found here: http://stackoverflow.com/questions/153152/resizing-an-iframe-based-on-content
        */
        function admin_head_resize() {
            global $pagenow;
            $hash = str_replace('.','-',$pagenow);
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function() {
                    var theDiv = jQuery("iframe", parent.document.body).parent();
                    
                    if (theDiv.length == 0) { //We're not in an iframe, redirect to the dashboard
                        window.location.replace('<?=admin_url("index.php#$hash"); ?>');
                    } else { //We are in an iframe, resize & do other actions
                        jQuery("iframe", parent.document.body).css('height','100%');
                        var height = jQuery(document).height();
                        if (height < 500) height = 500;
                        theDiv.height(height); //Update the height of the parent div, so there isn't an iframe scrollbar
                        
                        <?php if ($pagenow == 'edit.php' || $pagenow == 'edit-pages.php') { ?>
                        jQuery('.row-actions').find('.inline').remove();
                        <?php } else if ($pagenow == 'themes.php') { ?>                        
                        jQuery('.tb-theme-preview-link').live('click',function () {
                            document.location.href = jQuery(this).attr('href');
                            return false;
                        });
                        <?php } ?>
                    }
                });  
            </script>
            <?php
        }
        
        /**
        * If this is not the dashboard (which means it's likely in a tab) then hide the update-nag, wphead, and footer areas
        */
        function admin_head_css() {
            if (!$this->is_dash()) { ?>
            <style type="text/css">
                #update-nag { display: none; }
                #wphead { display: none; }
                #footer { display: none; }
            </style>
            <?php
            }
        }
        
        /**
        * Is this page the dashboard?
        */
        function is_dash() {
             global $pagenow;
             return (is_admin() && $pagenow == 'index.php');
        }
        
        /**
        * Removes all meta boxes from the $wp_meta_boxes array
        */
        function kill_meta_boxes() {
            global $wp_meta_boxes;
            
            unset($wp_meta_boxes['post']['normal']);
            unset($wp_meta_boxes['page']['normal']);
            unset($wp_meta_boxes['page']['side']['core']['pageparentdiv']);
        }
        
        /**
        * Add a link to the footer to turn on/off the easy admin area
        */
        function admin_footer() {
            global $user_ID, $pagenow;
            
            $url = $this->currenturl_with_querystring;
            if (strpos($url,'?') > 0)
                $connector = '&';
            else
                $connector = '?';

            ?><script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('#footer-left').after('<br/>&nbsp;');
                    $('#wphead-info').after('<?php
            if (!$this->options['disabled'][$user_ID])
                echo '<div id="admin_area_to_advanced" class="admin_area button"><a href="' . $this->currenturl_with_querystring . $connector . 'easyadmin=off">', __('Go to the Advanced Admin Area',$this->localizationDomain) . '</a></div>';
            else
                echo '<div id="admin_area_to_easy" class="admin_area button"><a href="' . $this->currenturl_with_querystring . $connector . 'easyadmin=on">', __('Go to the Easy Admin Area',$this->localizationDomain) . '</a></div>';
            ?>');
            <?php if (!$this->options['disabled'][$user_ID]) { ?>
                    $('#wphead-info').before('<div id="logout"><a href="<?php echo wp_logout_url() ?>" title="<?php _e('Log Out') ?>"><?php _e('Log Out'); ?></a></div>');
            <?php 
                      if ($pagenow == 'themes.php') { //This is inside [if (!$this->options['disabled'][$user_ID])] because we don't need to add it unless we're in the easy admin area'?>
                        $('#wpbody a:not(.thickbox, .activatelink, .submitdelete, .button, .updated a, .wizard_button, .page-numbers)').attr('target','_blank');
                        $(".add-new-h2").remove();
                        $(".theme-description, .action-links").next().remove();
            <?php     }
                    } ?>
                });                
            </script>
            <?php if (!$this->options['disabled'][$user_ID]) { ?>
                <style type="text/css">
                    body {
                        padding-top:0;
                    }
                </style>
            <?php
            }
        }
        
        /**
        *  Checks if this is the user's first login, and if so, hijacks the admin area and replaces it with the start page
        */
        function first_login() {
            if (!$this->is_started()) {
                wp_enqueue_style( 'easy-admin-css', $this->thispluginurl.'css/easy.admin.css'); //Enqueue the easy admin area css
                require_once('start.php');
                die();
            }
        }
        
        /**
        * Hijacks the admin area interface and replaces it with the Easy Admin area
        */
        function admin_area_init() {
            require_once('admin_area.php');
            die();
        }
        
        /**
        * @desc Retrieves the plugin options from the database.
        * @return array
        */
        function getOptions() {
            //Don't forget to set up the default options
            if (!$theOptions = get_option($this->optionsName)) {
                $theOptions = array('disabled'=>array());
                update_option($this->optionsName, $theOptions);
            }
            $this->options = $theOptions;
            
            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            //There is no return here, because you should use the $this->options variable!!!
            //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        }
        
        /**
        * @desc Saves the admin options to the database.
        */
        function saveAdminOptions(){
            return update_option($this->optionsName, $this->options);
        }

    } //End Class
    //instantiate the class
    if (is_admin()) {
        global $easy_admin_var;
        $easy_admin_var = new easy_admin();
    }
} //End if easy_admin class exists statement
?>