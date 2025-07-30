<?php
/*
Plugin Name: My Promo Wall
Description: Плагин для публикации промокодов AliExpress с AJAX-стеной
Version: 1.19
Author: Али Профи
Author URI: https://aliprofi.ru
*/

// Регистрация кастомного типа записи и таксономии
add_action('init', function() {
    register_post_type('mypromo', [
        'public' => true,
        'label' => 'My Promos',
        'supports' => ['title', 'author', 'thumbnail'],
        'has_archive' => true,
        'rewrite' => ['slug' => 'mypromo'],
        'publicly_queryable' => true,
    ]);

    register_taxonomy('promo_tag', 'mypromo', [
        'label' => 'Promo Tags',
        'rewrite' => ['slug' => 'promo-tag'],
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_in_rest' => true,
        'show_in_nav_menus' => true,
    ]);
});

// Исключение AJAX-запросов из кэша WP Rocket
add_filter('rocket_cache_reject_uri', function($uris) {
    $uris[] = '/wp-admin/admin-ajax.php?action=load_promos';
    $uris[] = '/wp-admin/admin-ajax.php?action=load_promo_block';
    return $uris;
});

// Отключение кэширования страниц promo-tag
add_filter('rocket_cache_reject_uri', function($uris) {
    $uris[] = '/promo-tag/(.*)';
    return $uris;
});

// Добавление мета-полей для промокодов
add_action('add_meta_boxes', function() {
    add_meta_box(
        'promo_details',
        'Детали промокода',
        'promo_details_callback',
        'mypromo',
        'normal',
        'high'
    );
});

function promo_details_callback($post) {
    wp_nonce_field('promo_details_nonce', 'promo_details_nonce');
    $promo_code = get_post_meta($post->ID, '_promo_code', true);
    $promo_link = get_post_meta($post->ID, '_promo_link', true);
    $promo_discount = get_post_meta($post->ID, '_promo_discount', true);
    $promo_description = get_post_meta($post->ID, '_promo_description', true);
    $promo_expiry_date = get_post_meta($post->ID, '_promo_expiry_date', true);
    ?>
    <p>
        <label for="promo_code">Промокод:</label><br>
        <input type="text" id="promo_code" name="promo_code" value="<?php echo esc_attr($promo_code); ?>" style="width: 100%;">
    </p>
    <p>
        <label for="promo_link">Ссылка:</label><br>
        <input type="url" id="promo_link" name="promo_link" value="<?php echo esc_attr($promo_link); ?>" style="width: 100%;">
    </p>
    <p>
        <label for="promo_discount">Скидка:</label><br>
        <input type="text" id="promo_discount" name="promo_discount" value="<?php echo esc_attr($promo_discount); ?>" style="width: 100%;">
    </p>
    <p>
        <label for="promo_description">Описание:</label><br>
        <textarea id="promo_description" name="promo_description" style="width: 100%; height: 100px;"><?php echo esc_textarea($promo_description); ?></textarea>
    </p>
    <p>
        <label for="promo_expiry_date">Дата окончания (ГГГГ-ММ-ДД):</label><br>
        <input type="date" id="promo_expiry_date" name="promo_expiry_date" value="<?php echo esc_attr($promo_expiry_date); ?>" style="width: 100%;">
    </p>
    <?php
}

// Сохранение мета-полей
add_action('save_post_mypromo', function($post_id) {
    if (!isset($_POST['promo_details_nonce']) || !wp_verify_nonce($_POST['promo_details_nonce'], 'promo_details_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    $fields = ['promo_code', 'promo_link', 'promo_discount', 'promo_description', 'promo_expiry_date'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
        }
    }
});

// Функция транслитерации для корректного слага
function transliterate($text) {
    $translit = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
        'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
        'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ы' => 'y', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
        'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
        'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'Kh', 'Ц' => 'Ts',
        'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Shch', 'Ы' => 'Y', 'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        'ь' => '', 'Ь' => '', 'ъ' => '', 'Ъ' => '',
    ];
    $text = strtr($text, $translit);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9-_]/u', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

