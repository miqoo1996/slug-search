============ Search by Slug ============

Plugin supports PHP 7.4 or higher

============ Usage outside the plugin ==========

if ( class_exists( 'WP_Search_By_Slug' ) ) {
    global $searchBySlug;

    $searchBySlug->setPrefix('slug:')->setSearchableTypes(WP_Search_By_Slug::DEFAULT_SEARCHABLE_TYPES);
}