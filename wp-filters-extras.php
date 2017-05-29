<?php
// Original Author: https://gist.github.com/tripflex/c6518efc1753cf2392559866b4bd1a53

if ( ! function_exists('remove_filters_bymethod_name') ) {
    /**
     * Remove Class Filter Without Access to Class Object
     *
     * In order to use the core WordPress remove_filter() on a filter added with the callback
     * to a class, you either have to have access to that class object, or it has to be a call
     * to a static method.  This method allows you to remove filters with a callback to a class
     * you don't have access to.
     *
     * Works with WordPress 4.7+ using internal WordPress removal
     *
     * @param string $filter_tag   Filter to remove
     * @param string $class_name  Optional class name for the filter's callback
     * @param string $method_name Method name for the filter's callback
     * @param int    $priority    Priority of the filter (default 10)
     *
     * @return bool Whether the function is removed.
     */
    function remove_filters_bymethod_name( $filter_tag, $class_name = '', $method_name = '', $priority = 10 ) {
        global $wp_filter;

        // Check that filter actually exists first
        if ( ! isset($wp_filter[$filter_tag]) ) return FALSE;

        /**
         * If filter config is an object, means we're using WordPress 4.7+ and the config is no longer
         * a simple array, rather it is an object that implements the ArrayAccess interface.
         *
         * To be backwards compatible, we set $callbacks equal to the correct array as a reference (so $wp_filter is updated)
         *
         * @see https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/
         */
        if ( is_object($wp_filter[$filter_tag]) && isset($wp_filter[$filter_tag]->callbacks) ) {
            // Create $filter_object object from filter tag, to use below
            $filter_object = $wp_filter[$filter_tag];
            $callbacks = &$wp_filter[$filter_tag]->callbacks;
        } else {
            $callbacks = &$wp_filter[$filter_tag];
        }

        // Exit if there aren't any callbacks for specified priority
        if ( ! isset( $callbacks[ $priority ] ) || empty( $callbacks[ $priority ] ) ) return FALSE;

        // Loop through each filter for the specified priority, looking for our class & method
        foreach( (array) $callbacks[ $priority ] as $filter_id => $filter_array ) {

            // Filter should always be an array - array( $this, 'method' ), if not goto next
            if ( ! isset( $filter_array[ 'function' ] ) || ! is_array( $filter_array[ 'function' ] ) ) continue;

            // If first value in array is not an object, it can't be a class
            if ( ! is_object( $filter_array[ 'function' ][ 0 ] ) ) continue;

            // Method doesn't match the one we're looking for, goto next
            if ( $filter_array[ 'function' ][ 1 ] !== $method_name ) continue;

            // Method matched, now let's check the Class
            if ( get_class( $filter_array[ 'function' ][ 0 ] ) === $class_name ) {

                // WordPress 4.7+ use core remove_filter() since we found the class object
                if( isset( $filter_object ) ){
                    // Handles removing filter, reseting callback priority keys mid-iteration, etc.
                    $filter_object->remove_filter($filter_tag, $filter_array['function'], $priority );

                } else {
                    // Use legacy removal process (pre 4.7)
                    unset( $callbacks[ $priority ][ $filter_id ] );
                    // and if it was the only filter in that priority, unset that priority
                    if ( empty( $callbacks[ $priority ] ) ) {
                        unset( $callbacks[ $priority ] );
                    }
                    // and if the only filter for that tag, set the tag to an empty array
                    if ( empty( $callbacks ) ) {
                        $callbacks = array();
                    }
                    // Remove this filter from merged_filters, which specifies if filters have been sorted
                    unset( $GLOBALS['merged_filters'][$filter_tag] );
                }

                return TRUE;
            } elseif ( empty( $class_name ) && get_class($filter_array['function'][0]) ) {
                if( isset( $filter_object ) ){
                    // Handles removing filter, reseting callback priority keys mid-iteration, etc.
                    $filter_object->remove_filter($filter_tag, $filter_array['function'], $priority );

                } else {
                    // Use legacy removal process (pre 4.7)
                    unset( $callbacks[ $priority ][ $filter_id ] );
                    // and if it was the only filter in that priority, unset that priority
                    if ( empty( $callbacks[ $priority ] ) ) {
                        unset( $callbacks[ $priority ] );
                    }
                    // and if the only filter for that tag, set the tag to an empty array
                    if ( empty( $callbacks ) ) {
                        $callbacks = array();
                    }
                    // Remove this filter from merged_filters, which specifies if filters have been sorted
                    unset( $GLOBALS['merged_filters'][$filter_tag] );
                }

                return TRUE;
            }
        }

        return FALSE;
    }
}

if ( ! function_exists('remove_actions_bymethod_name') ) {
    /**
     * Remove Class Action Without Access to Class Object
     *
     * In order to use the core WordPress remove_action() on an action added with the callback
     * to a class, you either have to have access to that class object, or it has to be a call
     * to a static method.  This method allows you to remove actions with a callback to a class
     * you don't have access to.
     *
     * Works with WordPress 4.7+ versions
     *
     * @param string $action_hook   Action to remove
     * @param string $class_name  Optional class name for the action's callback
     * @param string $method_name Method name for the action's callback
     * @param int    $priority    Priority of the action (default 10)
     *
     * @return bool               Whether the function is removed.
     */
    function remove_actions_bymethod_name( $action_hook, $class_name = '', $method_name = '', $priority = 10 ) {
        return remove_filters_bymethod_name( $action_hook, $class_name, $method_name, $priority );
    }
}

