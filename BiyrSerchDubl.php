<!-- Скрипт вытаскивает номер телефона из созданого лида, проверяет наличие дублей по вариациям этого телефона
и если находит - записывает в карточку созданого лида.


Для работы нужно:

1 Создать исходящий вебхук активирующийся при создании лида, обработчиком которого будет ссылка на файл скрипта на сервере. 
Внести его код авторизации в $ApToken
2 Создать входящий вебхук с доступом к CRM внести его в  $HOOKBIT
3 указать ссылку на портал, обязяьтельно с / в конце в  $PortBitr -->


<?





$HOOKBIT = 'https://portal.bitrix24.ua/rest/userID/keyHook/'; // входящий вебхук с порталом, пользователем и ключем
$ApToken = 'token_hook'; // Код авторизации проверки исходящего вебхука
$PortBitr = 'https://portal.bitrix24.ua/'; //портал для создания ссылок



//получаем событие создания лида





// if (!empty($_GET)) {
//     echo '404';
//     die;
// }

if (!empty($_POST) && ($_POST['event'] == 'ONCRMLEADADD') && ($_POST['auth']['application_token'] == $ApToken)) {

    $logID = $_POST['data']['FIELDS']['ID'];
    $phone = getPhone($logID);
    $dubl = getDubl($phone, $logID);
    if ($dubl != '') {


        writeComent($logID, $dubl);
    }
} else {

    header('Location: http://site.com/');

    die;
}

//получаем номер телефона из айди
function getPhone($ID)
{
    global $HOOKBIT;


    $method = 'crm.lead.get';
    $queryUrl = $HOOKBIT . $method;


    $queryData = http_build_query(array(
        'id' => strval($ID),

    ));
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $queryUrl,
        CURLOPT_POSTFIELDS => $queryData,
    ));
    $result = curl_exec($curl);
    curl_close($curl);

    $otvet = json_decode($result, true);


    $phonelid = $otvet["result"]["PHONE"][0]['VALUE'];

    return $phonelid;
}

//ищем дубли по номеру телефона

function getDubl($phonSerch, $ID)
{

    $method = 'crm.duplicate.findbycomm';

    global $HOOKBIT, $PortBitr;

    $mask1 = "38";
    $mask2 = "8";
    $mask3 = "3";

    $queryUrl = $HOOKBIT . $method;


    $queryData = http_build_query(array(
        'type' => "PHONE",
        'values' => ["$mask1$phonSerch", "$mask2$phonSerch", "$mask3$phonSerch", "$phonSerch", substr($phonSerch, 3)]

    ));

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $queryUrl,
        CURLOPT_POSTFIELDS => $queryData,
    ));
    $result = curl_exec($curl);
    curl_close($curl);
    $otvet = json_decode($result, true);
    var_dump($otvet);

    $serchLid = '';

    if ($otvet["result"][LEAD]) {

        $masphon = $otvet["result"][LEAD];
        foreach ($masphon as $key) {
            if ($key != $ID) {

                $serchLid = $serchLid . $PortBitr . "crm/lead/details/" . $key . "/" . "\n";
            }
        }



        // $myDubliLid = "дубль" . "\n" . $serchLid;
    }

    if ($otvet["result"][CONTACT]) {
        $serchCont = '';
        $maspCont = $otvet["result"][CONTACT];
        foreach ($maspCont as $key) {
            if ($key != $ID) {

                $serchCont = $serchCont . $PortBitr . "crm/contact/details/" . $key . "/" . "\n";
            }
        }
    }


    if ($serchLid != '' || $serchCont != '') {

        return "дубль лиды " . "\n" . $serchLid . "\n" . "дубль контакты " . "\n" . $serchCont;
    } else return  '';
}




//пишем коментарий в лид
function writeComent($ID, $coment)
{
    global $HOOKBIT;


    $method = 'crm.livefeedmessage.add';


    $queryUrl = $HOOKBIT . $method;


    $queryData = http_build_query(array(

        'fields' => [
            'POST_TITLE' => "Найдены дубли",
            'MESSAGE' => $coment,
            'SPERM' => "UA",
            'ENTITYTYPEID' => 1,
            'ENTITYID' => $ID
        ]


    ));

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $queryUrl,
        CURLOPT_POSTFIELDS => $queryData,
    ));
    $result = curl_exec($curl);

    curl_close($curl);
}
