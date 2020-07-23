<?php

/**
 * GitHub Plugin URI:  Mill3/poleacn-algolia-sync-plugin
 * GitHub Plugin URI:  https://github.com/Mill3/poleacn-algolia-sync-plugin
 * Plugin Name: Poleacn - Algolia Sync
 * Description: Sync data from Wordpress to Algolia
 * Version: 0.4.3
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
        $this->registered_post_types['companies'] = new \WpAlgolia\Register\Companies('companies', ALGOLIA_PREFIX . 'content', $this->algolia_client);
        $this->registered_post_types['jobs'] = new \WpAlgolia\Register\Jobs('jobs', ALGOLIA_PREFIX . 'content', $this->algolia_client);
        $this->registered_post_types['formations'] = new \WpAlgolia\Register\Formations('formations', ALGOLIA_PREFIX . 'content', $this->algolia_client);
        $this->registered_post_types['schools'] = new \WpAlgolia\Register\Schools('schools', ALGOLIA_PREFIX . 'content', $this->algolia_client);
        $this->registered_post_types['stages'] = new \WpAlgolia\Register\Stages('stages', ALGOLIA_PREFIX . 'content', $this->algolia_client);
        $this->registered_post_types['events'] = new \WpAlgolia\Register\Events('events', ALGOLIA_PREFIX . 'content', $this->algolia_client);
        $this->registered_post_types['researches'] = new \WpAlgolia\Register\Researches('researches', ALGOLIA_PREFIX . 'content', $this->algolia_client);
        $this->registered_post_types['research_chairs'] = new \WpAlgolia\Register\ResearchChairs('research_chairs', ALGOLIA_PREFIX . 'content', $this->algolia_client);
        $this->registered_post_types['research_projects'] = new \WpAlgolia\Register\ResearchProjects('research_projects', ALGOLIA_PREFIX . 'content', $this->algolia_client);
        $this->registered_post_types['research_incubators'] = new \WpAlgolia\Register\ResearchIncubators('research_incubators', ALGOLIA_PREFIX . 'content', $this->algolia_client);
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
        // require_once __DIR__ . '/post_types/Posts.php';
        require_once __DIR__ . '/post_types/Pages.php';
        require_once __DIR__ . '/post_types/Companies.php';
        require_once __DIR__ . '/post_types/Jobs.php';
        require_once __DIR__ . '/post_types/Formations.php';
        require_once __DIR__ . '/post_types/Schools.php';
        require_once __DIR__ . '/post_types/Stages.php';
        require_once __DIR__ . '/post_types/Events.php';
        require_once __DIR__ . '/post_types/Researches.php';
        require_once __DIR__ . '/post_types/ResearchChairs.php';
        require_once __DIR__ . '/post_types/ResearchProjects.php';
        require_once __DIR__ . '/post_types/ResearchIncubators.php';

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