// Проверка существующего терма перед созданием
function create_or_get_promo_tag($tag_name) {
    $tag_name = trim($tag_name);
    $slug = transliterate($tag_name);
    error_log("create_or_get_promo_tag: tag_name=$tag_name, slug=$slug");

    $term = get_term_by('slug', $slug, 'promo_tag');
    if (!$term) {
        $term_result = wp_insert_term($tag_name, 'promo_tag', ['slug' => $slug]);
        if (!is_wp_error($term_result)) {
            $term = get_term($term_result['term_id'], 'promo_tag');
            error_log("create_or_get_promo_tag: Создан новый тег, term_id={$term->term_id}");
        } else {
            error_log("create_or_get_promo_tag: Ошибка создания тега: " . $term_result->get_error_message());
            return null;
        }
    } else {
        error_log("create_or_get_promo_tag: Тег уже существует, term_id={$term->term_id}");
    }
    return $term;
}

// Применяем функцию при сохранении поста
add_action('save_post_mypromo', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    $description = get_post_meta($post_id, '_promo_description', true);
    preg_match_all('/#([\p{L}\p{N}_]+)/u', $description, $matches);
    error_log("save_post_mypromo: Найдены хэштеги: " . json_encode($matches[1]));

    if (!empty($matches[1])) {
        $tags = [];
        foreach ($matches[1] as $tag) {
            $term = create_or_get_promo_tag($tag);
            if ($term && !is_wp_error($term)) {
                $tags[] = (int)$term->term_id;
                error_log("save_post_mypromo: Добавлен тег, term_id={$term->term_id}");
            }
        }
        if (!empty($tags)) {
            $result = wp_set_object_terms($post_id, $tags, 'promo_tag');
            if (is_wp_error($result)) {
                error_log("save_post_mypromo: Ошибка установки тегов: " . $result->get_error_message());
            } else {
                error_log("save_post_mypromo: Теги успешно установлены: " . json_encode($tags));
            }
        }
    }
});

