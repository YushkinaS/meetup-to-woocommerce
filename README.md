# meetup-to-woocommerce
how to sell meetup events as woocommerce products

Библиотека для импорта данных Meetup: https://github.com/user3581488/Meetup

Создаем две категории товаров: Upcoming events и Past events.
Для каждого ивента создаем товар с дополнительными мета-полями: event_id, event_time, meetup_url.
Для featured ивентов также указываем _featured => yes (для обычных - no)
