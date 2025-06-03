<?php

namespace Includes\baseClasses;


use Automatic_Upgrader_Skin;
use Plugin_Upgrader;

class STGetDependency
{
    protected $include_files = false;
    protected $require_plugins;
    protected $installed_plugins;

    public function __construct($require_plugins)
    {
        $this->require_plugins = $require_plugins;
    }

    public function getPlugin()
    {
        $this->installed_plugins    = $this->installedPlugins();
        foreach ($this->require_plugins as $plugin) {
            if (key_exists($plugin, $this->installed_plugins)) {
                if (!is_plugin_active($this->installed_plugins[$plugin])) {
                    activate_plugin($this->callPluginPath(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $this->installed_plugins[$plugin]), '', false, false);
                }
            } else {
                $plugin_data = $this->getPluginData($plugin);
                if (isset($plugin_data->download_link)) {
                    $this->installPlugin($plugin_data->download_link);
                }
            }
        }
    }

    public function installedPlugins()
    {
        $installed_plugins  = get_plugins();
        $textdomains        = array_column($installed_plugins, 'TextDomain');
        $basenames          = array_keys($installed_plugins);
        return array_combine($textdomains, $basenames);
    }

    public function getPluginData($slug = '')
    {
        $args = array(
            'slug'      => $slug,
            'fields'    => array(
                'version' => false,
            ),
        );

        $response = wp_remote_post(
            'http://api.wordpress.org/plugins/info/1.0/',
            array(
                'body' => array(
                    'action' => 'plugin_information',
                    'request' => serialize((object) $args),
                ),
            )
        );

        if (is_wp_error($response)) {
            return false;
        } else {
            $response = unserialize(wp_remote_retrieve_body($response));
            if ($response)
                return $response;
        }

        return false;
    }

    public function installPlugin($plugin_url)
    {
        if (!$this->include_files) {
            include_once ABSPATH . 'wp-admin/includes/file.php';
            include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            include_once ABSPATH . 'wp-admin/includes/class-automatic-upgrader-skin.php';
        }
        $skin       = new Automatic_Upgrader_Skin;
        $upgrade    = new Plugin_Upgrader($skin);
        $upgrade->install($plugin_url);
        $this->include_files = true;
        // activate plugin
        activate_plugin($upgrade->plugin_info(), '', false, false);

        return $skin->result;
    }

    public function callPluginPath($path)
    {
        $path = str_replace(['//', '\\\\'], ['/', '\\'], $path);
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }
}
