<?php
// .fumoはなんか自分用のやつです
// 実行する優先度はfumoが上です


// 利用者のお前らがいじるconfig

// 自動挿入機能、使わないなら空白にしてクレメンス
$config_after_head  = ''; // <head>の直後（<fumo:head>が展開された直後）
$config_before_head = ''; // </head>の直前
$config_after_body  = ''; // <body>の直後
$config_before_body = ''; // </body>の直前

// メンテナンス機能
// ただし.fumoにしてるページだけしかメンテナンス機能は使えません
$mentenansu = false; // メンテナンス中はtrueにしよう、メンテナンス終わったらfalseにしよう
$mentenansu_text = "メンテナンス中<fumo:fumo>"; // メンテナンス中のメッセージ、生で挿入するからhtmlとphpとfumo使えるよ

// パンくず機能
$pankuzu_home = '<a href="/">ホーム</a>'; // パンくずのホームディレクトリの部分のhtml
$pankuzu_separator = ' &gt; '; // 階層の区切り文字（例: ' / ' や ' » ' などもOK）
$pankuzu_class = 'fumo-pankuzu'; // パンくず全体を囲むspanのCSSクラス名（装飾用）
$pankuzu_last_link = false; // 最後のページにリンクを貼るならtrue、貼らないならfalse
$pankuzu_titles = [ // パンくずのフォルダー名を置き換えるやつ
    'test' => 'てすと',
    'kamaboko' => 'かまぼこ',
    'sushi' => 'すし'
];
$pankuzu_before = '<span>ぱんくず: </span>'; // パンくずの前に挿入するhtml
$pankuzu_after = ''; // パンくずのあとに挿入するhtml

// 利用者のお前らがいじるconfig終わり！


header('Content-Type: text/html; charset=UTF-8'); // お前はUTF-8だ
date_default_timezone_set('Asia/Tokyo'); // お前は東京住まいだ

// かんすー作る！
function fumo($content) {
    global $config_after_head, $config_before_head, $config_after_body, $config_before_body; // globalとかいう知らない関数

    $base_dir = __DIR__;

    $title_value = "ふも"; // でふぉのページタイトル、<fumo:head>で何もなかったときにやるって思ったけど全然意味がないというかそもそもいらねえじゃねえかこれ無駄すぎる


    // 埋め込みます！

//自動挿入処理

    // ① <head> の直後に挿入（<fumo:noah> がなければ実行）
    if (!empty($config_after_head) && strpos($content, '<fumo:noah>') === false) {
        $content = preg_replace('/(<head\b[^>]*>)/i', "$1\n" . $config_after_head, $content);
    }

    // ② </head> の直前に挿入（<fumo:nobh> がなければ実行）
    if (!empty($config_before_head) && strpos($content, '<fumo:nobh>') === false) {
        $content = str_replace('</head>', $config_before_head . "\n" . '</head>', $content);
    }

    // ③ <body> の直後に挿入（<fumo:noab> がなければ実行）
    if (!empty($config_after_body) && strpos($content, '<fumo:noab>') === false) {
        $content = preg_replace('/(<body\b[^>]*>)/i', "$1\n" . $config_after_body, $content);
    }

    // ④ </body> の直前に挿入（<fumo:nobb> がなければ実行）
    if (!empty($config_before_body) && strpos($content, '<fumo:nobb>') === false) {
        $content = str_replace('</body>', $config_before_body . "\n" . '</body>', $content);
    }

    // 見ろ！<fumo:no〇〇>がゴミのようだ！
    $content = str_replace(['<fumo:noah>', '<fumo:nobh>', '<fumo:noab>', '<fumo:nobb>'], '', $content);

    // headタグ内のやつ(titleもできるように)
    if (file_exists($base_dir . "/fumo/head.fumo")) {
        $head_nakami = file_get_contents($base_dir . "/fumo/head.fumo");

        // <fumo:head:〇〇> または <fumo:head> にマッチするやーつ
        if (preg_match("/<fumo:head(?::(.*?))?>/", $content, $matches)) {

            // もしコロンの先の文字（$matches[1]）が存在して、空じゃなければそれをタイトルにする
            if (isset($matches[1]) && $matches[1] !== '') {
                $title_value = $matches[1];
            } else {
                // なければ、今アクセスされているファイル名を入れる
                $nama_title_value = preg_replace('/[^a-zA-Z0-9\/_-]/', '', $_GET['fumo_file'] ?? "index");

                // 生に拡張子と/を合体！
                $title_value = "/" . $nama_title_value . ".fumo";
            }

            $replaced_head = str_replace("<fumo:title>", $title_value, $head_nakami);
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

// パンくずのやつ
    if (strpos($content, '<fumo:pankuzu>') !== false) {
        global $pankuzu_home, $pankuzu_separator, $pankuzu_class, $pankuzu_last_link, $pankuzu_titles, $pankuzu_before, $pankuzu_after;

        $pankuzu_html = $pankuzu_home ?? '<a href="/">ホーム</a>';
        $separator = $pankuzu_separator ?? ' &gt; ';

        $current_file = preg_replace('/[^a-zA-Z0-9\/_-]/', '', $_GET['fumo_file'] ?? "");

        if ($current_file !== "" && $current_file !== "index") {
            $segments = explode('/', $current_file);
            $accumulated_path = '';
            $total_segments = count($segments);

            foreach ($segments as $index => $segment) {
                $accumulated_path .= ($accumulated_path === '' ? '' : '/') . $segment;
                $is_last = ($index === $total_segments - 1); // 最後の階層かどうか判定

                // もし翻訳用の配列（$pankuzu_titles）にこの英単語があれば、変身させる
                $display_name = $segment;
                if (isset($pankuzu_titles[$segment])) {
                    $display_name = $pankuzu_titles[$segment];
                }

                $pankuzu_html .= $separator;

                // 最後のページ、かつ「リンク無し」設定なら、aタグを貼らない
                if ($is_last && isset($pankuzu_last_link) && $pankuzu_last_link === false) {
                    $pankuzu_html .= htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8');
                } else {
                    $pankuzu_html .= '<a href="/' . htmlspecialchars($accumulated_path, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8') . '</a>';
                }
            }
        }

        // 前後の装飾文字を合体させて、spanで包む
        $before = $pankuzu_before ?? '';
        $after = $pankuzu_after ?? '';

        $p_class = $pankuzu_class ?? '';

        // もしクラス名が空っぽじゃなければ「 class="〇〇"」という文字列を作る
        $class_attr = '';
        if ($p_class !== '') {
            $class_attr = ' class="' . htmlspecialchars($p_class, ENT_QUOTES, 'UTF-8') . '"';
        }

        // 組み立てライン
        $final_pankuzu = '<span' . $class_attr . '>' . $before . $pankuzu_html . $after . '</span>';
        $content = str_replace("<fumo:pankuzu>", $final_pankuzu, $content);
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

// メンテナンス中の処理するらしい
if (isset($mentenansu) && $mentenansu === true) {
    http_response_code(503); // 503 Service Unavailable を返す
    header('Retry-After: 3600');

    // 設定された文字（HTMLやふもタグ入り）をふも関数に投げる
    $okaesi_mousu = fumo($mentenansu_text);

    // PHPも実行できるようにevalにいばらせる
    ob_start();
    try {
        eval("?> " . $okaesi_mousu . " <?php ");
    } catch (Throwable $e) {
        echo "<div style='color:red;'>[fumo-maintenance-error]: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    $dededon = ob_get_clean();

    // デデドンして終了！
    die($dededon);
}

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