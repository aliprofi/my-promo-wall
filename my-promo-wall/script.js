jQuery(document).ready(function($) {
    console.log('script.js загружен');

    // Обработка отправки формы
    $('#promo-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'add_promo');
        formData.append('nonce', promoWallSettings.nonce);
        formData.append('cache_bust', promoWallSettings.cache_bust);
        
        $.ajax({
            url: promoWallSettings.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Промокод успешно опубликован!');
                    $('#promo-form')[0].reset();
                    loadPromos();
                } else {
                    alert('Ошибка: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Ошибка AJAX (add_promo):', xhr, status, error);
                alert('Произошла ошибка при отправке формы');
            }
        });
    });
    
    // Показать полный промокод, открыть ссылку и добавить кнопку копирования
    $(document).on('click', '.show-code', function() {
        var button = $(this);
        var promoCode = button.data('code');
        var promoLink = button.data('link');
        
        button.siblings('.hidden-code').hide();
        button.siblings('.full-code').text(promoCode).show();
        button.hide();
        
        // Добавляем кнопку "Копировать"
        var copyButton = $('<button class="copy-code">Копировать</button>');
        button.parent('.promo-code').append(copyButton);
        
        if (promoLink) {
            window.open(promoLink, '_blank');
        }
    });
    
    // Обработка копирования промокода
    $(document).on('click', '.copy-code', function() {
        var promoCode = $(this).siblings('.full-code').text();
        navigator.clipboard.writeText(promoCode).then(function() {
            alert('Промокод скопирован: ' + promoCode);
        }).catch(function(err) {
            console.error('Ошибка копирования:', err);
            alert('Не удалось скопировать промокод');
        });
    });
    
    // Загрузка промокодов для стены
    function loadPromos(page = 1, term_id = 0, is_archive = false, is_promo_wall = false) {
        if ($('#promos').length) {
            console.log('Загрузка промокодов, страница: ' + page + ', term_id: ' + term_id + ', is_archive: ' + is_archive);
            $.ajax({
                url: promoWallSettings.ajaxurl,
                type: 'POST',
                data: {
                    action: 'load_promos',
                    page: page,
                    term_id: term_id,
                    is_archive: is_archive,
                    is_promo_wall: is_promo_wall,
                    nonce: promoWallSettings.nonce,
                    cache_bust: promoWallSettings.cache_bust
                },
                success: function(response) {
                    console.log('Успех AJAX (load_promos):', response);
                    if (response.success) {
                        $('#promos').html(response.data.promos);
                        $('#pagination').html(response.data.pagination);
                    } else {
                        console.error('Ошибка загрузки промокодов:', response.data);
                        $('#promos').html('<p>Ошибка загрузки промокодов: ' + (response.data || 'Неизвестная ошибка') + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Ошибка AJAX (load_promos):', xhr, status, error);
                    $('#promos').html('<p>Произошла ошибка при загрузке промокодов</p>');
                }
            });
        }
    }
    
    // Загрузка промокодов для шорткода [promo_block]
    function loadPromoBlock(page = 1) {
        if ($('#promo-block').length) {
            var count = $('#promo-block').data('count');
            var tag = $('#promo-block').data('tag') || '';
            console.log('Загрузка промокодов для блока, count: ' + count + ', tag: ' + tag + ', page: ' + page);
            
            $.ajax({
                url: promoWallSettings.ajaxurl,
                type: 'POST',
                data: {
                    action: 'load_promo_block',
                    page: page,
                    count: count,
                    tag: tag,
                    nonce: promoWallSettings.nonce,
                    cache_bust: promoWallSettings.cache_bust
                },
                success: function(response) {
                    console.log('Успех AJAX (load_promo_block):', response);
                    if (response.success) {
                        $('#promo-block-promos').html(response.data.promos);
                        $('#promo-block-pagination').html(response.data.pagination);
                    } else {
                        console.error('Ошибка загрузки промокодов:', response.data);
                        $('#promo-block-promos').html('<p>Ошибка загрузки промокодов: ' + (response.data || 'Неизвестная ошибка') + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Ошибка AJAX (load_promo_block):', xhr, status, error);
                    $('#promo-block-promos').html('<p>Произошла ошибка при загрузке промокодов</p>');
                }
            });
        } else {
            console.log('Элемент #promo-block не найден');
        }
    }
    
    // Обработка кликов по пагинации
    $(document).on('click', '#pagination .page-numbers', function(e) {
        e.preventDefault();
        var page = $(this).hasClass('prev') ? parseInt($(this).siblings('.current').text()) - 1 :
                   $(this).hasClass('next') ? parseInt($(this).siblings('.current').text()) + 1 :
                   parseInt($(this).text());
        console.log('Переход на страницу пагинации (promos): ' + page);
        var term_id = $('#promo-wall').data('term-id') || 0;
        var is_archive = $('#promo-wall').data('is-archive') || false;
        var is_promo_wall = $('#promo-wall').data('is-archive') === false;
        loadPromos(page, term_id, is_archive, is_promo_wall);
    });
    
    $(document).on('click', '#promo-block-pagination .page-numbers', function(e) {
        e.preventDefault();
        var page = $(this).hasClass('prev') ? parseInt($(this).siblings('.current').text()) - 1 :
                   $(this).hasClass('next') ? parseInt($(this).siblings('.current').text()) + 1 :
                   parseInt($(this).text());
        console.log('Переход на страницу пагинации (promo_block): ' + page);
        loadPromoBlock(page);
    });
    
    // Инициализация загрузки
    if ($('#promo-wall').length) {
        var is_archive = $('#promo-wall').data('is-archive') === true;
        var is_promo_wall = $('#promo-wall').data('is-archive') === false;
        loadPromos(1, 0, is_archive, is_promo_wall);
    }
    loadPromoBlock();
});