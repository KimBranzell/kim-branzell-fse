<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<a class="skip-link" href="#main-content"><?php esc_html_e( 'Skip to content', 'kim-branzell' ); ?></a>
<header class="site-header">
    <div class="site-header__top">
      <div class="site-header__inner">
        <div class="site-header__title">
          <svg class="site-header__logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 910 882" aria-hidden="true" focusable="false">
            <path d="M6.01 867.64c0-.61 9-16.99 20.06-36.43 11.26-19.44 33.36-58.94 49.53-87.59s47.89-85.54 70.81-126.27 54.44-96.8 69.99-124.84c37.04-66.1 58.73-104.99 157.99-280.99 45.43-80.43 89.02-157.99 97-172.32L485.92 13h84.52c46.46 0 84.11.61 83.7 1.64-2.87 4.5-65.28 114.81-96.6 170.27-20.06 35.4-46.25 81.45-57.92 102.33-11.87 20.87-36.43 64.67-54.64 97.21-31.72 56.48-90.66 160.86-128.32 227.16-9.62 16.99-46.25 81.66-81.45 143.87l-63.85 112.97H88.68c-45.43 0-82.68-.41-82.68-.82zm276.28-.41c0-2.25 79-144.07 202.4-363.05C619.56 264.53 637.56 233.02 640.84 232.4c2.05-.41 5.12 2.87 8.8 8.8 7.37 12.89 30.08 36.43 45.02 47.07 15.55 11.05 33.77 20.26 48.71 24.56 6.34 1.84 11.67 3.89 11.67 4.5 0 1.23-30.9 55.87-76.13 134.05l-30.29 52.6 38.07 67.13c45.23 79.4 111.53 196.06 144.28 253.36 12.89 22.51 23.74 41.54 23.94 42.57.41.82-36.43 1.23-81.66 1.02l-82.47-.61-61.6-107.85c-33.77-59.35-61.8-108.06-62.42-107.85-.61 0-27.22 47.07-59.35 104.37-31.93 57.51-59.35 106.21-60.99 108.47-2.66 3.68-6.14 3.89-83.5 3.89-44.41 0-80.63-.61-80.63-1.23zm507.54-643.42c-54.64-9.82-90.87-63.03-79.61-117.06 11.46-54.64 66.92-90.05 119.52-76.33 28.65 7.57 49.53 24.56 62.42 50.75 15.35 31.31 15.35 59.35 0 90.66-18.83 38.47-60.17 59.35-102.33 51.98"/>
          </svg>
          <a class="site-header__name" href="<?php echo home_url(); ?>">
            <?php bloginfo('name'); ?>
          </a>
        </div>
        <nav id="HeaderNav" class="site-header__navigation" aria-label="<?php esc_attr_e( 'Primary Menu', 'kim-branzell' ); ?>">
          <?php
            $menu_items = my_get_menu_tree('primary');
          ?>
          <?php if ( !empty($menu_items) ) : ?>
          <ul>
          <?php foreach ($menu_items as $item) : ?>
            <?php
              $has_mega_menu = ! empty( $item->classes ) && in_array( 'mega-menu', (array) $item->classes, true );
              $is_active = $item->object_id == get_the_ID();
            ?>
            <li class="nav-item<?php echo !empty($item->children) ? ' has-children' : ''; ?><?php echo $has_mega_menu ? ' has-mega-menu' : ''; ?><?php echo $is_active ? ' is-active' : ''; ?>">
                <a href="<?php echo esc_url($item->url); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>>
                  <?php echo esc_html($item->title); ?>
                </a>
                <?php if (!empty($item->children)) : ?>
                  <button id="parent-<?php echo $item->ID; ?>" class="submenu-toggle" aria-controls="submenu-<?php echo esc_attr($item->ID); ?>" aria-expanded="false" aria-label="Toggle submenu for <?php echo esc_attr($item->title); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="currentColor" d="M6.102 8c-1.074 0-1.648 1.265-.94 2.073l5.52 6.31a1.75 1.75 0 0 0 2.635 0l5.522-6.31c.707-.808.133-2.073-.941-2.073z"/>
                    </svg>
                  </button>
                <?php endif; ?>
                <?php if (!empty($item->children)) : ?>
                    <?php if ( $has_mega_menu ) : ?>
                    <div role="region" class="children" aria-labelledby="parent-<?php echo $item->ID; ?>" id="submenu-<?php echo esc_attr($item->ID); ?>">
                      <div class="mega-menu__grid">
                        <div class="mega-menu__title-description">
                          <h2><?php echo $item->title; ?></h2>
                          <div>
                            <?php echo $item->description; ?>
                            <a class="btn" href="<?php echo esc_url($item->url); ?>">
                              <?php esc_html_e( 'Read more', 'kim-branzell' ); ?>
                              <span class="screen-reader-text">
                                <?php
                                /* translators: %s: Menu item title. */
                                printf( esc_html__( 'about %s', 'kim-branzell' ), $item->title );
                                ?>
                              </span>
                            </a>
                          </div>
                        </div>
                        <?php foreach ($item->children as $child) : ?>
                          <div class="mega-menu__column">
                            <div class="mega-menu__column--header">
                              <h3 class="mega-menu__heading"><?php echo esc_html( $child->title ); ?></h3>
                              <div class="mega-menu__description">
                                <?php echo $child->description; ?>
                              </div>
                            </div>
                            <?php if($child->children): ?>
                              <ul>
                                <?php foreach ( $child->children as $sub ) : ?>
                                <li><a href="<?php echo esc_url( $sub->url ); ?>"<?php echo kb_data_description( $sub ); ?>><?php echo esc_html( $sub->title ); ?></a></li>
                                <?php endforeach; ?>
                              </ul>
                            <?php endif; ?>
                          </div>
                        <?php endforeach; ?>

                      </div>
                    </div>
                    <?php else : ?>
                    <div role="region" class="children" aria-labelledby="parent-<?php echo $item->ID; ?>" id="submenu-<?php echo esc_attr($item->ID); ?>">
                      <ul>
                        <?php foreach ($item->children as $child) : ?>
                         <li><a href="<?php echo esc_url( $child->url ); ?>"<?php echo kb_data_description( $child ); ?>><?php echo esc_html( $child->title ); ?></a></li>
                        <?php endforeach; ?>
                      </ul>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </li>
          <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </nav>
        <button id="MobileMenuToggle" class="site-header__mobile-menu-button" aria-controls="HeaderNav" aria-expanded="false" aria-haspopup="menu">
            <span class="site-header__mobile-menu-button__label"><?php esc_html_e( 'Menu', 'kim-branzell' ); ?></span>
            <svg class="site-header__mobile-menu-button__icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
      </div>

      </div>
    </div>
    <div class="site-header__bottom">
        <div class="site-header__search">
          <?php get_search_form(); ?>
        </div>
    </div>
    <?php if(!is_front_page()): ?>
      <nav class="site-header__breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'kim-branzell' ); ?>">
        <div class="site-header__inner">
          Du är här:
          <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'kim-branzell' ); ?></a>
          <?php if ( is_single() || is_page() ) : ?>
            <?php
            $ancestors = array_reverse( get_post_ancestors( get_the_ID() ) );
            foreach ( $ancestors as $ancestor ) :
            ?>
              <span aria-hidden="true">›</span>
              <a href="<?php echo esc_url( get_permalink( $ancestor ) ); ?>"><?php echo esc_html( get_the_title( $ancestor ) ); ?></a>
            <?php endforeach; ?>
            <span aria-hidden="true">›</span>
            <span aria-current="page"><?php echo esc_html( get_the_title() ); ?></span>
          <?php elseif ( is_search() ) : ?>
            <span aria-hidden="true">›</span>
            <span aria-current="page"><?php esc_html_e( 'Search', 'kim-branzell' ); ?></span>
          <?php elseif ( is_archive() ) : ?>
            <span aria-hidden="true">›</span>
            <span aria-current="page"><?php the_archive_title(); ?></span>
          <?php endif; ?>
        </div>
      </nav>
    <?php endif; ?>
</header>