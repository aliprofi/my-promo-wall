<?php
get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        // Получаем мета-данные
        $promo_code = get_post_meta(get_the_ID(), '_promo_code', true);
        $promo_link = get_post_meta(get_the_ID(), '_promo_link', true);
        $promo_discount = get_post_meta(get_the_ID(), '_promo_discount', true);
        $promo_description = get_post_meta(get_the_ID(), '_promo_description', true);
        $promo_expiry_date = get_post_meta(get_the_ID(), '_promo_expiry_date', true);
        $image = get_the_post_thumbnail_url(get_the_ID(), 'large');

        // Проверяем, не истёк ли промокод
        $is_expired = $promo_expiry_date && (strtotime($promo_expiry_date) < strtotime(date('Y-m-d', strtotime('-1 day'))));
        if ($is_expired) {
            echo '<div class="no-promos">Этот промокод истёк.</div>';
            continue;
        }

        // Частичное скрытие промокода
        $code_length = strlen($promo_code);
        $visible_length = ceil($code_length / 2);
        $hidden_code = substr($promo_code, 0, $visible_length) . str_repeat('*', $code_length - $visible_length);

        // Замена хэштегов на ссылки
        $promo_description = preg_replace_callback(
            '/#([\p{L}\p{N}_]+)/u',
            function($matches) {
                $tag_name = $matches[1];
                $tag_slug = sanitize_title($tag_name);
                return '<a href="' . site_url('/promo-tag/') . $tag_slug . '" class="promo-hashtag">#' . $tag_name . '</a>';
            },
            $promo_description
        );

        // Добавление микроразметки
        $microdata = '';
        if ($promo_code && $promo_link && $promo_discount) {
            $microdata = '
            <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "Offer",
                "name": "' . esc_js(wp_trim_words($promo_description, 10, '...')) . '",
                "description": "' . esc_js($promo_description) . '",
                "url": "' . esc_url($promo_link) . '",
                "price": "' . esc_js($promo_discount) . '",
                "priceCurrency": "USD",
                "availability": "https://schema.org/InStock",
                "validThrough": "' . esc_js($promo_expiry_date) . '"
            }
            </script>';
        }

        // Случайный светлый цвет для фона
        $colors = [
            '#FFE6E0', '#FFD9D0', '#FFF3E0', '#FFE8C7', '#F5F5F5', '#ECECEC',
        ];
        $bg_color = $colors[array_rand($colors)];
        ?>

        <div id="promo-wall">
            <?php if (function_exists('rank_math_the_breadcrumbs')) rank_math_the_breadcrumbs(); ?>
            <div class="promo" style="background: <?php echo $bg_color; ?>;" data-promo-id="<?php the_ID(); ?>" itemscope itemtype="https://schema.org/Offer">
                <?php if ($image) : ?>
                    <div class="promo-image-container">
                        <img src="<?php echo esc_url($image); ?>" alt="Promo Image" class="promo-image">
                    </div>
                <?php endif; ?>
                <div class="promo-content">
                    <div class="promo-code">
                        Код: <span class="hidden-code"><?php echo esc_html($hidden_code); ?></span>
                        <span class="full-code" style="display: none;"><?php echo esc_html($promo_code); ?></span>
                        <button class="show-code" data-link="<?php echo esc_url($promo_link); ?>" data-code="<?php echo esc_attr($promo_code); ?>">Показать</button>
                    </div>
                    <div class="promo-discount">Скидка: <?php echo esc_html($promo_discount); ?></div>
                    <div class="promo-expiry">Истекает: <?php echo esc_html($promo_expiry_date); ?></div>
                    <div class="promo-description"><?php echo wpautop($promo_description); ?></div>
                </div>
                <?php echo $microdata; ?>
            </div>

            <!-- Облако тегов -->
            <div id="promo-tags">
                <?php
                $tags = get_terms([
                    'taxonomy' => 'promo_tag',
                    'hide_empty' => true,
                ]);
                if (!empty($tags) && !is_wp_error($tags)) {
                    echo '<h3>Популярные товары</h3>';
                    foreach ($tags as $tag) {
                        $tag_link = get_term_link($tag);
                        echo '<a href="' . esc_url($tag_link) . '" class="promo-tag-button">' . esc_html($tag->name) . '</a>';
                    }
                }
                ?>
            </div>

            <!-- Ссылка на главную ленту -->
            <div class="back-to-promos">
                <a href="<?php echo esc_url(home_url('/promocodes-aliexpress/')); ?>" class="back-to-promos-button">Вернуться к промокодам</a>
            </div>
        </div>

        <?php
    endwhile;
else :
    echo '<div class="no-promos">Промокод не найден.</div>';
endif;

get_footer();
?>