// Сбрасываем правила переписывания при активации/деактивации
register_activation_hook(__FILE__, function() {
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

// Функция транслитерации для URL
function custom_sanitize_title($title) {
    $translit = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
        'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
        'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ы' => 'y', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
        'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
        'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'Kh', 'Ц' => 'Ts',
        'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Shch', 'Ы' => 'Y', 'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        'ь' => '', 'ъ' => '', 'Ь' => '', 'Ъ' => '',
        ' ' => '-', '_' => '-', '.' => '', ',' => '', '&' => 'and', '%' => '', '№' => '',
        '(' => '', ')' => '', '[' => '', ']' => '', '{' => '', '}' => '', '/' => '-',
        '\\' => '-', '|' => '-', '+' => '', '*' => '', '@' => '', '!' => '', '?' => '',
        '#' => '', '"' => '', '\'' => '', ':' => '', ';' => '', '<' => '', '>' => '',
    ];
    $title = strtr($title, $translit);
    $title = mb_strtolower($title, 'UTF-8');
    $title = preg_replace('/[^a-z0-9\-]+/u', '-', $title);
    $title = preg_replace('/-+/', '-', $title);
    return trim($title, '-');
}

function custom_term_exists($term_name, $taxonomy) {
    $existing = term_exists($term_name, $taxonomy);
    if ($existing) return $existing;
    
    $term_name_lower = mb_strtolower($term_name, 'UTF-8');
    $all_terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
    foreach ($all_terms as $term) {
        if (mb_strtolower($term->name, 'UTF-8') === $term_name_lower) {
            return ['term_id' => $term->term_id, 'term_taxonomy_id' => $term->term_taxonomy_id];
        }
    }
    
    $slug = custom_sanitize_title($term_name);
    $term = get_term_by('slug', $slug, 'promo_tag');
    if ($term) {
        return ['term_id' => $term->term_id, 'term_taxonomy_id' => $term->term_taxonomy_id];
    }
    return null;
}

// Фильтр для обработки слагов
add_filter('wp_unique_term_slug', function($slug, $term, $taxonomy) {
    if ($taxonomy !== 'promo_tag') return $slug;
    $transliterated_slug = custom_sanitize_title($term->name);
    $existing_term = get_term_by('slug', $transliterated_slug, $taxonomy);
    if ($existing_term) {
        return $transliterated_slug;
    }
    return $transliterated_slug;
}, 10, 3);

// Применение транслитерации к slug
add_filter('sanitize_title', function($title, $raw_title, $context) {
    if ($context === 'save') {
        return custom_sanitize_title($raw_title);
    }
    return $title;
}, 10, 3);

// Изменение заголовка архива таксономии
add_filter('get_the_archive_title', function($title) {
    if (is_tax('promo_tag')) {
        $term = get_queried_object();
        $title = sprintf('Промокоды: %s', esc_html($term->name));
    } elseif (is_post_type_archive('mypromo') && !is_search()) {
        $title = 'Промокоды';
    }
    return $title;
});

// Изменение текста и ссылки в хлебных крошках
add_filter('rank_math/frontend/breadcrumb/html', function($html, $crumbs, $class) {
    if (is_post_type_archive('mypromo') || is_tax('promo_tag') || is_singular('mypromo')) {
        $html = preg_replace(
            '/<span class="home"><a href="[^"]+">[^<]+<\/a><\/span>/',
            '<span class="home"><a href="' . esc_url('https://aliprofi.ru/promocodes-aliexpress/') . '">Промокоды AliExpress</a></span>',
            $html
        );
        $html = str_replace('My Promos', 'Промокоды', $html);
        $html = str_replace('Promo Tags', 'Промокоды', $html);
        $html = str_replace('Промокоды', 'Промокоды', $html);
    }
    return $html;
}, 10, 3);

// Подключение кастомных шаблонов
add_filter('template_include', function($template) {
    if (is_singular('mypromo')) {
        $plugin_template = plugin_dir_path(__FILE__) . 'single-mypromo.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    } elseif (is_tax('promo_tag')) {
        $plugin_template = plugin_dir_path(__FILE__) . 'taxonomy-promo_tag.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    } elseif (is_post_type_archive('mypromo')) {
        $plugin_template = plugin_dir_path(__FILE__) . 'archive-mypromo.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    return $template;
});

// Шорткод для вывода стены промокодов
add_shortcode('promo_wall', function() {
    ob_start();
    ?>
    <div id="promo-wall" data-is-archive="false">
        <?php if(is_user_logged_in()) : ?>
            <form id="promo-form" enctype="multipart/form-data">
                <input type="text" name="promo_code" placeholder="Промокод" required>
                <input type="url" name="promo_link" placeholder="Ссылка на товар" required>
                <input type="text" name="promo_discount" placeholder="Скидка (например, 10% или $5)" required>
                <textarea name="promo_description" placeholder="Описание промокода"></textarea>
                <input type="text" name="promo_hashtags" placeholder="Хэштеги (через пробел, без #, например: скидка акция)">
                <input type="file" name="promo_image" accept="image/*">
                <input type="date" name="promo_expiry_date" placeholder="Дата окончания (ГГГГ-ММ-ДД)" required>
                <button type="submit">Опубликовать</button>
            </form>
        <?php endif; ?>
        <div id="promos"></div>
        <div id="pagination"></div>
        
        <!-- Вывод меток -->
        <div id="promo-tags">
            <?php
            $tags = get_terms([
                'taxonomy' => 'promo_tag',
                'hide_empty' => true,
            ]);
            if (!empty($tags) && !is_wp_error($tags)) {
                echo '<h3>Популярные темы</h3>';
                foreach ($tags as $tag) {
                    $tag_link = get_term_link($tag);
                    $tag_name = esc_html($tag->name);
                    echo "<a href='$tag_link' class='promo-tag-button'>$tag_name</a>";
                }
            }
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

// Шорткод для вывода блока промокодов в статьях
add_shortcode('promo_block', function($atts) {
    $atts = shortcode_atts([
        'count' => 1,
        'tag' => '',
    ], $atts);

    $count = absint($atts['count']);
    $tag = sanitize_text_field($atts['tag']);

    ob_start();
    ?>
    <div id="promo-block" data-count="<?php echo esc_attr($count); ?>" data-tag="<?php echo esc_attr($tag); ?>">
        <div id="promo-block-promos"></div>
        <div id="promo-block-pagination"></div>
    </div>
    <?php
    return ob_get_clean();
});

// Подключаем скрипты и стили
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('my-promo-wall', plugin_dir_url(__FILE__) . 'script.js', ['jquery'], '1.8', true);
    wp_localize_script('my-promo-wall', 'promoWallSettings', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('promo_wall_nonce'),
        'cache_bust' => time(),
    ]);
    
    wp_enqueue_style('kurale-font', 'https://fonts.googleapis.com/css2?family=Kurale&display=swap', [], null);
    wp_enqueue_style('my-promo-wall', plugin_dir_url(__FILE__) . 'style.css', [], '1.8');
});

// Добавление настройки для текста в архиве
add_action('admin_menu', function() {
    add_options_page(
        'Настройки Promo Wall',
        'Promo Wall',
        'manage_options',
        'promo-wall-settings',
        'promo_wall_settings_page'
    );
});

function promo_wall_settings_page() {
    if (!current_user_can('manage_options')) return;
    if (isset($_POST['promo_wall_archive_text'])) {
        update_option('promo_wall_archive_text', wp_kses_post($_POST['promo_wall_archive_text']));
    }
    $archive_text = get_option('promo_wall_archive_text', '');
    ?>
    <div class="wrap">
        <h1>Настройки Promo Wall</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="promo_wall_archive_text">Текст в архиве (после промокодов):</label></th>
                    <td>
                        <?php wp_editor($archive_text, 'promo_wall_archive_text', ['textarea_rows' => 10]); ?>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// AJAX для добавления промокода
add_action('wp_ajax_add_promo', function() {
    error_log('Начало обработки AJAX-запроса add_promo');

    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'promo_wall_nonce')) {
        error_log('Ошибка проверки nonce');
        wp_send_json_error('Ошибка безопасности');
        wp_die();
    }
    
    if (empty($_POST['promo_code']) || empty($_POST['promo_link']) || empty($_POST['promo_discount']) || empty($_POST['promo_expiry_date'])) {
        error_log('Не заполнены обязательные поля');
        wp_send_json_error('Заполните все обязательные поля');
        wp_die();
    }
    
    $promo_code = sanitize_text_field($_POST['promo_code']);
    $promo_link = esc_url_raw($_POST['promo_link']);
    $promo_discount = sanitize_text_field($_POST['promo_discount']);
    $promo_description = sanitize_textarea_field($_POST['promo_description'] ?? '');
    $promo_expiry_date = sanitize_text_field($_POST['promo_expiry_date']);
    $promo_hashtags = sanitize_text_field($_POST['promo_hashtags'] ?? '');
    
    if (!DateTime::createFromFormat('Y-m-d', $promo_expiry_date)) {
        error_log('Неверный формат даты: ' . $promo_expiry_date);
        wp_send_json_error('Неверный формат даты окончания');
        wp_die();
    }
    
    // Обработка хэштегов из нового поля
    $hashtags = array_filter(array_map('trim', explode(' ', $promo_hashtags)));
    $tags = [];
    foreach ($hashtags as $hashtag) {
        if (!empty($hashtag)) {
            $tags[] = $hashtag;
            $promo_description .= ' #' . $hashtag;
        }
    }
    
    $image_id = null;
    if (!empty($_FILES['promo_image']['name'])) {
        error_log('Обработка загрузки изображения: ' . $_FILES['promo_image']['name']);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('promo_image', 0);
        if (is_wp_error($attachment_id)) {
            error_log('Ошибка загрузки изображения: ' . $attachment_id->get_error_message());
            wp_send_json_error('Ошибка загрузки изображения: ' . $attachment_id->get_error_message());
            wp_die();
        }
        $image_id = $attachment_id;
    }
    
    $post_id = wp_insert_post([
        'post_type' => 'mypromo',
        'post_title' => wp_trim_words($promo_description, 10, '...'),
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
    ], true);
    
    if (is_wp_error($post_id)) {
        error_log('Ошибка создания поста: ' . $post_id->get_error_message());
        wp_send_json_error('Ошибка при создании поста: ' . $post_id->get_error_message());
        wp_die();
    }
    
    update_post_meta($post_id, '_promo_code', $promo_code);
    update_post_meta($post_id, '_promo_link', $promo_link);
    update_post_meta($post_id, '_promo_discount', $promo_discount);
    update_post_meta($post_id, '_promo_description', $promo_description);
    update_post_meta($post_id, '_promo_expiry_date', $promo_expiry_date);
    
    if ($image_id) {
        set_post_thumbnail($post_id, $image_id);
    }
    
    $term_ids = [];
    if (!empty($tags)) {
        foreach ($tags as $tag_name) {
            $term = create_or_get_promo_tag($tag_name);
            if ($term && !is_wp_error($term)) {
                $term_ids[] = (int)$term->term_id;
            }
        }
    }
    
    if (!empty($term_ids)) {
        $set_terms_result = wp_set_object_terms($post_id, $term_ids, 'promo_tag');
        if (is_wp_error($set_terms_result)) {
            error_log('Ошибка установки термина: ' . $set_terms_result->get_error_message());
        }
    }
    
    error_log('Промокод успешно опубликован, post_id: ' . $post_id);
    wp_send_json_success('Промокод успешно опубликован');
    wp_die();
});

