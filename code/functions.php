<?php
/**
 * Fix 404 for permalinks using category_base
 * @see https://wordpress.stackexchange.com/questions/98083/how-to-get-permalinks-with-category-base-working-with-sub-categories
 */
function permalinkWithCategoryBaseFix() {
    global $wp_query;
    // Only check on 404's
    if ( true === $wp_query->is_404) {
        $currentURI = !empty($_SERVER['REQUEST_URI']) ? trim($_SERVER['REQUEST_URI'], '/') : '';
        if ($currentURI) {
            $categoryBaseName = trim(get_option('category_base'), '/.'); // Remove / and . from base
            if ($categoryBaseName) {
                // Perform fixes for category_base matching start of permalink custom structure
                if ( substr($currentURI, 0, strlen($categoryBaseName)) == $categoryBaseName ) {
                    // Find the proper category
                    $childCategoryObject = get_category_by_slug($wp_query->query_vars['name']);
                    // Make sure we have a category
                    if (is_object($childCategoryObject)) {
                        $paged = ($wp_query->query_vars['paged']) ? $wp_query->query_vars['paged']: 1;
                        $wp_query->query(array(
                                              'cat' => $childCategoryObject->term_id,
                                              'paged'=> $paged
                                         )
                        );
                        // Set our accepted header
                        status_header( 200 ); // Prevents 404 status
                    }
                    unset($childCategoryObject);
                }
            }
            unset($categoryBaseName);
        }
        unset($currentURI);
    }
}

add_action('template_redirect', 'permalinkWithCategoryBaseFix');


/**
 * Fix the problem where next/previous of page number buttons are broken of posts in a category when the custom permalink
 *
 * The problem is that with a url like this: /categoryname/page/2 the 'page' looks like a post name, not the keyword "page"
 * @see https://wordpress.stackexchange.com/questions/98083/how-to-get-permalinks-with-category-base-working-with-sub-categories#98095
 */
function fixCategoryPagination($queryString) {
    if (isset($queryString['name']) && $queryString['name'] == 'page' && isset($queryString['page'])) {
        unset($queryString['name']);
        // 'page' in the query_string looks like '/2', so i'm exploding it
        list($delim, $page_index) = explode('/', $queryString['page']);
        $queryString['paged'] = $page_index;
    }
    return $queryString;
}

add_filter('request', 'fixCategoryPagination');
