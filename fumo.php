<?php
// .fumoはなんか自分用のやつです
// 実行する優先度はfumoが上です


header('Content-Type: text/html; charset=UTF-8'); // お前はUTF-8だ
date_default_timezone_set('Asia/Tokyo'); // お前は東京住まいだ

// かんすー作る！
function fumo($content) {
    $base_dir = __DIR__;

    $title_value = "ふも"; // でふぉのページタイトル、<fumo:head>で何もなかったときにやるって思ったけど全然意味がないというかそもそもいらねえじゃねえかこれ無駄すぎる

    // 埋め込みます！
    // headタグ内のやつ(titleもできるように)
    if (file_exists($base_dir . "/fumo/head.fumo")) {
        $head_nakami = file_get_contents($base_dir . "/fumo/head.fumo");
        if (preg_match("/<fumo:head:(.*?)>/", $content, $matches)) {
            $title_value = $matches[1];

            $replaced_head = str_replace("<fumo:title>", $title_value, $head_nakami); // 変だなとか言わないで、ヤケクソで書いたやつ(Geminiに)だから

            $content = str_replace($matches[0], $replaced_head, $content);
        }
    }

    // Geminiのちからを借りて簡単にできた
    $parts = [
        'header'   => 'header.fumo',
        'footer'   => 'footer.fumo',
        'sidebar'  => 'sidebar.fumo',
        'sidebar2' => 'sidebar2.fumo',
        'ad' => 'ad.fumo'
    ];

    foreach ($parts as $tag => $file) {
        $path = $base_dir . "/fumo/" . $file;
        if (file_exists($path)) {
            $part_content = file_get_contents($path);
            $content = str_replace("<fumo:{$tag}>", $part_content, $content);
        }
    }

    // Re:TITLE処理
    $content = str_replace("<fumo:title>", $title_value, $content);

    // シンプルに置き換えする系の処理
    $content = str_replace("<fumo:fumo>", "ᗜˬᗜ", $content); // ᗜˬᗜ

    $content = str_replace("<fumo:now>", date("Y/m/d H:i:s"), $content); // 今の時間を出す
    $content = str_replace("<fumo:unix>", time(), $content); // unixで返すやつ


    // お返し申すってやる処理
    return $content;
}


// ファイルをゲットだぜ！
$file = preg_replace('/[^a-zA-Z0-9\/_-]/', '', $_GET['fumo_file'] ?? "");

// ほう、../を使ってくるのか、ならば成敗！
if (strpos($file, '..') !== false) {
    http_response_code(403); // 403を返す
    die("403 Fumobidden<br>アクセスしようとしたファイル名: ". htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . ".fumo");
}

// fの部分があるかないか確認する
if ($file == "") {

    // なかったらふもを返す
    echo "ふもᗜˬᗜ";

} else { // あったらまずfileがあるか確認する処理
    $base_dir = __DIR__;
    $target = $base_dir . "/" . $file . ".fumo"; // 拡張子ガッチャンコ

    // あるかないか確認
    if (file_exists($target)) {

        // ありますねぇの場合の処理
        // ターゲットから生のコンテントを読み込む
        $nama_content = file_get_contents($target);

        // fumo関数に投げる
        $okaesi_mousu = fumo($nama_content);

        // fumoの次はphp
        ob_start();
        ?>

        <?php
            try {
                // evalでいばる
                eval("?> " . $okaesi_mousu. " <?php ");
            } catch (Throwable $e) {
                echo "<div style='color:red;' class='fumo-error'>[fumo]: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        ?>

        <?php
        // ただいま
        $dededon = ob_get_clean();

        // デデドン
        echo $dededon;

    } else {
        http_response_code(404); // 404を返す

        // ないですの場合の処理
        echo "404 fumo not found<br>アクセスしようとしたファイル名: " . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . ".fumo";

    }
}
?>