// Обеспечиваем правильные ссылки на теги в описании промокодов
add_filter('the_content', function($content) {
    global $post;
    if ($post->post_type !== 'mypromo') return $content;
    
    return preg_replace_callback(
        '/#([\p{L}\p{N}_]+)/u',
        function($matches) {
            $tag_name = $matches[1];
            $term = get_term_by('name', $tag_name, 'promo_tag');
            if (!$term) {
                $slug = custom_sanitize_title($tag_name);
                $term = get_term_by('slug', $slug, 'promo_tag');
            }
            if ($term) {
                $tag_link = get_term_link($term);
                return '<a href="' . esc_url($tag_link) . '" class="promo-hashtag">#' . $tag_name . '</a>';
            }
            return '#' . $tag_name;
        },
        $content
    );
}, 11);

// AJAX для загрузки промокодов для стены
add_action('wp_ajax_load_promos', 'load_promos_handler');
add_action('wp_ajax_nopriv_load_promos', 'load_promos_handler');

function load_promos_handler() {
    error_log('load_promos_handler: term_id=' . (isset($_POST['term_id']) ? $_POST['term_id'] : 'не передан'));
    error_log('load_promos_handler: page=' . (isset($_POST['page']) ? $_POST['page'] : 1));
    error_log('load_promos_handler: is_archive=' . (isset($_POST['is_archive']) ? $_POST['is_archive'] : 'не передан'));
    error_log('load_promos_handler: cache_bust=' . (isset($_POST['cache_bust']) ? $_POST['cache_bust'] : 'не передан'));
    error_log('load_promos_handler: is_promo_wall_shortcode=' . (isset($_POST['is_promo_wall']) ? $_POST['is_promo_wall'] : 'не передан'));

    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $term_id = isset($_POST['term_id']) ? (int)$_POST['term_id'] : 0;
    $is_archive = isset($_POST['is_promo_wall']) && $_POST['is_promo_wall'] === 'true' ? false : (isset($_POST['is_archive']) ? (bool)$_POST['is_archive'] : false);
    
    // Кэширование запроса
    $cache_key = 'promo_wall_' . md5(json_encode($_POST) . $page . $term_id . $is_archive);
    $cached = get_transient($cache_key);
    if ($cached) {
        wp_send_json_success($cached);
        wp_die();
    }
    
    $args = [
        'post_type' => 'mypromo',
        'posts_per_page' => 10,
        'paged' => $page,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [
            [
                'key' => '_promo_expiry_date',
                'value' => date('Y-m-d', strtotime('-1 day')),
                'compare' => '>=',
                'type' => 'DATE',
            ],
        ],
    ];
    
    if ($term_id) {
        $term = get_term($term_id, 'promo_tag');
        if ($term && !is_wp_error($term)) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'promo_tag',
                    'field' => 'term_id',
                    'terms' => $term_id,
                ],
            ];
            error_log("load_promos_handler: Применён tax_query для term_id=$term_id, term_name={$term->name}");
        } else {
            error_log("load_promos_handler: Тег с term_id=$term_id не существует");
        }
    }
    
    error_log('load_promos_handler: WP_Query args=' . json_encode($args));
    
    $query = new WP_Query($args);
    error_log('load_promos_handler: Найдено постов=' . $query->found_posts);
    
    $promos = '';
    $colors = [
        '#FFE6E0', '#FFD9D0', '#FFF3E0', '#FFE8C7', '#F5F5F5', '#ECECEC',
    ];
    
    if ($query->have_posts()) {
        $count = 0;
        while ($query->have_posts()) {
            $query->the_post();
            $color_index = $count % count($colors);
            $bg_color = $colors[$color_index];
            
            $promo_code = get_post_meta(get_the_ID(), '_promo_code', true);
            $promo_link = get_post_meta(get_the_ID(), '_promo_link', true);
            $promo_discount = get_post_meta(get_the_ID(), '_promo_discount', true);
            $promo_description = get_post_meta(get_the_ID(), '_promo_description', true);
            $promo_expiry_date = get_post_meta(get_the_ID(), '_promo_expiry_date', true);
            $image = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
            
            $promo_description = preg_replace_callback(
                '/#([\p{L}\p{N}_]+)/u',
                function($matches) {
                    $tag_name = $matches[1];
                    $tag_slug = custom_sanitize_title($tag_name);
                    return '<a href="' . site_url('/promo-tag/') . $tag_slug . '" class="promo-hashtag">#' . $tag_name . '</a>';
                },
                $promo_description
            );
            
            if ($is_archive) {
                $excerpt = wp_trim_words($promo_description, 20, '...');
                $permalink = get_permalink();
                
                $promos .= '<div class="promo promo-preview" style="background: ' . $bg_color . ';" data-promo-id="' . get_the_ID() . '">';
                $promos .= '<div class="promo-image-container">';
                if ($image) {
                    $promos .= '<a href="' . esc_url($permalink) . '"><img src="' . esc_url($image) . '" alt="Promo Image" class="promo-image promo-image-preview"></a>';
                }
                $promos .= '</div>';
                $promos .= '<div class="promo-content">';
                $promos .= '<div class="promo-excerpt">' . wpautop($excerpt) . '</div>';
                $promos .= '<div class="promo-details">';
                $promos .= '<div class="promo-discount">Скидка: ' . esc_html($promo_discount) . '</div>';
                $promos .= '<a href="' . esc_url($permalink) . '" class="get-promo-button">Получить промокод</a>';
                $promos .= '</div>';
                $promos .= '</div>';
                $promos .= '</div>';
            } else {
                $code_length = strlen($promo_code);
                $visible_length = ceil($code_length / 2);
                $hidden_code = substr($promo_code, 0, $visible_length) . str_repeat('*', $code_length - $visible_length);
                
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
                
                $promos .= '<div class="promo" style="background: ' . $bg_color . ';" data-promo-id="' . get_the_ID() . '" itemscope itemtype="https://schema.org/Offer">';
                $promos .= '<div class="promo-image-container">';
                if ($image) {
                    $promos .= '<img src="' . esc_url($image) . '" alt="Promo Image" class="promo-image">';
                }
                $promos .= '</div>';
                $promos .= '<div class="promo-content">';
                $promos .= '<div class="promo-code">Промокод: <span class="hidden-code">' . esc_html($hidden_code) . '</span>';
                $promos .= '<span class="full-code" style="display: none;">' . esc_html($promo_code) . '</span>';
                $promos .= '<button class="show-code" data-link="' . esc_url($promo_link) . '" data-code="' . esc_attr($promo_code) . '">Показать</button></div>';
                $promos .= '<div class="promo-discount">Скидка: ' . esc_html($promo_discount) . '</div>';
                $promos .= '<div class="promo-expiry">Истекает: ' . esc_html($promo_expiry_date) . '</div>';
                $promos .= '<div class="promo-description">' . wpautop($promo_description) . '</div>';
                $promos .= '</div>';
                $promos .= $microdata;
                $promos .= '</div>';
            }
            $count++;
        }
    } else {
        $promos = '<div class="no-promos">Пока нет промокодов. Будьте первым!</div>';
    }
    
    $pagination = paginate_links([
        'total' => $query->max_num_pages,
        'current' => $page,
        'format' => '?paged=%#%',
        'type' => 'plain',
        'prev_text' => '«',
        'next_text' => '»',
    ]);
    
    wp_reset_postdata();
    
    $response = [
        'promos' => $promos,
        'pagination' => $pagination
    ];
    
    // Сохраняем в transient на 5 минут
    set_transient($cache_key, $response, 5 * MINUTE_IN_SECONDS);
    
    wp_send_json_success($response);
    wp_die();
}

