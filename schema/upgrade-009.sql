UPDATE `setting`
SET `type` = 'select', `options` = '[ {value:"USD", text:"USD"}, {value:"EUR", text:"EUR"}, {value:"JPY", text:"JPY"}, {value:"BGN", text:"BGN"}, {value:"CZK", text:"CZK"}, {value:"DKK", text:"DKK"}, {value:"GBP", text:"GBP"}, {value:"HUF", text:"HUF"}, {value:"LTL", text:"LTL"}, {value:"PLN", text:"PLN"}, {value:"RON", text:"RON"}, {value:"SEK", text:"SEK"}, {value:"CHF", text:"CHF"}, {value:"NOK", text:"NOK"}, {value:"HRK", text:"HRK"}, {value:"RUB", text:"RUB"}, {value:"TRY", text:"TRY"}, {value:"AUD", text:"AUD"}, {value:"BRL", text:"BRL"}, {value:"CAD", text:"CAD"}, {value:"CNY", text:"CNY"}, {value:"HKD", text:"HKD"}, {value:"IDR", text:"IDR"}, {value:"ILS", text:"ILS"}, {value:"INR", text:"INR"}, {value:"KRW", text:"KRW"}, {value:"MXN", text:"MXN"}, {value:"MYR", text:"MYR"}, {value:"NZD", text:"NZD"}, {value:"PHP", text:"PHP"}, {value:"SGD", text:"SGD"}, {value:"THB", text:"THB"}, {value:"ZAR", text:"ZAR"} ]'
WHERE `setting`.`name` = 'item_currency_type';

INSERT INTO `setting` (`id`, `label`, `name`, `value`, `type`, `options`, `help`)
VALUES (NULL, 'Item Slider', 'item_slider_show', '1', 'select', '[{value:"0", text:"Disable"},{value:"1", text:"Enable"}]', 'Change to disable or enable displaying item slider.');