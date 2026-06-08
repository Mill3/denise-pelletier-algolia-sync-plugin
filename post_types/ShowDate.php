<?php

/**
 * This file is part of WpAlgolia plugin.
 * (c) Antoine Girard for Mill3 Studio <antoine@mill3.studio>
 *
 * @version 0.0.7
 */

namespace WpAlgolia\Register;

use DateTimeZone;
use IntlDateFormatter;
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

    public function extraFields($data, $post)
    {
        $postID = $post->ID;

        $date = get_field('date', $postID, false);

        if (!$date) return $data;

        $parsed_date = new \DateTime($date, new DateTimeZone('America/Toronto'));

        $data['time_period'] = $this->setTimePeriod($parsed_date);

        try {
            $fmt_weekday = new IntlDateFormatter('fr_CA', IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'America/Toronto', null, 'EEEE');
            $fmt_month   = new IntlDateFormatter('fr_CA', IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'America/Toronto', null, 'MMMM');
            $fmt_my      = new IntlDateFormatter('fr_CA', IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'America/Toronto', null, 'MMMM YYYY');

            $data['day']        = $parsed_date->format('j');
            $data['weekday']    = $fmt_weekday->format($parsed_date);
            $data['month']      = ucfirst($fmt_month->format($parsed_date));
            $data['month_year'] = ucfirst($fmt_my->format($parsed_date));
            $data['year']       = $parsed_date->format('Y');
            $data['time']       = $parsed_date->format('G:i');
            $data['timestamp']  = $parsed_date->getTimestamp() * 1000;
        } catch (\Throwable $th) {
            // continue without date fields
        }

        $data['show_type']    = $this->setShowType($post->ID);
        $data['meet_artists'] = $this->setMeetArtists($post->ID);

        $show = $this->getShow($postID);

        if ($show && $show->post_title) {
            $data['show_date_group'] = $this->createAttributeForDistinct($post, $show, $parsed_date);
            $data['post_thumbnail']  = get_the_post_thumbnail_url($show, 'post-thumbnail');
            $data['show_title']      = $show->post_title;
            $data['duration']        = get_field('duration', $show->ID);
            $data['intermission']    = get_field('intermission', $show->ID);

            $room         = $this->getShowRoom($show->ID);
            $data['room'] = $room ? $room[0]->post_title : false;
        }

        return $data;
    }

    private function createAttributeForDistinct($post, $show, \DateTime $parsed_date): ?string
    {
        try {
            $date_slug = strtolower($parsed_date->format('j-F-Y'));
            return implode('-', [isset($show->post_name) ? $show->post_name : null, $date_slug]);
        } catch (\Throwable $th) {
            return null;
        }
    }

    private function getShow($postID)
    {
        $show = get_field('show', $postID);
        if ($show) {
            $show = is_object($show) ? $show : $show[0];
            return $show;
        }
        return null;
    }

    private function getShowRoom($postID)
    {
        $room = get_field('room', $postID);
        return isset($room) ? $room : null;
    }

    private function setTimePeriod(\DateTime $parsed_date): string
    {
        // shows starting at 18:00 or later are considered evening
        return (int) $parsed_date->format('H') >= 18 ? 'Soir' : 'Après-midi';
    }

    private function setShowType($postID): string
    {
        return get_field('school_only', $postID) ? 'Scolaire' : 'Grand public';
    }

    private function setMeetArtists($postID): bool
    {
        return (bool) get_field('meet_artists', $postID);
    }
}