// AJAX для загрузки промокодов для шорткода [promo_block]
add_action('wp_ajax_load_promo_block', 'load_promo_block_handler');
add_action('wp_ajax_nopriv_load_promo_block', 'load_promo_block_handler');

function load_promo_block_handler() {
    error_log('Начало обработки AJAX load_promo_block');
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'promo_wall_nonce')) {
        error_log('Ошибка проверки nonce в load_promo_block');
        wp_send_json_error('Ошибка безопасности');
        wp_die();
    }

    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $count = isset($_POST['count']) ? (int)$_POST['count'] : 1;
    $tag = isset($_POST['tag']) ? sanitize_text_field($_POST['tag']) : '';
    
    // Кэширование запроса
    $cache_key = 'promo_block_' . md5(json_encode($_POST) . $page . $count . $tag);
    $cached = get_transient($cache_key);
    if ($cached) {
        wp_send_json_success($cached);
        wp_die();
    }
    
    $args = [
        'post_type' => 'mypromo',
        'posts_per_page' => $count,
        'paged' => $page,
        'orderby' => 'rand',
        'meta_query' => [
            [
                'key' => '_promo_expiry_date',
                'value' => date('Y-m-d', strtotime('-1 day')),
                'compare' => '>=',
                'type' => 'DATE',
            ],
        ],
    ];
    
    if (!empty($tag)) {
        $term = get_term_by('slug', custom_sanitize_title($tag), 'promo_tag');
        if ($term && !is_wp_error($term)) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'promo_tag',
                    'field' => 'term_id',
                    'terms' => $term->term_id,
                ],
            ];
            error_log("load_promo_block_handler: Применён tax_query для tag=$tag, term_id={$term->term_id}");
        } else {
            error_log("load_promo_block_handler: Тег с slug=" . custom_sanitize_title($tag) . " не найден");
        }
    }
    
    error_log('load_promo_block_handler: WP_Query args=' . json_encode($args));
    
    $query = new WP_Query($args);
    error_log('load_promo_block_handler: Найдено постов=' . $query->found_posts);
    
    $promos = '';
    $colors = [
        '#FFE6E0', '#FFD9D0', '#FFF3E0', '#FFE8C7', '#F5F5F5', '#ECECEC',
    ];
    
    if ($query->have_posts()) {
        $index = 0;
        while ($query->have_posts()) {
            $query->the_post();
            $color_index = $index % count($colors);
            $bg_color = $colors[$color_index];
            
            $promo_code = get_post_meta(get_the_ID(), '_promo_code', true);
            $promo_link = get_post_meta(get_the_ID(), '_promo_link', true);
            $promo_discount = get_post_meta(get_the_ID(), '_promo_discount', true);
            $promo_description = get_post_meta(get_the_ID(), '_promo_description', true);
            $promo_expiry_date = get_post_meta(get_the_ID(), '_promo_expiry_date', true);
            $image = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
            
            $promo_description = preg_replace_callback(
                '/#([\p{L}\p{N}_]+)/u',
                function($matches) {
                    $tag_name = $matches[1];
                    $tag_slug = custom_sanitize_title($tag_name);
                    return '<a href="' . site_url('/promo-tag/') . $tag_slug . '" class="promo-hashtag">#' . $tag_name . '</a>';
                },
                $promo_description
            );
            
            $code_length = strlen($promo_code);
            $visible_length = ceil($code_length / 2);
            $hidden_code = substr($promo_code, 0, $visible_length) . str_repeat('*', $code_length - $visible_length);
            
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
            
            $promos .= '<div class="promo" style="background: ' . $bg_color . ';" data-promo-id="' . get_the_ID() . '" itemscope itemtype="https://schema.org/Offer">';
            $promos .= '<div class="promo-image-container">';
            if ($image) {
                $promos .= '<img src="' . esc_url($image) . '" alt="Promo Image" class="promo-image">';
            }
            $promos .= '</div>';
            $promos .= '<div class="promo-content">';
            $promos .= '<div class="promo-code">Промокод: <span class="hidden-code">' . esc_html($hidden_code) . '</span>';
            $promos .= '<span class="full-code" style="display: none;">' . esc_html($promo_code) . '</span>';
            $promos .= '<button class="show-code" data-link="' . esc_url($promo_link) . '" data-code="' . esc_attr($promo_code) . '">Показать</button></div>';
            $promos .= '<div class="promo-discount">Скидка: ' . esc_html($promo_discount) . '</div>';
            $promos .= '<div class="promo-expiry">Истекает: ' . esc_html($promo_expiry_date) . '</div>';
            $promos .= '<div class="promo-description">' . wpautop($promo_description) . '</div>';
            $promos .= '</div>';
            $promos .= $microdata;
            $promos .= '</div>';
            
            $index++;
        }
    } else {
        $promos = '<div class="no-promos">Пока нет промокодов.</div>';
    }
    
    wp_reset_postdata();
    
    $response = [
        'promos' => $promos,
        'pagination' => ''
    ];
    
    set_transient($cache_key, $response, 5 * MINUTE_IN_SECONDS);
    
    wp_send_json_success($response);
    wp_die();
}
?>