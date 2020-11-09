<?php

/**
 * This file is part of WpAlgolia plugin.
 * (c) Antoine Girard for Mill3 Studio <antoine@mill3.studio>
 *
 * @version 0.0.7
 */

namespace WpAlgolia\Register;

use Carbon\Carbon;
use WpAlgolia\RegisterAbstract as WpAlgoliaRegisterAbstract;
use WpAlgolia\RegisterInterface as WpAlgoliaRegisterInterface;

class ShowDate extends WpAlgoliaRegisterAbstract implements WpAlgoliaRegisterInterface
{
    public $searchable_fields = array('post_title', 'uuid', 'show', 'date', 'school_only', 'tuxedo_soldout', 'tuxedo_url', 'tuxedo_venue_id');

    public $acf_fields = array('uuid', 'date', 'school_only', 'tuxedo_soldout', 'tuxedo_url', 'tuxedo_venue_id');

    public $taxonomies = array();

    public function __construct($post_type, $index_name, $algolia_client)
    {
        $index_config = array(
            'acf_fields'        => $this->acf_fields,
            'taxonomies'        => $this->taxonomies,
            'post_type'         => $post_type,
            'hidden_flag_field' => 'search_hidden',
            'config'            => array(
                'searchableAttributes'  => $this->searchableAttributes(),
                'customRanking'         => array('asc(post_title)'),
                // 'attributesForFaceting' => array(''),
                'queryLanguages'        => array('fr'),
            ),
            array(
               'forwardToReplicas' => true,
            ),
        );

        parent::__construct($post_type, $index_name, $algolia_client, $index_config);
    }

    public function searchableAttributes()
    {
        return array_merge($this->searchable_fields, $this->acf_fields, $this->taxonomies);
    }

    // implement any special data handling for post type here
    public function extraFields($data, $post)
    {
        $date_locale = 'fr';

        // extra postID
        $postID = $post->ID;

        // get date
        $date = get_field('date', $postID, false);

        // parse dates with Carbon lib
        $parsed_date = $date ? Carbon::parse($date) : null;

        // send day, month and year as seperate field value to index
        try {
            $data['day'] = $parsed_date->locale($date_locale)->isoFormat('D');
            $data['weekday'] = $parsed_date->locale($date_locale)->isoFormat('dddd');
            $data['month'] = ucfirst($parsed_date->locale($date_locale)->isoFormat('MMMM'));
            $data['month_year'] = ucfirst($parsed_date->locale($date_locale)->isoFormat('MMMM YYYY'));
            $data['year'] = $parsed_date->locale($date_locale)->isoFormat('YYYY');
            $data['time'] = $parsed_date->locale($date_locale)->isoFormat('H:mm');
            // convert php timestamp from epoch to milliseconds
            $data['timestamp'] = $parsed_date->getTimestamp() * 1000;
        } catch (\Throwable $th) {
            //throw $th;
        }

        // get related show
        $show = $this->getShow($postID);

        // generate AttributeForDistinct facetting
        $data['show_date_group'] = $this->createAttributeForDistinct($post, $show, $parsed_date);

        // post thumbnail from show
        $data['post_thumbnail'] = $show ? get_the_post_thumbnail_url($show, 'post-thumbnail') : false;
        $data['show_title'] = $show ? $show->post_title : false;
        $data['duration'] = $show ? get_field('duration', $show->ID) : false;
        $data['intermission'] = $show ? get_field('intermission', $show->ID) : false;

        // find room
        $room = $show ? $this->getShowRoom($show->ID) : false;
        $data['room'] = $room ? $room[0]->post_title : false;

        return $data;
    }

    private function createAttributeForDistinct($post, $show, $parsed_date)
    {
        try {
            // create a date string as slug
            $date_slug = strtolower($parsed_date->locale('en')->isoFormat('D-MMMM-YYYY'));

            // join all that with show slug in front
            return implode([isset($show->post_name) ? $show->post_name : null, $date_slug], '-');
        } catch (\Throwable $th) {
            return null;
        }
    }

    private function getShow($postID)
    {
        $show = get_field('show', $postID);
        if (isset($show)) {
            return $show;
        } else {
            return null;
        }
    }


    private function getShowRoom($postID)
    {
        $room = get_field('room', $postID);
        if (isset($room)) {
            return $room;
        } else {
            return null;
        }
    }
}
