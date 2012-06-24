<?php

# se os parametros já forem passados na url, faz a tradução e retorna
if ($_REQUEST['operation'] == 'traducao' and $_REQUEST['from'] and $_REQUEST['to'] and $_REQUEST['text']) {
    //Client ID of the application.
    $clientID       = "dentalmasterclub";
    //Client Secret key of the application.
    $clientSecret = "dsHWsznegDwjA1cgaRc6b392vpBnKcIFfDXfsXifVs0=";

    //Create the Translator Object.
    $translatorObj = new HTTPTranslator($clientID, $clientSecret);
    $trad = $translatorObj->translate($_REQUEST['from'], $_REQUEST['to'], $_REQUEST['text']);
    if ($_REQUEST['callback']) {
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: text/javascript; charset=UTF-8');
        echo($_REQUEST['callback'] . '(' . json_encode(utf8_encode($trad)) . ');');
    } else {
        header('Content-type: text/javascript; charset=UTF-8');
        echo(utf8_encode($trad));
    }
}
