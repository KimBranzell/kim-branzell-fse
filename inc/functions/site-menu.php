<?php

function my_get_menu_tree( $location ) {
	$locations = get_nav_menu_locations();

	if ( ! isset( $locations[ $location ] ) ) {
		return array();
	}

	$menu = wp_get_nav_menu_object( $locations[ $location ] );
	if ( ! $menu ) {
		return array();
	}

	$items = wp_get_nav_menu_items( $menu->term_id );
	if ( ! $items ) {
		return array();
	}

	$tree    = array();
	$indexed = array();

	foreach ( $items as $item ) {
		$item->children       = array();
		$indexed[ $item->ID ] = $item;
	}

	foreach ( $indexed as $item ) {
		if ( $item->menu_item_parent && isset( $indexed[ $item->menu_item_parent ] ) ) {
			$indexed[ $item->menu_item_parent ]->children[] = $item;
		} else {
			$tree[] = $item;
		}
	}

	return $tree;
}

function kb_data_description( $item ): string {
	return ! empty( $item->description )
	? ' data-description="' . esc_attr( wp_strip_all_tags( $item->description ) ) . '"'
	: '';
}
