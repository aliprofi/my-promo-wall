<?php
get_header();
?>

<div id="promo-wall">
    <?php if (function_exists('rank_math_the_breadcrumbs')) rank_math_the_breadcrumbs(); ?>
    <h1>Все промокоды AliExpress</h1>
    <div id="promos"></div>
    <div id="pagination"></div>
</div>

<script>
jQuery(document).ready(function($) {
    function loadPromos(page = 1) {
        $.ajax({
            url: promoWallSettings.ajaxurl,
            type: 'POST',
            data: {
                action: 'load_promos',
                page: page,
                is_archive: true
            },
            success: function(response) {
                if (response.success) {
                    $('#promos').html(response.data.promos);
                    $('#pagination').html(response.data.pagination);
                } else {
                    $('#promos').html('<p>Ошибка загрузки промокодов: ' + (response.data || 'Неизвестная ошибка') + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.log('Ошибка AJAX (loadPromos):', xhr, status, error);
                $('#promos').html('<p>Произошла ошибка при загрузке промокодов</p>');
            }
        });
    }
    loadPromos();
});
</script>

<?php
get_footer();
?>