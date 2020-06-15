<?php
/**
 * Plugin Name: Builder Branding
 * Plugin URI:  mailto:vitaliix@gmail.com
 * Description: Module to define Plugins Brand, Color and Menu visibility
 * Version:     2.9.8
 * Author:      Vitalie Lupu
 * Author URI:  mailto:vitaliix@gmail.com
 * Text Domain: builderBranding
 * Domain Path: /languages
 */
if (defined('ABSPATH') == false) {
	exit;
}

use Elementor\Elements_Manager;
use Elementor\Plugin;
use Elementor\Core\Responsive\Responsive;
use Elementor\Widget_Base;
use Elementor\Widgets_Manager;

final class PluginsBranding {
	const TEXT_DOMAIN = 'builderBranding';
	const OPTION_PLUGIN_NAME = 'Builder';
	const OPTION_PLUGIN_DESCRIPTION = 'WYSIWYG Builder';
	const OPTION_PLUGIN_AUTHOR = 'Vitalie Lupu';
	const OPTION_PLUGIN_URI = 'mailto://vitaliix@gmail.com';
	private $colorEditorLoadingBar = '#29d';//'#39b54a';
	private $colorEditorIcon = '#555d66'; //'#c2cbd2';
	private $colorEditorIconHover = '#0073aa'; //'#c2cbd2'; //#6d7882;
	private $colorEditorHandler = '#4285F4'; //'#71d7f7';
	private $colorEditorHandlerHover = '#3d6ede';
	private $colorActiveElement = '#000000';//'green'
	private $logoIcon = '\f538';
	private $pluginPath = 'elementor/elementor.php';
	private $pluginPathPro = 'elementor-pro/elementor-pro.php';
	private $excludeWidgets = [/*'common',
	                           'heading',
	                           'image',
	                           'text-editor',
	                           'video',
	                           'button',
	                           'divider',
	                           'spacer',
	                           'image-box',
	                           'google-maps',
	                           'icon',
	                           'icon-box',
	                           'image-gallery',
	                           'image-carousel',
	                           'icon-list',
	                           'counter',
	                           'progress',
	                           'testimonial',
	                           'tabs',
	                           'accordion',
	                           'toggle',
	                           'social-icons',
	                           'alert',
	                           'audio',
	                           'shortcode',
	                           'html',
	                           'menu-anchor',
	                           'sidebar',
	                           //PRO
	                           'posts',
	                           'portfolio',
	                           'slides',
	                           'form',
	                           'login',
	                           'media-carousel',
	                           'testimonial-carousel',
	                           'nav-menu',
	                           'pricing',
	                           'facebook-comment',
	                           'nav-menu',
	                           'animated-headline',
	                           'price-list',
	                           'price-table',
	                           'facebook-button',
	                           'facebook-comments',
	                           'facebook-embed',
	                           'facebook-page',
	                           'add-to-cart',
	                           'categories',
	                           'elements',
	                           'products',
	                           'flip-box',
	                           'carousel',
	                           'countdown',
	                           'share-buttons',
	                           'author-box',
	                           'breadcrumbs',
	                           'search-form',
	                           'post-navigation',
	                           'post-comments',
	                           'theme-elements',
	                           'blockquote',
	                           'template',
	                           'wp-widget-audio',
	                           'woocommerce',
	                           'social',
	                           'library',*/
	                           // Wordpress
	                           'wp-widget-pages',
	                           'wp-widget-archives',
	                           'wp-widget-media_audio',
	                           'wp-widget-media_image',
	                           'wp-widget-media_gallery',
	                           'wp-widget-media_video',
	                           'wp-widget-meta',
	                           'wp-widget-search',
	                           'wp-widget-text',
	                           'wp-widget-categories',
	                           'wp-widget-recent-posts',
	                           'wp-widget-recent-comments',
	                           'wp-widget-rss',
	                           'wp-widget-tag_cloud',
	                           'wp-widget-nav_menu',
	                           'wp-widget-custom_html',
	                           'wp-widget-polylang',
	                           'wp-widget-calendar',
	                           'wp-widget-elementor-library'];
	protected static $instances = null;
	final public static function i() {
		if (!isset(static::$instances)) {
			static::$instances = new static();
		}
		return static::$instances;
	}
	protected function __construct() {
		add_filter('woocommerce_admin_disabled', '__return_true', 1);
		if (defined('ELEMENTOR_PRO_VERSION')) {
			//Disable Tracker Notice
			add_filter('pre_option_elementor_tracker_notice', [$this, 'handleTrackerOptionNotice']);
			/*Text*/
			add_filter('gettext', [$this,'handleGetText'], 999, 1);
			add_filter('gettext_with_context', [$this,'handleGetText'], 999, 1);
			add_filter('admin_footer_text', [$this,'handleTextInAdminFooter'], 100, 0);
			/*Plugins*/
			add_filter('all_plugins', [$this,'handlePluginsAll']);
			add_filter('plugin_row_meta', [$this,'handlePluginRowMeta'], 20, 2);
			/*Admin Visibility*/
			add_action('admin_menu', [$this,'handleVisibilityInAdminMenu'], 999);
			add_action('wp_dashboard_setup', [$this,'handleVisibilityInDashboard'], 100);
			/*Widgets*/
			add_action('elementor/widgets/widgets_registered', function (Widgets_Manager $manager) {
				$widgetNames = $manager->get_widget_types();
				/*foreach ($this->excludeWidgets as $widgetName) {
					$manager->unregister_widget_type($widgetName);
				}*/
				/**@var $widget Widget_Base*/
				foreach ($widgetNames as $widgetName => $widget) {
					$result = strpos($widgetName, 'wp-widget-');
					if($result !== false){
						$manager->unregister_widget_type($widgetName);
					}
				}
			}, 15);
			/*Styles*/
			add_action('admin_footer', [$this,'handleFooterAdmin']);
			add_action('wp_footer', [$this,'handleFooterSite'], 99999);
			add_action('elementor/editor/footer', [$this,'handleFooterEditor']);
			add_action('elementor/editor/after_enqueue_styles', [$this,'handleEditorAfterEnqueueStyles']);
			add_action('elementor/editor/before_enqueue_scripts', [$this,'handleEditorBeforeEnqueueScripts']);
			add_filter('elementor/editor/localize_settings', [$this, 'handleEditorLocalizeSettings']);
			add_filter('elementor/utils/get_placeholder_image_src', [$this, 'handleGetPlaceHolderImgSrc']);
			//FILTERS for JET Plugins
			add_filter('cherry_core_base_url', [$this, 'handleCherryCoreUrl'], 10, 2);
			add_filter('cx_include_module_url', [$this, 'handleCherryCoreUrl'], 10, 2);
			//Fix User Locale
			add_action('set_current_user', [$this, 'handleSetCurrentUser'], 99);
			add_filter('elementor/document/config', function ($config) {
				$config['remoteLibrary'] = ['default_route' => 'templates/my-templates'];
				return $config;
			});
		}
	}
	function handleSetCurrentUser() {
		global $current_user;
		if ($current_user) {
			$userId = get_current_user_id();
			$userLocale = get_user_meta($userId, 'locale', true);
			$current_user->locale = $userLocale;
		}
	}
	function handleCherryCoreUrl($url, $file_path) {
		if (empty($file_path) == false) {
			$rootPath = dirname(ABSPATH) . DIRECTORY_SEPARATOR;
			$url = content_url() . DIRECTORY_SEPARATOR . str_replace($rootPath, '', $url);
		}
		return $url;
	}
	function handleTrackerOptionNotice() {
		return '1';
	}
	function handleGetText($translatedText) {
		return str_replace('Elementor', self::OPTION_PLUGIN_NAME, $translatedText);
	}
	function handleTextInAdminFooter() {
		return '';
	}
	function handlePluginsAll($allPlugins) {
		if (defined('ELEMENTOR_PLUGIN_BASE') && isset($allPlugins[ELEMENTOR_PLUGIN_BASE])) {
			$allPlugins[ELEMENTOR_PLUGIN_BASE]['Name'] = self::OPTION_PLUGIN_NAME;
			//$allPlugins[ELEMENTOR_PLUGIN_BASE]['PluginURI'] = self::OPTION_PLUGIN_URI;
			$allPlugins[ELEMENTOR_PLUGIN_BASE]['Description'] = self::OPTION_PLUGIN_DESCRIPTION;
			$allPlugins[ELEMENTOR_PLUGIN_BASE]['Author'] = self::OPTION_PLUGIN_AUTHOR;
			$allPlugins[ELEMENTOR_PLUGIN_BASE]['AuthorURI'] = self::OPTION_PLUGIN_URI;
			$allPlugins[ELEMENTOR_PLUGIN_BASE]['Title'] = self::OPTION_PLUGIN_NAME;
			$allPlugins[ELEMENTOR_PLUGIN_BASE]['AuthorName'] = self::OPTION_PLUGIN_AUTHOR;
			if (defined('ELEMENTOR_PRO_PLUGIN_BASE')) {
				$textPro = ' Pro';
				$allPlugins[ELEMENTOR_PRO_PLUGIN_BASE]['Name'] = self::OPTION_PLUGIN_NAME . $textPro;
				//$allPlugins[ELEMENTOR_PRO_PLUGIN_BASE]['PluginURI'] = self::OPTION_PLUGIN_URI;
				$allPlugins[ELEMENTOR_PRO_PLUGIN_BASE]['Description'] = self::OPTION_PLUGIN_DESCRIPTION;
				$allPlugins[ELEMENTOR_PRO_PLUGIN_BASE]['Author'] = self::OPTION_PLUGIN_AUTHOR;
				$allPlugins[ELEMENTOR_PRO_PLUGIN_BASE]['AuthorURI'] = self::OPTION_PLUGIN_URI;
				$allPlugins[ELEMENTOR_PRO_PLUGIN_BASE]['Title'] = self::OPTION_PLUGIN_NAME . $textPro;
				$allPlugins[ELEMENTOR_PRO_PLUGIN_BASE]['AuthorName'] = self::OPTION_PLUGIN_AUTHOR;
			}
			//Hide Current Plugin record from Plugins Page
			//unset($allPlugins[plugin_basename(__FILE__)]);
			//Hide Elementor Plugin record from Plugins Page
			//unset($allPlugins[ELEMENTOR_PLUGIN_BASE]);
			//if (defined('ELEMENTOR_PRO_PLUGIN_BASE')) unset($allPlugins[ELEMENTOR_PRO_PLUGIN_BASE]);
		}
		return $allPlugins;
	}
	function handleVisibilityInAdminMenu() {
		if (isset($_GET['page']) && in_array($_GET['page'], ['go_knowledge_base_site',
		                                                     'go_elementor_pro'
			])) {
			wp_redirect(self::OPTION_PLUGIN_URI);
			die;
		}
		//Remove Page from Admin Menu: Elementor Settings
		//remove_menu_page('elementor');
		//Remove Page from Admin Menu: Elementor Template Library
		//remove_menu_page('edit.php?post_type=elementor_library');
	}
	function handleVisibilityInDashboard() {
		global $wp_meta_boxes;
		//Hide Elementor Widget Overview
		if (is_array($wp_meta_boxes) && isset($wp_meta_boxes['dashboard']['normal']['core']['e-dashboard-overview'])) {
			unset($wp_meta_boxes['dashboard']['normal']['core']['e-dashboard-overview']);
		}
	}
	function handlePluginRowMeta($plugin_meta, $plugin_file) {
		//Hide Elementor External Links
		if (defined('ELEMENTOR_PLUGIN_BASE') && ELEMENTOR_PLUGIN_BASE === $plugin_file) {
			if (isset($plugin_meta['docs'])) {
				unset($plugin_meta['docs']);
			}
			if (isset($plugin_meta['ideo'])) {
				unset($plugin_meta['ideo']);
			}
			if (isset($plugin_meta['video'])) {
				unset($plugin_meta['video']);
			}
		}
		if (defined('ELEMENTOR_PRO_PLUGIN_BASE')) {
			if (ELEMENTOR_PRO_PLUGIN_BASE === $plugin_file) {
				if (isset($plugin_meta['docs'])) {
					unset($plugin_meta['docs']);
				}
				if (isset($plugin_meta['ideo'])) {
					unset($plugin_meta['ideo']);
				}
				if (isset($plugin_meta['video'])) {
					unset($plugin_meta['video']);
				}
				if (isset($plugin_meta['changelog'])) {
					unset($plugin_meta['changelog']);
				}
			}
		}
		return $plugin_meta;
	}
	function handleFooterAdmin() {
		$idCssContent = self::TEXT_DOMAIN;
		$cssContent = '';
		//TODO Remove Menu Page Of Elementor not with css but with WP menu handler
		echo "<style id='{$idCssContent}'>{$cssContent}
        .woocommerce-layout__header .woocommerce-layout__header-breadcrumbs span:first-child,
		.woocommerce-layout__header .woocommerce-layout__header-breadcrumbs span+span:before,
		.woocommerce-layout__activity-panel-tabs #activity-panel-tab-inbox{
			display:none !important;
		}
        /*Logo*/
        #adminmenu #toplevel_page_elementor div.wp-menu-image:before, 
        #adminmenu #toplevel_page_edit-post_type-elementor_library div.wp-menu-image:before 
        {
            content: '{$this->logoIcon}';
            font-family: dashicons,serif;
            margin-top: auto;
            font-size: 18px;
        }
        .eicon-elementor-square:before
        {
            content: '{$this->logoIcon}';
            font-family: dashicons,serif;
            vertical-align: middle;
        }
        /*Page Hide: Intro */
        ul#adminmenu li.toplevel_page_elementor li a[href=\"admin.php?page=elementor-getting-started\"],
        /*Page Hide: Knowledge Base */
        ul#adminmenu li.toplevel_page_elementor li a[href=\"admin.php?page=go_knowledge_base_site\"],
        /*Page Hide: License */
        ul#adminmenu li.toplevel_page_elementor li a[href=\"admin.php?page=elementor-license\"] 
        { 
            display: none;
        }
        /*Hide Elementor External Links*/
        .elementor-button-go-pro,
        .elementor-message[data-notice_id='rate_us_feedback'], 
        tr[data-plugin='{$this->pluginPath}'] span.go_pro,
        tr[data-plugin='{$this->pluginPathPro}'] span.active_license,
        tr[data-slug='elementor'] .open-plugin-details-modal,
        tr[data-slug='elementor-pro'] .open-plugin-details-modal,
        tr[data-plugin='{$this->pluginPathPro}'] .open-plugin-details-modal,
        tr.elementor_allow_tracking a,
        div.elementor-template-library-blank-footer, 
        #adminmenu #toplevel_page_elementor a[href='admin.php?page=go_elementor_pro'],
        #adminmenu #toplevel_page_edit-post_type-elementor_library a[href='admin.php?page=go_elementor_pro']
        { 
            display: none; 
        }
        /*Dialog*/
        .elementor-templates-modal__header__logo__icon-wrapper{
            display: none;
        }
        .elementor-button.elementor-button-success:not([disabled])
        {
            background-color: black;
        }
        #elementor-new-template-dialog-content
        { 
            padding: 10px; 
        }
        #elementor-new-template-modal .dialog-widget-content
        {
            top: 50px !important;
            max-width: none; 
            width: 60%;
            margin-left: auto;
            margin-right: auto;
        }
        @media (max-width: 1439px), all{ 
            #elementor-new-template-modal .dialog-widget-content{ width: 60%;}
        }
        @media (max-width:767px) { 
            #elementor-new-template-modal .dialog-widget-content{ width: 95%;} 
        }
        #elementor-new-template__form__title, #elementor-new-template__description
        { 
            display: none; 
        }
        #elementor-new-template__form 
        {
             max-width: none; 
             padding: 0; 
             border-radius: 0; 
             -webkit-box-shadow: none; 
             box-shadow: none; 
        }
        .elementor-templates-modal .dialog-widget-content
        {
            background-color: white !important;
        }
        /*Post Page Styles*/
        .elementor-loader-wrapper
        {
            display:none !important;
        }
        #elementor-switch-mode,
        body.elementor-editor-active #elementor-editor
        {
            margin: 0;
            vertical-align: middle;
            height: auto;
            width: auto;
        }
        #elementor-go-to-edit-page-link{
        	background-color: transparent;
        	border: none;
        }
        #elementor-go-to-edit-page-link.elementor-animate #elementor-editor-button
        {
            display: initial !important; 
        }
        /*Font Page*/
        .elementor-metabox-content .repeater-block
        {
            padding: 10px;
        }
        .elementor-metabox-content .repeater-content .repeater-content-top
        {
            margin-bottom: 10px;
            line-height: initial;
        }
        .elementor-metabox-content .repeater-content .repeater-content-top .elementor-field-toolbar 
        {
            max-width: 100px;
            text-align: left;
        }
        </style>";
	}
	function handleFooterSite() {
		if (is_user_logged_in()) {
			$idCssContent = self::TEXT_DOMAIN;
			echo "<style id='{$idCssContent}'>
            /*Logo*/
            #wpadminbar #wp-admin-bar-elementor_edit_page>.ab-item:before {
                content: '{$this->logoIcon}';
                font-family: dashicons,serif !important;
                font-size: 18px;
                top: 3px;
            }
            /*Editor Handler Colors*/
            .elementor-device-mobile .elementor-responsive-switcher-mobile,
            .elementor-device-tablet .elementor-responsive-switcher-tablet,
            .elementor-control-type-switcher .elementor-switch-input:checked ~ .elementor-switch-label,
            .elementor-editor-section-settings,
            .elementor-sortable-placeholder:not(.elementor-column-placeholder),
            .elementor-element[data-side='top']:before, 
            .elementor-element[data-side='bottom'] + .elementor-element:before,
            .elementor-element[data-side='bottom']:last-child:after,
            .elementor-first-add.elementor-html5dnd-current-element:after,
            .elementor-draggable-over:not([data-dragged-element='section']):not([data-dragged-is-inner='true']) > .elementor-empty-view > .elementor-first-add:after
            {
                background-color: {$this->colorEditorHandler} !important;
            }
            /*Buttons*/
            .elementor-button
            {
                padding: 10px 20px;
            }
            .elementor-add-section-button,
            .elementor-add-template-button{
            	cursor: pointer;
            }
            .elementor-add-section-button *,
            .elementor-add-template-button *{
            	color: white;
            }
            div.elementor-add-section-button,
            div.elementor-add-template-button,
            div.elementor-template-library-template-remote.elementor-template-library-pro-template .elementor-template-library-template-body:before
            { 
                background-color: {$this->colorActiveElement} !important;
            }
            /*Handler*/
            .elementor-editor-column-settings .elementor-editor-element-setting:not(:hover),
            .elementor-editor-widget-settings .elementor-editor-element-setting:not(:hover) {
                background-image: linear-gradient(to top, {$this->colorEditorHandler}, {$this->colorEditorHandler}) !important;
            }
            .elementor-editor-column-settings .elementor-editor-element-setting:hover,
            .elementor-editor-widget-settings .elementor-editor-element-setting:hover {
                background-image: linear-gradient(to top, {$this->colorEditorHandlerHover}, {$this->colorEditorHandlerHover}) !important;
            }
            .elementor-editor-active .elementor-widget.elementor-element-editable, 
            .elementor-editor-active .elementor-widget.elementor-element-edit-mode:hover {
                outline: 1px solid {$this->colorEditorHandler}; 
            }
            .elementor-section > .elementor-element-overlay:after{
                outline: 2px solid {$this->colorEditorHandler};
            }
            .elementor-column.elementor-dragging-on-child > .elementor-element-overlay{
                border: 1px solid {$this->colorEditorHandler};
            }
            .elementor-editor-section-settings .elementor-editor-element-setting:first-child:before,
            .elementor-editor-section-settings .elementor-editor-element-setting:last-child:after{
                border-right-color: {$this->colorEditorHandler};
                display: none;
            }
            .elementor-editor-section-settings .elementor-editor-element-setting:hover {
                background-color: {$this->colorEditorHandlerHover}; 
            }
            .elementor-editor-section-settings .elementor-editor-element-setting:last-child:hover:after{
                border-left-color: {$this->colorEditorHandlerHover};
            }
            .elementor-editor-section-settings .elementor-editor-element-setting:first-child:hover:before {
                border-right-color: {$this->colorEditorHandlerHover};
            }
            .elementor-add-section.elementor-dragging-on-child .elementor-add-section-inner {
                border: 3px dashed {$this->colorEditorHandler}; 
            }
            </style";
		}
	}
	function handleEditorAfterEnqueueStyles() {
		wp_add_inline_style('elementor-editor', "/*General*/
        ::selection {
          background: {$this->colorEditorHandlerHover} !important; /* WebKit/Blink Browsers */
        }
        ::-moz-selection {
          background: {$this->colorEditorHandlerHover} !important; /* Gecko Browsers */
        }
        *:not(input, text, textarea){
        -webkit-user-select: none;
         -moz-user-select: none;
          -ms-user-select: none;
              user-select: none;
        }
        a[href*='elementor'],
        .elementor-nerd-box-icon, .eicon-nerd, 
        #elementor-template-library-footer-banner,
        #elementor-template-library-header-sync
        {
            display:none;
        }
        /*---- [Logo] ----*/
        #wpadminbar #wp-admin-bar-elementor_edit_page > .ab-item::before { 
            content: '{$this->logoIcon}'; 
            font-family: dashicons; 
        }
        /*-----------------------------------------------------------[Loader]*/
        .elementor-loading-title {
            color: {$this->colorEditorIcon};
            text-transform: initial;
            letter-spacing: initial;
            text-indent:  initial;
            font-size: 20px;
        }
        div.elementor-loader-wrapper .elementor-loader
        { 
            display: none; 
        }
        #nprogress .bar 
        { 
            background: {$this->colorEditorLoadingBar}; 
        }
        #nprogress .peg {
            -webkit-box-shadow: 0 0 10px {$this->colorEditorLoadingBar}, 0 0 5px {$this->colorEditorLoadingBar};
            box-shadow: 0 0 10px {$this->colorEditorLoadingBar}, 0 0 5px {$this->colorEditorLoadingBar};
        }
        #nprogress .spinner-icon{
            border-top-color: {$this->colorEditorLoadingBar};
            border-left-color: {$this->colorEditorLoadingBar};
        }
        /*-----------------------------------------------------------[Pro Messages]*/
        div.elementor-panel-nerd-box,
        div.elementor-control-custom_css_pro, 
        div.elementor-control-section_custom_css_pro,
        div#elementor-panel-get-pro-elements 
        { 
            display: none; 
        }
        .elementor-control-wc_style_warning
        {
            display:none;
        }
        /*-----------------------------------------------------------[Panel]*/
        .elementor-panel {
            font-size: 15px;
        }
        .elementor-panel #elementor-panel-elements-search-input
        {
            padding: 5px 0 5px 20px
            font-size: 14px;
            font-style: initial;
            color: initial;
        }
        @media (prefers-color-scheme: light){
            div.elementor-panel a,
            div.elementor-panel a:hover, 
            div.elementor-panel .elementor-element:hover .icon,
            div.elementor-panel .elementor-element:hover .title,
            div.elementor-panel .elementor-control-type-gallery .elementor-control-gallery-clear 
            { 
                color: {$this->colorActiveElement}; 
            }
            #elementor-mode-switcher,
            #elementor-mode-switcher:hover, 
            #elementor-panel-content-wrapper,
            .elementor-panel #elementor-panel-header,
            .elementor-panel #elementor-panel-footer,
            .elementor-panel .elementor-panel-footer-sub-menu-wrapper
            {
                background-color:#E5E5E5 !important;
            }
            .elementor-panel #elementor-panel-header-title 
            {
                color: {$this->colorEditorIcon};
            }
        }
        .elementor-panel .elementor-element-wrapper 
        { 
            width: 110px!important; 
            padding: 4px; 
        }
        .elementor-panel .elementor-element .icon
        {
            font-size: 30px;
            padding-top: 10px;
        }
        .elementor-panel .elementor-element .title 
        {
            font-size: 13px;
            height: 40px;
            padding-left: 2px;
            padding-right: 2px;
        }
        .elementor-panel .elementor-panel-category-title
        {
            padding-top:10px;
            padding-bottom:10px;
        }
        .elementor-panel #elementor-panel-footer-saver-options {
            border-left: none;
            width:auto;
        }
        #elementor-panel-footer-sub-menu-item-conditions
        {
            display: table !important;
        }
        .elementor-panel #elementor-panel-header-title img
        {
            visibility:hidden;
        }
        .elementor-panel .elementor-panel-scheme-color-system-items 
        {
            border: 2px solid transparent;
        }
        .elementor-panel .elementor-panel-scheme-color-system-items:hover
        {
            border: 2px solid {$this->colorEditorIconHover};
        }
        .elementor-panel .elementor-panel-navigation .elementor-panel-navigation-tab.elementor-active
        { 
            border-bottom-color: {$this->colorActiveElement}; 
        } 
        /*-----------------------------------------------------------[Panel: Navigation]*/
        .elementor-panel .elementor-panel-navigation .elementor-panel-navigation-tab a
        {
            color: {$this->colorActiveElement}
        }
        /*-----------------------------------------------------------[Panel: Footer]*/
        .elementor-panel-menu-item-icon
        {
            font-size: 19px;
        }
        .elementor-panel .elementor-panel-footer-tool i:before
        {
            font-size: 19px;
        }
        @media (prefers-color-scheme: light){
            .elementor-panel .elementor-panel-menu-item>*,
            .elementor-panel .elementor-panel-footer-sub-menu-item:not(.elementor-disabled)>*
            {
                background-color:#fff;
                color: {$this->colorEditorIcon};
            }
            .elementor-panel .elementor-panel-footer-sub-menu-item.elementor-disabled
            {
                background-color:#fff;
            }
            .elementor-panel .elementor-panel-footer-tool.elementor-disabled,
            .elementor-panel .elementor-panel-footer-sub-menu-item.elementor-disabled>*
            {
                color: #cccccc !important;
            }
            .elementor-panel .elementor-panel-footer-sub-menu-item.active>*
            {
                background-color: #efefef;
                color:{$this->colorEditorIconHover};
            }
            .elementor-panel .elementor-panel-menu-item:hover:not(.active)>*,
            .elementor-panel .elementor-panel-footer-sub-menu-item:hover:not(.elementor-disabled):not(.active)>*
            {
                background-color: #f7f7f7;
                color:{$this->colorEditorIconHover};
            }
            .elementor-panel .elementor-panel-footer-tool
            {
                color: {$this->colorEditorIcon};
            }
            .elementor-panel .elementor-panel-footer-tool:hover:not(.elementor-disabled)>*,
            .elementor-panel .elementor-panel-footer-tool.elementor-open:not(.elementor-disabled)>*
            {
                color:{$this->colorEditorIconHover};
            }
        }
        .elementor-panel .elementor-panel-footer-tool.elementor-open
        {
            box-shadow: inset 0px -3px 0px 0px {$this->colorEditorIconHover};
        }
        /*-----------------------------------------------------------[Page]*/
        #elementor-panel__editor__help
        {
            display:none !important;
        }
        .elementor-nerd-box 
        {
            padding: 20px;
            color: #556068;
        }
        .elementor-nerd-box-title 
        {
            margin-top: 10px;
            font-size: 18px;
            font-weight: inherit;
            line-height: 1.4;
        }
        .elementor-nerd-box-message 
        {
            margin-top: 10px;
            font-size: 14px;
            line-height: 1.8;
        }
        #elementor-controls,
        #elementor-panel-general-settings-controls,
        #elementor-panel-page-settings-controls
        {
            margin: 10px 10px 10px 5px;
        }
        .elementor-panel .elementor-control-type-section
        {
            border-radius: 5px;
        }
        .elementor-panel .elementor-panel-box
        {
            border-radius: 5px;
            margin: 10px 10px 10px 5px;
        }
        .elementor-panel .elementor-panel-box-content
        {
            padding: 10px;
        }
        .elementor-panel .elementor-panel-navigation .elementor-panel-navigation-tab a 
        {
            font-size: 14px;
            padding: 6px 0 6px;
        }
        .elementor-panel #elementor-panel-elements-navigation .elementor-panel-navigation-tab 
        {
            font-size: 14px;
            text-transform: inherit;
        }
        .elementor-panel .elementor-panel-category-title
        {
            text-transform: inherit;
            font-size: 14px;
            padding: 10px 0 !important;
        }
        /*-----------------------------------------------------------[Page: Menu]*/
        .elementor-panel #elementor-panel-page-menu
        {
            padding: 10px 10px 10px 5px;
        }
        .elementor-panel .elementor-panel-menu-group-title
        {
            text-transform: inherit;
            font-size: 14px;
            text-align: center;
        }
        .elementor-panel .elementor-panel-menu-items
        {
            margin: 10px 0 10px;
        }
        /*-----------------------------------------------------------[Page: Color Scheme]*/
        .wp-color-result
        {
            width: 36px;
        }
        .elementor-panel .elementor-panel-scheme-color .elementor-panel-scheme-item,
        .elementor-panel .elementor-panel-scheme-color .elementor-panel-scheme-items
        {
            text-align:center;
        }
        .elementor-panel .elementor-panel-scheme-color .elementor-panel-scheme-item
        {
            margin-bottom:5px;
            margin-right:5px !important;
        }
        .elementor-panel .elementor-panel-scheme-color-title
        {
            text-align: center;
            text-transform: inherit;
            margin: 0;
            color: inherit;
            font-size: 12px;
        }
        .elementor-panel .elementor-panel-scheme-color-system-scheme .elementor-title
        {
            font-size: 13px;
            color: inherit;
            font-style: inherit;
            margin-top: 0;
            text-align: center;
        }
        /*-----------------------------------------------------------[Buttons]*/
        @media (prefers-color-scheme: light){
            .elementor-header-button:not([disabled]),
            #elementor-mode-switcher-preview:not([disabled])
            {
                color: {$this->colorEditorIcon} !important;
            }
            .elementor-panel .elementor-panel-scheme-discard .elementor-button, 
            .elementor-panel .elementor-panel-scheme-reset .elementor-button
            {
                color: {$this->colorEditorIcon} !important;
            }
            #elementor-mode-switcher-preview:hover:not([disabled])>*,
            .elementor-header-button:hover:not([disabled])>*,
            .elementor-panel .elementor-element:not([disabled]):hover,
            .elementor-panel .elementor-panel-scheme-discard .elementor-button:hover, 
            .elementor-panel .elementor-panel-scheme-reset .elementor-button:hover
            {
                color:{$this->colorEditorIconHover} !important;
            }
        }
        #elementor-template-library-header-preview-insert-wrapper .elementor-template-library-template-insert,
        .elementor-button.elementor-button-success:not([disabled])
        {
            background-color: {$this->colorActiveElement} !important;
        }
        .elementor-safe-mode-button
        {
            display:none !important;
        }
        .elementor-button,
        .elementor-panel #elementor-panel-saver-button-publish, 
        .elementor-panel #elementor-panel-saver-button-save-options 
        {
            border-radius:none;
            font-size: inherit;
            text-transform: inherit; 
            height: inherit;
        } 
        .elementor-panel #elementor-panel-saver-button-publish
        {
            width: auto;
            background-color:transparent !important;
        }
        .elementor-panel #elementor-panel-saver-button-save-options
        {
            width: auto !important;
            float: none !important;
        }
        .elementor-panel #elementor-panel-saver-button-save-options.elementor-disabled {
            background-color: transparent;
            color: #a4afb7;
        }
        /*-----------------------------------------------------------[Page Handlers]*/
        .pen-group-icon:after,
        .elementor-context-menu-list__item__icon
        {
            color: {$this->colorEditorIcon};
        }
        .elementor-context-menu-list__item:not(.elementor-context-menu-list__item--disabled):hover > .elementor-context-menu-list__item__icon
        {
            color: white;
        }
        .elementor-control-dynamic-value .elementor-control-dynamic-switcher,
        .elementor-control-type-popover_toggle .elementor-control-popover-toggle-toggle:checked + .elementor-control-popover-toggle-toggle-label
        {
            color: {$this->colorEditorHandler};
        }
        .elementor-finder__results__item.elementor-active,
        .elementor-control-type-switcher .elementor-switch-input:checked~.elementor-switch-label,
        .elementor-navigator__element:not(.elementor-navigator__element--hidden)>.elementor-navigator__item.elementor-editing,
        .elementor-context-menu-list__item:not(.elementor-context-menu-list__item--disabled):hover
        { 
            background-color: {$this->colorEditorHandlerHover} !important; 
        }
        /*-----------------------------------------------------------[Modal]*/
        .elementor-templates-modal .dialog-message
        {
            padding: 10px;
        }
        .elementor-templates-modal__header
        {
            height: 50px;
        }
        .elementor-templates-modal__header__logo__title
        {
            font-weight: initial;
            text-transform: initial;
            font-size: 15px
        }
        .elementor-templates-modal__header__logo i
        {
            color: inherit;
            font-size: inherit;
        }
        .elementor-templates-modal__header .elementor-templates-modal__header__logo__icon-wrapper
        {
            display:block;
            background: none;
            padding:0;
        }
        .elementor-templates-modal__header .elementor-template-library-menu-item.elementor-active
        { 
            border-bottom-color: {$this->colorActiveElement}; 
        }
        /*-----------------------------------------------------------[Template]*/
        .elementor-component-tab[data-tab='templates/blocks'],
        .elementor-component-tab[data-tab='templates/pages'], 
        .elementor-template-library-footer-banner,
        .elementor-template-library-template-remote.elementor-template-library-pro-template/* .elementor-template-library-template-body:before*/
        {
            display:none;
        }
        .elementor-template-library-template-local .elementor-template-library-template-more
        {
            padding: 3px 10px;
            right: 35px;
        }
        .elementor-template-library-template-local .elementor-template-library-template-more:before
        {
            border-width: 8px 10px;
            right: 10px;
        }
        .elementor-template-library-blank-title
        {
            margin-top:10px;
            font-size: 24px
        }
        .elementor-template-library-blank-icon,
        .elementor-template-library-blank-message
        {
            margin-top:0;
        }
        .elementor-template-library-blank-message
        {
            font-size: 16px
        }
        #elementor-template-library-modal .eicon-elementor:before
        {
            content: '\\e919';
        }
        #elementor-template-library-save-template {
            width: 95%;
            padding: 60px;
            border: 2px dashed #d5dadf;
            margin: 35px auto;
            -webkit-transition: background-color .5s;
            -o-transition: background-color .5s;
            transition: background-color .5s;
        }
        #elementor-template-library-save-template-name
        {
            min-width: 170px;
            padding-left: 10px;
        }
        #elementor-template-library-save-template-submit
        {
            width: auto;
            padding-left:20px;
            padding-right:20px;
        }
        #elementor-template-library-save-template-form
        {
            margin-top: 20px;
        }
        #elementor-template-library-save-template-form>*
        {
            height: 40px;
        }
        /*-----------------------------------------------------------[Conditions]*/
        #elementor-theme-builder-conditions
        {
            margin:0 !important;
            padding-bottom: 60px;
        }
        #elementor-theme-builder-conditions__footer
        {
            text-align: center !important;
        }
        #elementor-theme-builder-conditions .elementor-button-wrapper
        {
            margin-top:10px !important;
        }
        #elementor-theme-builder-conditions .elementor-repeater-fields-wrapper 
        {
            width: auto !important;
        }
        #elementor-pro-panel-saver-conditions
        {
            display: inline-table !important;
        }
        #elementor-pro-panel-saver-conditions i:before
        {
            content:'\\f070';
        }
        #elementor-conditions-modal .eicon-elementor:before
        {
            font: normal normal normal 14px/1 FontAwesome;
            content:'\\f070';
        }
        /*-----------------------------------------------------------[Finder]*/
        #elementor-finder__modal .eicon-elementor:before
        {
            content: '\\e984';
        }
        /*-----------------------------------------------------------[Navigator]*/
        #elementor-navigator__close
        {
            font-size: 18px;
            padding-left: 10px;
            border-left: 1px solid;
        }
        #elementor-navigator__toggle-all 
        {
            font-size: 16px;
        }
        #elementor-navigator__header__title:before
        {
            display: inline-block;
            font-family: eicons;
            content: '\\e1023';
            font-size: inherit;
            font-weight: 400;
            font-style: normal;
            font-variant: normal;
            line-height: 1;
            text-rendering: auto;
            margin-right: 2px;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        #elementor-navigator__inner
        {
            background-color: #E5E5E5;
            font-size:15px;
        }
        .elementor-navigator__element__title__text:not([contenteditable=true])
        {
            font-size: 13px
        }
        .elementor-navigator__element__element-type {
            font-size: 14px;
        }
        .elementor-navigator__element:not(.elementor-navigator__element--hidden)>.elementor-navigator__item:not(.elementor-editing) .elementor-navigator__element__toggle
        {
            color:{$this->colorEditorIcon};
        }");
	}
	function handleFooterEditor() {
		$textMenu = __('Menu', 'elementor');
		$textNavigator = __('Navigator', 'elementor');
		echo "<script type='text/template' id='tmpl-elementor-panel-header'>
        <div id='elementor-panel-header-menu-button' class='elementor-header-button'>
            <i class='elementor-icon eicon-chevron-left tooltip-target' aria-hidden='true' data-tooltip='{$textMenu}'></i>
            <span class='elementor-screen-only'>{$textMenu}</span>
        </div>
        <div id='elementor-panel-header-title'></div>
        <div id='elementor-panel-footer-navigator' class='elementor-header-button tooltip-target' data-tooltip='{$textNavigator}'>
            <i class='elementor-icon eicon-navigator' aria-hidden='true'></i>
            <span class='elementor-screen-only'>{$textNavigator}</span>
        </div>
        </script>";
		$textAddNewSection = __('Add New Section', 'elementor');
		$textAddTemplate = __('Add Template', 'elementor');
		$textDragWidgetHere = __('Drag widget here', 'elementor');
		$textSelectYourStructure = __('Select your Structure', 'elementor');
		$textClose = __('Close', 'elementor');
		echo "<script type='text/template' id='tmpl-elementor-add-section'>
        <div class='elementor-add-section-inner'>
            <div class='elementor-add-section-close'>
                <i class='eicon-close' aria-hidden='true'></i>
                <span class='elementor-screen-only'>{$textClose}</span>
            </div>
            <div class='elementor-add-new-section'>
                <div class='elementor-button elementor-button-success elementor-add-section-button'>
                    <i class='eicon-column'></i>
                    <span>{$textAddNewSection}</span>
                </div>
                <div class='elementor-button elementor-button-success elementor-add-template-button'>
                    <i class='fa fa-folder'></i>
                    <span>{$textAddTemplate}</span>
                </div>
                <div class='elementor-add-section-drag-title'>{$textDragWidgetHere}</div>
            </div>
            <div class='elementor-select-preset'>
                <div class='elementor-select-preset-title'>{$textSelectYourStructure}</div>
                <ul class='elementor-select-preset-list'>
                    <#
                        var structures = [ 10, 20, 30, 40, 21, 22, 31, 32, 33, 50, 60, 34 ];
    
                        _.each( structures, function( structure ) {
                        var preset = elementor.presetsFactory.getPresetByStructure( structure ); #>
    
                        <li class='elementor-preset elementor-column elementor-col-16' data-structure='{{ structure }}'>
                            {{{ elementor.presetsFactory.getPresetSVG( preset.preset ).outerHTML }}}
                        </li>
                        <# } ); #>
                </ul>
            </div>
        </div>
        </script>";
		$document = Plugin::$instance->documents->get(Plugin::$instance->editor->get_post_id());
		$textPage = $document::get_title();//__('Page');
		$textSettings = __('Settings', 'elementor');
		$textHistory = __('History', 'elementor');
		$textPreviewChanges = __('Preview Changes', 'elementor');
		$breakpoints = Responsive::get_breakpoints();
		$textPreviewForBreakPoint = sprintf(__('Preview for %s', 'elementor'), $breakpoints['md'] . 'px');
		$textWidgetsPanel = __('Widgets Panel', 'elementor');
		$textResponsiveMode = __('Responsive Mode', 'elementor');
		$textDesktop = __('Desktop', 'elementor');
		$textDefaultPreview = __('Default Preview', 'elementor');
		$textTablet = __('Tablet', 'elementor');
		$textMobile = __('Mobile', 'elementor');
		$textPreviewFor360px = __('Preview for 360px', 'elementor');
		$textPublish = __('Publish', 'elementor');
		$textSaveOptions = __('Save Options', 'elementor');
		$textSaveDraft = __('Save Draft', 'elementor');
		$textSaveAsTemplate = __('Save as Template', 'elementor');
		echo "<script type='text/template' id='tmpl-elementor-panel-footer-content'>
        <div id='elementor-panel-footer-settings' class='elementor-panel-footer-tool elementor-toggle-state tooltip-target' data-tooltip='{$textSettings}'>
            <i class='fa fa-cog' aria-hidden='true'></i>
            <span class='elementor-screen-only'>{$textPage}: {$textSettings}</span>
        </div>
        <div id='elementor-panel-footer-history' class='elementor-panel-footer-tool elementor-toggle-state tooltip-target' data-tooltip='{$textPage}: {$textHistory}'>
            <i class='fa fa-history' aria-hidden='true'></i>
            <span class='elementor-screen-only'>{$textPage}: {$textHistory}</span>
        </div>
        <div id='elementor-panel-footer-widgets' class='elementor-panel-footer-tool elementor-toggle-state tooltip-target' data-tooltip='{$textWidgetsPanel}'>
            <i class='eicon-apps' aria-hidden='true'></i>
            <span class='elementor-screen-only'>{$textWidgetsPanel}</span>
        </div>
        <div id='elementor-panel-saver-button-publish' class='elementor-panel-footer-tool elementor-toggle-state tooltip-target elementor-disabled' data-tooltip='{$textPublish}'>
            <i class='fa fa-upload' aria-hidden='true'></i>
            <span id='elementor-panel-saver-button-publish-label' class='elementor-screen-only'>{$textPublish}</span>
        </div>
        <div id='elementor-panel-footer-saver-options' class='elementor-panel-footer-tool elementor-toggle-state'>
            <div id='elementor-panel-saver-button-save-options' class='tooltip-target' data-tooltip='{$textSaveOptions}'>
                <i class='fa fa-save' aria-hidden='true'></i>
                <span class='elementor-screen-only'>{$textSaveOptions}</span>
            </div>
            <div class='elementor-panel-footer-sub-menu-wrapper'>
                <div class='elementor-panel-footer-sub-menu'>
                    <div id='elementor-panel-footer-sub-menu-item-save-template' class='elementor-panel-footer-sub-menu-item'>
                        <i class='elementor-icon fa fa-folder' aria-hidden='true'></i>
                        <span class='elementor-title'>{$textSaveAsTemplate}</span>
                    </div>
                    <div id='elementor-panel-footer-saver-preview' class='elementor-panel-footer-sub-menu-item'>
                        <i class='elementor-icon fa fa-share-square-o' aria-hidden='true'></i>
                        <span class='elementor-title'>{$textPreviewChanges}</span>
                    </div>
                    <div id='elementor-panel-footer-sub-menu-item-save-draft' class='elementor-panel-footer-sub-menu-item elementor-disabled'>
                        <i class='elementor-icon fa fa-file-text-o' aria-hidden='true'></i>
                        <span class='elementor-title'>{$textSaveDraft}</span>
                    </div>
                </div>
            </div>
        </div>
        <div id='elementor-panel-footer-responsive' class='elementor-panel-footer-tool elementor-toggle-state'>
            <i class='eicon-device-desktop tooltip-target' aria-hidden='true' data-tooltip='{$textResponsiveMode}'></i>
            <span class='elementor-screen-only'>{$textResponsiveMode}</span>
            <div class='elementor-panel-footer-sub-menu-wrapper'>
                <div class='elementor-panel-footer-sub-menu'>
                    <div class='elementor-panel-footer-sub-menu-item' data-device-mode='desktop'>
                        <i class='elementor-icon eicon-device-desktop' aria-hidden='true'></i>
                        <span class='elementor-title'>{$textDesktop}</span>
                        <span class='elementor-description'>{$textDefaultPreview}</span>
                    </div>
                    <div class='elementor-panel-footer-sub-menu-item' data-device-mode='tablet'>
                        <i class='elementor-icon eicon-device-tablet' aria-hidden='true'></i>
                        <span class='elementor-title'>{$textTablet}</span>
                        <span class='elementor-description'>{$textPreviewForBreakPoint}</span>
                    </div>
                    <div class='elementor-panel-footer-sub-menu-item' data-device-mode='mobile'>
                        <i class='elementor-icon eicon-device-mobile' aria-hidden='true'></i>
                        <span class='elementor-title'>{$textMobile}</span>
                        <span class='elementor-description'>{$textPreviewFor360px}</span>
                    </div>
                </div>
            </div>
        </div>
        </script>";
	}
	function handleEditorBeforeEnqueueScripts() {
		$pathToPluginDir = plugins_url('/', __FILE__);
		wp_deregister_script('elementor-editor');
		$requiredScripts = ['elementor-common',
		                    'elementor-editor-modules',
		                    'elementor-editor-document',
		                    'wp-auth-check',
		                    'jquery-ui-sortable',
		                    'jquery-ui-resizable',
		                    'perfect-scrollbar',
		                    'nprogress',
		                    'tipsy',
		                    'imagesloaded',
		                    'heartbeat',
		                    'jquery-elementor-select2',
		                    'flatpickr',
		                    'ace',
		                    'ace-language-tools',
		                    'jquery-hover-intent',
		                    'nouislider',
		                    'pickr'];
		wp_register_script('elementor-editor', "{$pathToPluginDir}/editor.js", $requiredScripts, ELEMENTOR_VERSION, true);
	}
	function handleEditorLocalizeSettings($config) {
		$config['elementor_site'] = '#';
		$config['docs_elementor_site'] = '#';
		$config['help_the_content_url'] = '#';
		$config['help_preview_error_url'] = '#';
		$config['help_right_click_url'] = '#';
		$config['i18n']['home'] = __('Home');
		$config['i18n']['widgets_panel'] = __('Widgets Panel', 'elementor');
		$config['i18n']['go_to'] = __('Go to Dashboard');
		/*$config['i18n'] = [
			'go_to' => ,
			'elementor_settings' => __('Settings')];*/
		return $config;
	}
	function handleGetPlaceHolderImgSrc($placeholderImage) {
		if ($placeholderImage) {
			$placeholderImage = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAABLAAAAMgCAIAAAC8ggxVAAAACXBIWXMAAAsTAAALEwEAmpwYAAAZkElEQVR4nO3dUWvb2LrHYU9kCIkhFm6uUqZQ6Pf/RAcOnDKFDalxCmkpVGZfaG+f0KapHUtay/4/D3MxDSFZZcoov76vpL8+/et+BgAAQJ6L0gcAAACgDEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABAKEEIAAAQShACAACEEoQAAAChBCEAAEAoQQgAABBKEAIAAIQShAAAAKEEIQAAQChBCAAAEEoQAgAAhBKEAAAAoQQhAABA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AAAglCAEAAAIJQgBAABCCUIAAIBQghAAACCUIAQAAAglCAEAAEIJQgAAgFCCEAAAIJQgBAAACCUIAQAAQglCAACAUIIQAAAglCAEAAAIJQgBAABCCUIAAIBQghAAACCUIAQAAAglCAEAAEIJQgAAgFCCEAAAIJQgBAAACCUIAQAAQglCAACAUIIQAAAglCAEAAAIJQgBAABCCUIAAIBQghAAACCUIAQAAAglCAEAAEIJQgAAgFCCEAAAIJQgBAAACCUIAQAAQglCAACAUIIQAAAglCAEAAAIJQgBAABCCUIAAIBQghAAACCUIAQAAAglCAEAAEIJQgAAgFCCEAAAIJQgBAAACCUIAQAAQglCAACAUIIQAAAglCAEAAAIJQgBAABCCUIAAIBQghAAACCUIAQAAAglCAEAAEIJQgAAgFCCEAAAIJQgBAAACCUIAQAAQglCAACAUIIQAAAglCAEAAAIJQgBAABCCUIAAIBQghAAACCUIAQAAAglCAEAAEIJQgAAgFCCEAAAIJQgBAAACCUIAQAAQglCAACAUIIQAAAglCAEAAAIJQgBAABCCUIAAIBQghAAACCUIAQAAAglCAEAAEIJQgAAgFCCEAAAIJQgBAAACCUIAQAAQglCAACAUIIQAAAglCAEAAAIJQgBAABCCUIAAIBQghAAACCUIAQAAAglCAEAAEIJQgAAgFCCEAAAIJQgBAAACCUIAQAAQglCAACAUIIQAAAglCAEAAAIJQgBAABCCUIAAIBQghAAACCUIAQAAAglCAEAAEIJQgAAgFCCEAAAIJQgBAAACCUIAQAAQglCAACAUIIQAAAglCAEAAAIJQgBAABCCUIAAIBQghAAACCUIAQAAAglCAEAAEIJQgAAgFCCEAAAIJQgBAAACCUIAQAAQglCAACAUIIQAAAglCAEAAAIJQgBAABCCUIAAIBQghAAACCUIAQAAAglCAEAAEIJQgAAgFCCEAAAIJQgBAAACCUIAQAAQglCAACAUIIQAAAglCAEAAAIJQgBAABCCUIAAIBQghAAACCUIAQAAAglCAEAAEIJQgAAgFCCEAAAIJQgBAAACCUIAQAAQglCAACAUIIQAAAglCAEAAAIJQgBAABCCUIAAIBQghAAACCUIAQAAAglCAEAAEIJQgAAgFCCEAAAIJQgBAAACCUIAQAAQglCAACAUIIQAAAglCAEAAAIJQgBAABCCUIAAIBQghAAACCUIAQAAAglCAEAAEIJQgAAgFCCEAAAIJQgBAAACCUIAQAAQglCAACAUIIQAAAglCAEAAAIJQgBAABCCUIAAIBQghAAACCUIAQAAAglCAEAAEIJQgAAgFCCEAAAIJQgBAAACCUIAQAAQglCAACAUIIQAAAglCAEAAAIJQgBAABCCUIAAIBQghAAACCUIAQAAAglCAEAAEIJQgAAgFCCEAAAIJQgBAAACCUIAQAAQglCAACAUIIQAAAglCAEAAAIJQgBAABCCUIAAIBQghAAACCUIAQAAAglCAEAAEIJQgAAgFCCEAAAIJQgBAAACCUIAQAAQglCAACAUIIQAAAglCAEAAAIJQgBAABCCUIAAIBQghAAACDUvwHpcB0NCougigAAAABJRU5ErkJggg==";
		}
		return $placeholderImage;
	}
}

PluginsBranding::i();