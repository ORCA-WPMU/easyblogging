<?php
    $title = __('Easy Admin Area', $this->localizationDomain);
    
    wp_enqueue_script('jquery');
    wp_enqueue_script('hoverintent');
    wp_enqueue_script('cluetip', $this->thispluginurl.'js/cluetip-1.0.6/jquery.cluetip.js');
    wp_enqueue_style( 'cluetip', $this->thispluginurl.'js/cluetip-1.0.6/jquery.cluetip.css');
    
    //We need to set the $page_hook because if we don't, then a bug in WP will think this page is the custom-header page, load farbtastic, and break the JS on this page... Awesome.
    global $page_hook;
    $page_hook = 'easy_admin_dashboard';
    
    require_once(ABSPATH . 'wp-admin/admin-header.php');
    
    $supporter_rebrand = get_site_option( "supporter_rebrand" );
    if ($supporter_rebrand == '') {
        $supporter_rebrand = __('Supporter','supporter');
    }
?>
<script type="text/javascript">
    jQuery(document).ready(function(){
        jQuery(".tab_tooltip").cluetip({splitTitle: "|", cluetipClass: "rounded", dropShadow: false, arrows: true,
            hoverIntent: {
                sensitivity: 7,
                interval:     500
            }
        });
    });
</script>
   <div class="wrap">
        <div id="easy-admin-area">

            <div id="easy_admin_tabs" class="ui-tabs-nav">
            <?php 
                    /**
                    * NOTICE! If you're going to change the text in the title attributes, you can NOT use a period (.) in the middle of the text!
                    * There's a strange bug with the jQuery Tabs component that throws an error when a period shows up in the middle of the title attribute,
                    * which is what the cluetip plugin uses to generate the jQuery tooltips
                    */
            ?>
                <ul>
                <?php if (current_user_can('edit_posts')) { ?>
                    <li><a id="post-new-php" href="<?php bloginfo('wpurl'); ?>/wp-admin/?frame=post-new" class="tab_tooltip" title="<?php _e( 'New Post|Create a new post', $this->localizationDomain ) ?>"><span><?php _e( 'New Post', $this->localizationDomain ) ?></span></a></li>
                    <li><a id="edit-php" href="<?php bloginfo('wpurl'); ?>/wp-admin/?frame=edit" class="tab_tooltip" title="<?php _e( 'My Posts|Edit your posts', $this->localizationDomain ) ?>"><span><?php _e( 'My Posts', $this->localizationDomain ) ?></span></a></li>
                <?php }
                if (current_user_can('publish_pages')) { ?>
                    <li><a id="page-new-php" href="<?php bloginfo('wpurl'); ?>/wp-admin/?frame=page-new" class="tab_tooltip" title="<?php _e( 'New Page|Create a new page', $this->localizationDomain ) ?>"><span><?php _e( 'New Page', $this->localizationDomain ) ?></span></a></li>
                <?php }
                if (current_user_can('edit_pages')) { ?>
                    <li><a id="edit-pages-php" href="<?php bloginfo('wpurl'); ?>/wp-admin/?frame=edit-pages" class="tab_tooltip" title="<?php _e( 'My Pages|Edit your pages', $this->localizationDomain ) ?>"><span><?php _e( 'My Pages', $this->localizationDomain ) ?></span></a></li>
                <?php }
                if (current_user_can('moderate_comments') || current_user_can('edit_posts')) { ?>
                    <li><a id="edit-comments-php" href="<?php bloginfo('wpurl'); ?>/wp-admin/?frame=edit-comments" class="tab_tooltip" title="<?php _e( 'Comments|Manage the comments on your blog', $this->localizationDomain ) ?>"><span><?php _e( 'Comments', $this->localizationDomain ) ?></span></a></li>
                <?php }
                if (current_user_can('edit_themes')) { ?>                    
                    <li><a id="themes-php" href="<?php bloginfo('wpurl'); ?>/wp-admin/?frame=themes" class="tab_tooltip" title="<?php _e( 'Themes|Change to a different theme', $this->localizationDomain ) ?>"><span><?php if (function_exists('is_supporter')) { _e( 'Free Themes', $this->localizationDomain ); } else { _e( 'Manage Themes', $this->localizationDomain ); } ?></span></a></li>
                <?php }
                if (function_exists('is_supporter')) { //We only need to know if the supporter plugin is on, we need to display these tabs regardless of whether or not the user is a supporter, to encourage upgrades
                    if (current_user_can('edit_themes')) { ?>
                        <li><a id="premium-themes-php" href="<?php bloginfo('wpurl'); ?>/wp-admin/?frame=premium-themes" class="tab_tooltip" title="<?php echo sprintf(__( '%1$s Themes|View the %1$s themes', $this->localizationDomain ),$supporter_rebrand) ?>"><span><?php echo $supporter_rebrand . ' ' . __( 'Themes', $this->localizationDomain );?></span></a></li>
                    <?php } ?>
                        <li id="hidden_tab"><a id="supporter-help-php" href="<?php bloginfo('wpurl'); ?>/wp-admin/?frame=premium-support" class="tab_tooltip"><span><?php echo $supporter_rebrand  . ' ' . __( 'Help', $this->localizationDomain ); ?></span></a></li>
                <?php }
                if (current_user_can('edit_theme_options')) { ?>
                    <li><a id="widgets-php" href="<?php bloginfo('wpurl'); ?>/wp-admin/?frame=widgets" class="tab_tooltip" title="<?php _e( 'Customize Design|Customize the look and content of your design', $this->localizationDomain ) ?>"><span><?php _e( 'Customize Design', $this->localizationDomain ) ?></span></a></li>
                <?php }
                if (function_exists('is_supporter')) { //We're not using the $this->is_supporter wrapper here, because we need to know if the function exists at all, but we don't care if this blog is a supporter blog ?>
                        <li<?php if ($this->is_supporter()) echo ' id="hidden_tab"'; //If the user is a supporter, hide this tab ?>><a id="supporter-php" href="<?php bloginfo('wpurl'); ?>/wp-admin/?frame=supporter" class="tab_tooltip" title="<?php echo sprintf(__( '%1$s|%1$s users get access to more features for their blogs', $this->localizationDomain ),$supporter_rebrand) ?>"><span><?php echo $supporter_rebrand ?></span></a></li>
                <?php } ?>
                    <li style="float: right;"><a id="profile-php" class="tab_tooltip" href="<?php bloginfo('wpurl'); ?>/wp-admin/?frame=profile" title="<?php _e( 'Profile|Edit your profile', $this->localizationDomain ) ?>"><span><?php _e( 'Profile', $this->localizationDomain ) ?></span></a></li>
                    <li id="hidden_tab"><a id="noteasy" href="<?php bloginfo('wpurl'); ?>/wp-admin/?frame=noteasy" class="tab_tooltip"><span><?php echo __( 'Not Easy', $this->localizationDomain ); ?></span></a></li>
                    <?php do_action('easy_admin_more_tabs'); ?>
                </ul>
            </div>
            <div class="clear"></div>
        </div>
<?php
    require_once(ABSPATH . 'wp-admin/admin-footer.php');
?>
    </div>
