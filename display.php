<?
header('Access-Control-Allow-Origin: *');
$URL = 'https://nimbus.wialon.com';
// ID табло
$STOP_ID = $_GET['id'];
// NimBus API Token
$TOKEN = '03e5b64060f84c929672ba156983c3fa';
// NimBus Depot Id
$DEPOT_ID = 'DEPOT_ID';
// Запрос к NimBus API
// https://sdk.wialon.com/products/nimbus/#/StopPanel/get_depot__depot_id__stop__stop_id__panel
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$URL/api/depot/$DEPOT_ID/stop/$STOP_ID/panel");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Authorization: $TOKEN"
));
$out = curl_exec($ch);
curl_close($ch);
// Текущее время для отображения на табло
$html = 't="'.getTime().'"'.PHP_EOL;
$out = json_decode($out, true);
if (isset($out['error']) || !isset($out['r'])) {
    // В случае ошибки или некорректных данных - выводим сообщение бегущей строкой
    $html .=
    's=3,"Ошибка получения данных. Информация на табло неактуальна!"'.PHP_EOL;
} else {
    // Цикл по маршрутам, проходящим через остановку
    foreach ($out['r'] as $index => $route) {
        // все рейсы машрута
        $timetables = $route['tt'];
        // Ищем ближайший рейс
        $nearRide = False;
        foreach ($timetables as $ind => $ride) {
            // пропускаем невалидные рейсы
            if (
                is_null($ride['uid']) &&        // объект не назначен и
                $ride['ot'] < 0                 // рейс по расписанию уже начался
            ) {
                continue;
            }
            // дополнительная проверка на валидность
            if (
                is_null($ride['tin']) ||        // объект не вошёл в геозону или
                $out['tm'] - $ride['tin'] < 60  // объект вошёл в геозону меньше 60 сек назад
            ) {
                // ближайший валидный рейс маршрута найден
                $nearRide = $ride;
                break;
            }
        }
        // Если нет подходящих рейсов - пропускаем маршрут и переходим к следующему
        if ($nearRide === False) {
            continue;
        }
        // номер маршрута
        $number = $route['n'];
        // конечная остановка
        $to = $route['ls'];
        // расчётное время прибытия по расписанию
        $eta = $ride['eta']['tt'];
        if ($eta < 0) {
            $eta = 0;
        }
        // Перевод секунд в минуты
        $eta = ceil($eta / 60);
        // Если расчётное время прибытия автобуса = 0, выводим '<1'
        if ($eta == 0) {
            $eta = '<1';
        }
        
        // Формируем строку в соответсвии с протоколом табло
        $html .= 'n="'.$number.';'.$to.';'.$eta.'"'.PHP_EOL;
    }
    // бегущая строка: имя/описание остановки
    $html .= 's=4,"ОСТАНОВКА: '.$out['n'].($out['d'] ? ' ('.$out['d'].')' : '').'"'.PHP_EOL;
}
// Конвертируем возвращаемый рещультат в кодировку "windows-1251"
mb_internal_encoding('Windows-1251');
header('Content-Type: text/html; charset=windows-1251');
echo mb_convert_encoding($html, 'Windows-1251', 'UTF-8');
?>
