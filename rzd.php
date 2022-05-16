<?php

date_default_timezone_set('Etc/GMT-3');

require 'PHPMailerAutoload.php';

$flag = true;

class rzd {

	private $tariffs = array();
    private $minPrice;
    private $maxPrice;
    private $lowerTimeBound;
    private $trainsInfo = array();

	private $urlSearchStationId = 'http://pass.rzd.ru/suggester?lang=ru&stationNamePart=';
    private $urlMain = 'https://pass.rzd.ru/timetable/public/ru?';
    private $urlData = 'STRUCTURE_ID=735&layer_id=5371&dir=0&tfl=3&checkSeats=1&st0={{from}}&code0={{code_from}}&dt0={{date}}&st1={{to}}&code1={{code_to}}&dt1={{date}}';
    private $data;
    private $replace = [
        '{{from}}',
        '{{code_from}}',
        '{{to}}',
        '{{code_to}}',
        '{{date}}',
    ];
    private $secure = '&rid={{rid}}';
    private $replaceSecure = ['{{rid}}'];
    private $cookie = 'cookie';

    private function UrlEncode($string) {
      	$entities = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
      	$replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
      	return str_replace($entities, $replacements, urlencode($string));
    }

    public function getStationsId($from, $fromId, $to, $toId, $date, $lowerTimeBound, $minPrice, $maxPrice) {
    	#echo mb_strtoupper(mb_convert_encoding($from, "UTF-8"))."\n";

    	$from = mb_strtoupper($from, "UTF-8");
    	$to = mb_strtoupper($to, "UTF-8");

    	#$ch = curl_init($this->urlSearchStationId . $from);
    	#curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	#$result = json_decode(curl_exec($ch), true);
    	#$result = $result[0];
    	#var_dump($result[0]);
    	#foreach ($result as $station) {
    	#	if ($station['n'] == $from) {
    	#		$fromId = $station['c'];
    	#	}
    	#}

    	#$ch = curl_init($this->urlSearchStationId . $to);
    	#curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	#$result = json_decode(curl_exec($ch), true);
    	#var_dump($result[0]);
    	#foreach ($result as $station) {
    	#	if ($station['n'] == $to) {
    	#		$toId = $station['c'];
    	#	}
    	#}

    	#curl_close($ch);
        #unset($ch);

        #echo "fromId: ".$fromId." toId: ".$toId."\n";
        $this->data = [$from , $fromId , $to , $toId , $date ];
        $this->minPrice = $minPrice;
        $this->maxPrice = $maxPrice;
        $this->lowerTimeBound = $lowerTimeBound;
        #echo $this->data."\n";
    }

    public function sendEmail($text) {

        $mail = new PHPMailer;
        $mail->CharSet = "utf8";

        $mail->isSMTP();
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = 'html';

        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 587;
        $mail->SMTPSecure = 'tls';
        $mail->SMTPAuth = true;


        $mail->Username = "<email>";
        $mail->Password = "<password>";
        $mail->setFrom("<email>");
        $mail->addAddress('<email>');

        $mail->Subject = 'РЖД';
        $mail->Body = $text;

        if (!$mail->send()) {
            echo "Mailer Error: " . $mail->ErrorInfo;
        } else {
            echo "Message sent!";
        }
        unset($mail);
    }

