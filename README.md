# meetup-to-woocommerce
how to sell meetup events as woocommerce products

(код пока выложен не весь)

Библиотека для импорта данных Meetup: https://github.com/user3581488/Meetup

Создаем две категории товаров: Upcoming events и Past events.
Для каждого ивента создаем товар с дополнительными мета-полями: event_id, event_time, meetup_url.
Для featured ивентов также указываем _featured => yes (для обычных - no)
В excerpt (цитату) помещаем первые 500 символов описания ивента.
Регулярным выражением находим в описании первую ссылку на изображение и загружаем ее как картинку товара.

Когда проходит event_time, товар должен сменить категорию (было - Upcoming events, станет - Past events). Также удаляем все цены и ставим _stock_status => outofstock.

На будущее: 

1. Сейчас загрузка новых ивентов и конвертация имеющихся (из будущих в прошедшие) выполняются по нажатию кнопки в админке. Планируется делать это через планировщик задач.

2. Планируется сделать два режима работы:

    а) Единожды импортированные ивенты живут новой жизнью, их можно редактировать (сейчас работаем именно в этом режиме).

    б) Полная синхронизация с meetup. В этом случае править ивенты через вордпресс бессмысленно - правки будут затерты.
    

