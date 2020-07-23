<?php

/*
 * This file is part of WpAlgolia library.
 * (c) Raymond Rutjes for Algolia <raymond.rutjes@algolia.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace WpAlgolia;

use WP_CLI;
use WP_CLI_Command;

class Commands extends WP_CLI_Command
{
    /**
     * @var Main
     */
    private $instance;

    /**
     * @param InMemoryIndexRepository $indexRepository
     */
    public function __construct(Main $instance)
    {
        $this->instance = $instance;
    }


    /**
     * Get registered post-type from Main $instance
     *
     * @param string $indexName
     * @return class
     */
    private function get_registered_post_type($indexName) {
        try {
            return $this->instance->registered_post_types[$indexName];
        } catch (\Throwable $th) {
            return null;
        }
    }

    /**
     * List all available index.
     *
     *
     * ## EXAMPLES
     *
     *     wp algolia list
     *
     * @when before_wp_load
     *
     */

    public function list() {
        foreach ($this->instance->registered_post_types as $key => $registered_post_type) {
            WP_CLI::line($registered_post_type->get_post_type());
        }
    }

    /**
     * Push all records to Algolia for a given post type.
     *
     * ## OPTIONS
     *
     * <indexName>
     * : The key of the index.
     * ---
     * default: all
     * ---
     *
     * [--all]
     * : if should index all
     *
     * ## EXAMPLES
     *
     *     wp algolia re-index articles
     *     wp algolia re-index --all
     *
     * @when before_wp_load
     *
     * @param mixed $args
     * @param mixed $assoc_args
     */
    public function reIndex($args, $assoc_args)
    {
        list($indexName) = $args;

        // index all registred instances
        if($assoc_args['all']) {
            foreach ($this->instance->registered_post_types as $key => $registered_post_type) {
                WP_CLI::line(sprintf("Start indexing '%s'...", $registered_post_type->get_post_type()));
                $registered_post_type->cli_reindex();
            }

            // Then should stop command here
            return;
        }

        // get registered post type
        $indexInstance = $this->get_registered_post_type($indexName);

        if( !$indexInstance ) {
            WP_CLI::error(sprintf("Index for post type '%s' is not a registered index.", $indexName));
            return;
        }

        // run reindex
        $indexInstance->cli_reindex();
    }

}