    public function request() {

        #]$this->data = $data;
        $this->urlData = str_replace($this->replace, $this->data, $this->urlData);
#        var_dump($this->urlData);
#        var_dump($this->urlMain);
        echo  $this->UrlEncode($this->urlData);
        $ch = curl_init($this->urlMain . $this->UrlEncode($this->urlData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
        $result = json_decode(curl_exec($ch), true);

        var_dump($result);

        sleep(5);
        echo "\n".$result['rid']."\n";
        #$this->urlData .= str_replace($this->replaceSecure, [$result['rid']], $this->secure);
        $urlData1 = $this->urlData . str_replace($this->replaceSecure, [$result['rid']], $this->secure);

        #$ch = curl_init($this->urlMain . $this->UrlEncode($this->urlData));
        $ch = curl_init($this->urlMain . $this->UrlEncode($urlData1));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
        $result = json_decode(curl_exec($ch), true);

        curl_close($ch);
        unset($ch);
        unlink($this->cookie);

        unset($urlData1);

        var_dump($result);

        $result = $result['tp'][0]['list'];
        $tr_number = ""; #??

        #print_r($this->tariffs);
		var_dump($result[1]);

        foreach ($result as $train) {
            if (isset($train['cars']) && is_array($train['cars'])) {
                foreach ($train['cars'] as $ticket) {
				
                    #var_dump($train['number']);

                    if (($ticket['tariff'] <= $this->maxPrice) && ($ticket['tariff'] >= $this->minPrice) && ($train['time0'] >= $this->lowerTimeBound)) {

                        $trainInfo = 'Поезд: '.$train['number']."  ".$train['route0']." - ".$train['route1']."\n".'Отправление: '.$train['time0'].'|'.$train['date0'].'  Прибытие: '.$train['time1'].'|'.$train['date1'].'  Время в пути: '.$train['timeInWay']."\n".'Тип: '.$ticket['typeLoc'].'  Мест: '.$ticket['freeSeats'].'  Цена: '.$ticket['tariff'].' руб.';
                        array_push($this->trainsInfo, $trainInfo);

                        array_push($this->tariffs, $ticket['tariff']);
                    }
                	#echo($ticket['tariff']); echo("\n");

                }
            }
        }

 #       echo $resultExec;
        #var_dump($this->tariffs);
        #print_r($this->tariffs);

        #$this->minTariff = min($this->tariffs);
		#echo "Самый дешёвый билет: ".$this->minTariff.' руб.'."\n";
        #print_r($this->trainsInfo);

        if ($this->trainsInfo != null) {
            $this->sendEmail(implode("\n\n", $this->trainsInfo)."\n\nМинимальный тариф: ".min($this->tariffs).' руб.'."\n");
            global $flag;
            $flag = false;
        }

        array_splice($this->tariffs, 0);
        array_splice($this->trainsInfo, 0);
        #print_r($this->tariffs);

        #array_push($this->tariffs, '200');
        #print_r($this->tariffs);
    }
}

# коды станций см. здесь: https://www.parovoz.com/spravka/codes/

echo "Start\n";
echo "From: "; $from = trim(fgets(STDIN)); if ($from == null) { $from = 'Арзамас'; echo $from."\n";}
echo "To: "; $to = trim(fgets(STDIN)); if ($to == null) { $to = 'Москва'; echo $to."\n";}
echo "FromId: "; $fromId = trim(fgets(STDIN)); if ($fromId == null) { $fromId = '2060320'; echo $fromId."\n";}
echo "ToId: "; $toId = trim(fgets(STDIN)); if ($toId == null) { $toId = '2000000'; echo $toId."\n";}
echo "Date (dd.mm.yyyy): "; $date = trim(fgets(STDIN)); if  ($date == null) { $date = '10.01.2022'; echo $date."\n";}
echo "LowerTimeBound (hh:mm): "; $lowerTimeBound = trim(fgets(STDIN)); if  ($lowerTimeBound == null) { $lowerTimeBound = '09:00'; echo $lowerTimeBound."\n";}
echo "Min Price: "; $minPrice = trim(fgets(STDIN)); if ($minPrice == null) { $minPrice = '1000'; echo $minPrice."\n";}
echo "Max Price: "; $maxPrice = trim(fgets(STDIN)); if ($maxPrice == null) { $maxPrice = '2000'; echo $maxPrice."\n";}

$rzd = new rzd();
$rzd->getStationsId($from, $fromId, $to, $toId, $date, $lowerTimeBound, $minPrice, $maxPrice);

while($flag)
{
	#echo "\rSending request...\n";

	#$rzd->request([	    'Москва',	    '2000000',	    'Калуга',	    '2000350',	    '02.05.2021',	]);
    echo date('Y/m/d H:i:s')."\n";
	$rzd->request();

	sleep(15);
}
