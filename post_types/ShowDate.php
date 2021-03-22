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

        // stop here if no date found
        if( !$date ) return $data;

        // set mid-day at in Carbon, we assume any show that start after 6PM is set in the evening
        Carbon::setMidDayAt(18);

        // parse date with Carbon lib
        $parsed_date = $date ? new Carbon($date, 'America/Toronto') : null;

        // set the time_period : matin, soir, etc.
        $data['time_period'] = $this->setTimePeriod($parsed_date);

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

        // set show type if school_only, etc.
        $data['show_type'] = $this->setShowType($post->ID);

        // get related show
        $show = $this->getShow($postID);

        if ($show && $show->post_title) {
            // generate AttributeForDistinct facetting
            $data['show_date_group'] = $this->createAttributeForDistinct($post, $show, $parsed_date);

            // post thumbnail from show
            $data['post_thumbnail'] = get_the_post_thumbnail_url($show, 'post-thumbnail');
            $data['show_title'] = $show->post_title;
            $data['duration'] = get_field('duration', $show->ID);
            $data['intermission'] = get_field('intermission', $show->ID);

            // find room
            $room = $this->getShowRoom($show->ID);
            $data['room'] = $room ? $room[0]->post_title : false;
        }

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
        if ($show) {
            // ACF do *not* always return object
            $show = is_object($show) ? $show : $show[0];
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

    private function setTimePeriod($parsed_date)
    {
        // if datetime is between mid-day and end of the day, return evening
        if ( $parsed_date->isBetween($parsed_date->copy()->midDay(), $parsed_date->copy()->endOfDay()) === true ) {
            return "Soir";
        }

        // defaults to afternoon
        return "Après-midi";
    }

    private function setShowType($postID)
    {
        return get_field('school_only', $postID) ? "Scolaire" : "Grand public";
    }
}
