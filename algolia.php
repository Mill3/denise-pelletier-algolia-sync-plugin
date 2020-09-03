<?php

/**
 * GitHub Plugin URI:  Mill3/denise-pelletier-algolia-sync-plugin
 * GitHub Plugin URI:  https://github.com/Mill3/denise-pelletier-algolia-sync-plugin
 * Plugin Name: TDP - Algolia Sync
 * Description: Sync data from Wordpress to Algolia
 * Version: 0.4.5
 * Author Name: Mill3 Studio (Antoine Girard)
 *
 * @package CSTJ_Algolia_Sync
 */

namespace WpAlgolia;
class Main {

    public $algolia_client;

    public $registered_post_types = array();

    public function __construct($algolia_client) {
        $this->algolia_client = $algolia_client;
    }

    public function run() {
        return $this->register();
    }

    public function search() {
        return null;
    }

    private function register() {
        $this->registered_post_types['post'] = new \WpAlgolia\Register\Post('post', ALGOLIA_PREFIX . 'content', $this->algolia_client);
        $this->registered_post_types['show'] = new \WpAlgolia\Register\Show('show', ALGOLIA_PREFIX . 'content', $this->algolia_client);
        $this->registered_post_types['page'] = new \WpAlgolia\Register\Page('page', ALGOLIA_PREFIX . 'content', $this->algolia_client);
        $this->registered_post_types['biography'] = new \WpAlgolia\Register\Biography('biography', ALGOLIA_PREFIX . 'content', $this->algolia_client);
    }

}

add_action(
    'plugins_loaded',
    function () {

        if(!defined('ALGOLIA_APPLICATION_ID') || !defined('ALGOLIA_ADMIN_API_KEY')) {
            // Unless we have access to the Algolia credentials, stop here.
            return;
        }

        if(!defined('ALGOLIA_PREFIX')) {
            define('ALGOLIA_PREFIX', 'prod_');
        }

        require_once __DIR__ . '/vendor/autoload.php';
        require_once __DIR__ . '/inc/AlgoliaIndex.php';
        require_once __DIR__ . '/inc/RegisterAbstract.php';
        require_once __DIR__ . '/inc/RegisterInterface.php';

        // available post types
        require_once __DIR__ . '/post_types/Post.php';
        require_once __DIR__ . '/post_types/Show.php';
        require_once __DIR__ . '/post_types/Page.php';
        require_once __DIR__ . '/post_types/Biography.php';

        // client
        $algoliaClient = \Algolia\AlgoliaSearch\SearchClient::create(ALGOLIA_APPLICATION_ID, ALGOLIA_ADMIN_API_KEY);

        // instance with supported post types
        $instance = new \WpAlgolia\Main($algoliaClient);

        // run
        $instance->run();

        // WP CLI commands.
        if (defined('WP_CLI') && WP_CLI && $instance) {
            require_once __DIR__ . '/inc/Commands.php';
            $commands = new \WpAlgolia\Commands($instance);
            \WP_CLI::add_command('algolia', $commands);
        }

    }
);
