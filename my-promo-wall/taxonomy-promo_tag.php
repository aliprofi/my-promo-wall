<?php
get_header();

$term = get_queried_object();
$term_description = term_description($term->term_id, 'promo_tag');

// Получаем все промокоды с данным тегом без пагинации
$args = array(
    'post_type' => 'mypromo',
    'posts_per_page' => -1, // Выводим ВСЕ промокоды
    'tax_query' => array(
        array(
            'taxonomy' => 'promo_tag',
            'field'    => 'term_id',
            'terms'    => $term->term_id,
        ),
    ),
    'post_status' => 'publish',
    'meta_query' => array(
        array(
            'key' => '_promo_expiry_date',
            'value' => date('Y-m-d'),
            'compare' => '>=',
            'type' => 'DATE'
        )
    )
);

$promos = new WP_Query($args);
?>

<div class="promo-archive-page">
    <h1><?php echo esc_html($term->name); ?></h1>
    
    <?php if ($term_description): ?>
        <div class="term-description">
            <?php echo wp_kses_post($term_description); ?>
        </div>
    <?php endif; ?>

    <div class="promo-full-content">
        <?php if ($promos->have_posts()): ?>
            <?php while ($promos->have_posts()): $promos->the_post(); ?>
                <?php
                $promo_code = get_post_meta(get_the_ID(), '_promo_code', true);
                $promo_link = get_post_meta(get_the_ID(), '_promo_link', true);
                $promo_discount = get_post_meta(get_the_ID(), '_promo_discount', true);
                $promo_description = get_post_meta(get_the_ID(), '_promo_description', true);
                $promo_expiry = get_post_meta(get_the_ID(), '_promo_expiry_date', true);
                
                // Форматируем описание с хештегами
                $promo_description = preg_replace_callback(
                    '/#([\p{L}\p{N}_]+)/u',
                    function($matches) {
                        $tag_name = $matches[1];
                        $tag_slug = sanitize_title($tag_name);
                        return '<a href="' . get_term_link($tag_slug, 'promo_tag') . '" class="promo-hashtag">#' . $tag_name . '</a>';
                    },
                    $promo_description
                );
                ?>
                
                <article class="promo-full-item" itemscope itemtype="https://schema.org/Offer">
                    <h2 itemprop="name"><?php the_title(); ?></h2>
                    
                    <?php if (has_post_thumbnail()): ?>
                        <div class="promo-image">
                            <?php the_post_thumbnail('medium'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="promo-details">
                        <?php if ($promo_discount): ?>
                            <div class="promo-discount" itemprop="description">
                                Скидка: <?php echo esc_html($promo_discount); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($promo_code): ?>
                            <div class="promo-code-full">
                                Промокод: <strong itemprop="serialNumber"><?php echo esc_html($promo_code); ?></strong>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($promo_description): ?>
                            <div class="promo-description-full" itemprop="description">
                                <?php echo wp_kses_post($promo_description); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($promo_expiry): ?>
                            <div class="promo-expiry" itemprop="validThrough" content="<?php echo esc_attr($promo_expiry); ?>">
                                Действует до: <?php echo esc_html(date('d.m.Y', strtotime($promo_expiry))); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($promo_link): ?>
                            <div class="promo-link">
                                <a href="<?php echo esc_url($promo_link); ?>" target="_blank" rel="nofollow" itemprop="url">
                                    Перейти к предложению
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <hr>
                </article>
                
            <?php endwhile; ?>
        <?php else: ?>
            <p>В данной категории пока нет активных промокодов.</p>
        <?php endif; ?>
    </div>
</div>

<?php
wp_reset_postdata();
get_footer();
?>
