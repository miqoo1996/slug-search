<?php

/**
 * Plugin Name: Search By Slug
 * Plugin URI: https://www.your-site.com/
 * Description: Search By Slug everywhere inside /wp-admin area. (SUPPORTS PHP7.4 or HIGHER)
 * Version: 0.1
 * Author: Michael Ish <miqoo1996@gmail.com>
 * Author URI: https://www.your-site.com/
 **/


class WP_Search_By_Slug
{
    public const DEFAULT_SEARCHABLE_TYPES = ['post', 'page'];

    /**
     * add the supported types here.
     */
    public array $searchableTypes = self::DEFAULT_SEARCHABLE_TYPES;

    /**
     * @var wpdb $wpdb
     */
    protected wpdb $wpdb;

    /**
     * @var string $prefix
     */
    protected string $prefix = 'slug:';

    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;

        add_filter( 'posts_search', [$this, 'search'], 10, 2 );
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * @return array|string[]
     */
    public function getSearchableTypes(): array
    {
        return $this->searchableTypes;
    }

    /**
     * @param array $searchableTypes
     * @return $this
     */
    public function setSearchableTypes(array $searchableTypes): self
    {
        $this->searchableTypes = $searchableTypes;

        return $this;
    }

    /**
     * Can search
     *
     * Only run if we're in the admin and searching our specific post type
     *
     * @param WP_Query $query
     * @return bool
     */
    public function canSearch(WP_Query $query) : bool
    {
        return $query->is_search() && $query->is_admin && !empty($query->query_vars['search_terms']) &&
            !empty($this->getTerms($query)) && in_array($query->query_vars['post_type'], $this->searchableTypes);
    }

    /**
     * Get terms.
     *
     * @param WP_Query $query
     * @return array
     */
    public function getTerms(WP_Query $query) : array
    {
        static $terms = [];

        if (empty($terms) && isset($query->query_vars['search_terms'])) {
            $terms = array_filter($query->query_vars['search_terms'], function ($term) {
                return preg_match("/^($this->prefix)/", $term);
            });
        }

        return $terms;
    }

    /**
     * Walk on array for terms.
     *
     * Returns the terms which contain the "$this->prefix" text and able to be processed.
     *
     * @param WP_Query $query
     * @param Closure $closure
     * @return array
     */
    protected function walkTerms(WP_Query $query, Closure $closure) : array
    {
        return array_filter($this->getTerms($query), $closure);
    }

    /**
     * Perform Search
     *
     * @param string|null $search
     * @param WP_Query $query
     * @return string|void|null
     */
    public function search(?string $search, WP_Query $query)
    {
        if (! $this->canSearch($query)) {
            return $search;
        }

        // We will rebuild the entire clause
        $search = $searchand = '';

        $this->walkTerms($query, function (string $term) use (&$search, &$searchand) {
            if (! preg_match("/^($this->prefix)/", $term)) {
                return false;
            }

            $term = str_replace($this->prefix, '', $term);

            $like = '%' . $this->wpdb->esc_like($term) . '%';
            $search .= $this->wpdb->prepare("{$searchand}(({$this->wpdb->posts}.post_name LIKE %s))", $like);
            $searchand = ' AND ';

            return true;
        });

        if (!empty($search)) {
            $search = " AND ({$search}) ";
        }

        return $search;
    }
}

$searchBySlug = new WP_Search_By_Slug();