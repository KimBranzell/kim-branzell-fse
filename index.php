<?php
get_header();
?>
<main id="main-content">
  <header>
    <div class="main__inner">
      <h1><?php echo get_the_title(); ?></h1>
    </div>
  </header>
  <?php the_content() ;?>
</main>
<?php
get_footer();
?>