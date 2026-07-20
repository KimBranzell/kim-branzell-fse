<?php wp_footer(); ?>
<footer>
        <div class="theme-toggle">
          <div class="theme-switcher" role="radiogroup" aria-label="<?php esc_attr_e( 'Tema', 'kim-branzell' ); ?>">

            <div class="theme-switcher__option" role="radio" aria-checked="false" tabindex="-1" data-value="light" aria-label="<?php esc_attr_e( 'Ljust tema', 'kim-branzell' ); ?>" aria-setsize="3" aria-posinset="1">
              <svg class="theme-switcher__icon" aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="5"/>
                <line x1="12" y1="1" x2="12" y2="3"/>
                <line x1="12" y1="21" x2="12" y2="23"/>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                <line x1="1" y1="12" x2="3" y2="12"/>
                <line x1="21" y1="12" x2="23" y2="12"/>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
              </svg>
              <span class="theme-switcher__label"><?php esc_html_e( 'Ljust', 'kim-branzell' ); ?></span>
            </div>

            <div class="theme-switcher__option" role="radio" aria-checked="true" tabindex="0" data-value="auto" aria-label="<?php esc_attr_e( 'System', 'kim-branzell' ); ?>" aria-setsize="3" aria-posinset="2">
              <svg class="theme-switcher__icon" aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                <line x1="8" y1="21" x2="16" y2="21"/>
                <line x1="12" y1="17" x2="12" y2="21"/>
              </svg>
              <span class="theme-switcher__label"><?php esc_html_e( 'System', 'kim-branzell' ); ?></span>
            </div>

            <div class="theme-switcher__option" role="radio" aria-checked="false" tabindex="-1" data-value="dark" aria-label="<?php esc_attr_e( 'Mörkt', 'kim-branzell' ); ?>" aria-setsize="3" aria-posinset="3">
              <svg class="theme-switcher__icon" aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
              </svg>
              <span class="theme-switcher__label"><?php esc_html_e( 'Mörkt', 'kim-branzell' ); ?></span>
            </div>

          </div>
        </div>
</footer>