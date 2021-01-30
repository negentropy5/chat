<?php
header('Access-Control-Allow-Origin: *');
// session_start(); //★テスト用

// サニタイズ関数
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

//改行区切りで配列にする関数
function ar_from_txt($addr){
  return explode("\n",trim(file_get_contents($addr)));
}

// rom数処理
function remote_addr() {
    // $line = session_id() . ',' . microtime(true); // ★テスト用
    $line = gethostbyaddr($_SERVER["REMOTE_ADDR"]) . ',' . microtime(true);

    file_put_contents('addr.txt', $line . "\n", FILE_APPEND);

    $res_addr = ar_from_txt('addr.txt'); 

    // 4秒以内の $res_addr配列だけ残す
    foreach($res_addr as $k => $res){
        $re = str_getcsv($res);
        if($re[1] < microtime(true) - 4.000) {
          unset($res_addr[$k]);
        }
    }

    $return_addr = $res_addr; // ★ 4秒以内の 配列
    
    $res_addr = implode("\n", $res_addr);
    file_put_contents('addr.txt',$res_addr . "\n");
    
    return $return_addr; // ★ 4秒以内の 配列
}

function text_append() {
    $result_text = filter_input(INPUT_POST, 'res'); // POST
    $array_text  = str_getcsv($result_text);
    if(empty($array_text[0]) || empty($array_text[1])) return;

    // サイタイズ
    $array_text[0] = h(mb_substr($array_text[0], 0, 10)); // 名前  // 0
    $array_text[1] = h(mb_substr($array_text[1], 0, 100));// 発言  // 1
    $array_text[2] = h($array_text[2]); // 色                      // 2
    $array_text[]  = gethostbyaddr($_SERVER["REMOTE_ADDR"]);       // 3
    $array_text[]  = date('(Y-m-d H:i:s)', time());                // 4

    // 削除処理
    if(preg_match("/^clear$/", $array_text[1])) {
        $arrays = ar_from_txt('text.txt');
        foreach($arrays as $k => $array) {
            $re = str_getcsv($array);
            if($re[3] === $array_text[3]) {
                unset($arrays[$k]);
            }
        }
        file_put_contents('text.txt', implode("\n", $arrays) . "\n");
    }

    // 全削除
    if(preg_match("/^adminclear$/", $array_text[1])) {
        file_put_contents('text.txt','');
    }

    // 入力処理
    file_put_contents('text.txt', implode(",", $array_text) ."\n", FILE_APPEND);

    // 行数制限
    $sum = ar_from_txt('text.txt');
    if(count($sum) > 30) {
        array_shift($sum); // 先頭一つの要素を削除
        file_put_contents('text.txt', implode("\n", $sum). "\n");
    }
}

$return_addr = remote_addr(); // ★ 4秒以内の 配列

foreach($return_addr as $res){
    $re = str_getcsv($res);
    $ipAddr[] = $re[0]; // アドレスだけの配列を作る
}
// 重複する要素を弾いた配列にしてカウントする
$uni_count = count(array_unique($ipAddr));

if($uni_count === 1) {
    $rom = sprintf("【 あなた %d 人 】", $uni_count);
} else {
    $rom = sprintf("【 あなたを含めて %d 人 】", $uni_count);
}

text_append();

$array_results = ar_from_txt('text.txt');

?>

<html>
<body>
<div style="color:#4285f4"><?= $rom ?></div>
<?php foreach(array_reverse($array_results) as $res): ?>
<?php $r = str_getcsv($res); ?>
<span style="color:<?=$r[2]?>;font-size:22px"><?=$r[0]?></span>
<span style="display:inline-block;border-width:6px 0 6px 8px;border-color: transparent transparent transparent #4285f4;border-style: solid;"></span>
<span style="color:<?=$r[2]?>;font-size:22px"><?=$r[1]?></span>
<?php $input = preg_replace('/\d+/', '※', $r[3]); ?>
<span style="color:#555;font-size:14px"><?=$input?></span>
<span style="color:#555;font-size:14px"><?=$r[4]?></span><br>
<?php endforeach ?>
</body>
</html>