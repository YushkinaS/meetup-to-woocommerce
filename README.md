# meetup-to-woocommerce
how to sell meetup events as woocommerce products

(код пока выложен не весь)

Библиотека для импорта данных Meetup: https://github.com/user3581488/Meetup

Создаем две категории товаров: Upcoming events и Past events.
Для каждого ивента создаем товар с дополнительными мета-полями: event_id, event_time, meetup_url.
Для featured ивентов также указываем _featured => yes (для обычных - no)
В excerpt (цитату) помещаем первые 500 символов описания ивента.
```php
                $end = 500;
                if (strlen($event->description) < $end) {
                    $end = strlen($event->description);
                }
                $post_excerpt = strip_tags(substr($event->description, 0, $end)) . '... ';
                 
                $my_post = array(
    			  'post_title'    => $event->name,
    			  'post_content'  => $event->description,
    			  'post_status'   => 'publish',
    			  'post_type'     => 'product',
    			  'post_excerpt'  => $post_excerpt,
    			  'meta_input'    => array(
    			      'event_id'      => $event->id,
    			      'event_time'    => $event->time / 1000,
    			      '_stock_status' => 'instock',
    			      '_regular_price'=> $event->fee->amount,
    			      '_price'        => $event->fee->amount,
    			      'meetup_url'    => $event->event_url,
    			      '_featured'     => ($event->featured == 'true') ? 'yes':'no'
    			      )
    			);
    			
    			$post_id = wp_insert_post( $my_post);
    			wp_set_object_terms($post_id,'upcoming-events','product_cat');
   
    			if (! isset($event->fee->amount)) {
    			    delete_post_meta($post_id,'_regular_price');
    			    delete_post_meta($post_id,'_price');
    			}
    			
    			preg_match('/(?<=<img src=")[^"]+/',$event->description,$image_links);
    			
    			add_action('add_attachment','obrabotka_save_attachment_id');
    			foreach ($image_links as $image_link) {
    			    media_sideload_image($image_link,$post_id,'product_'.$post_id.'_image');
    			}
    			remove_action('add_attachment','obrabotka_save_attachment_id');
```

Регулярным выражением находим в описании первую ссылку на изображение и загружаем ее как картинку товара.

Когда проходит event_time, товар должен сменить категорию (было - Upcoming events, станет - Past events). Также удаляем все цены и ставим _stock_status => outofstock.

На будущее: 

1. Сейчас загрузка новых ивентов и конвертация имеющихся (из будущих в прошедшие) выполняются по нажатию кнопки в админке. Планируется делать это через планировщик задач.

2. Планируется сделать два режима работы:

    а) Единожды импортированные ивенты живут новой жизнью, их можно редактировать (сейчас работаем именно в этом режиме).

    б) Полная синхронизация с meetup. В этом случае править ивенты через вордпресс бессмысленно - правки будут затерты.
    

