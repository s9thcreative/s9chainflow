# s9chainflow
S9ChainFlowのフレームワーク

## ファイル構成
 - .htaccess mod_rewriteの設定
 - assets/ 画像、スタイルなどの静的コンテンツ
 - env/ 環境依存設定
 - myapp/ アプリケーション本体。ここに処理を追加していく。
 - setup/ 設定ファイル
 - views/ 画面、メールのテンプレート

## パスから処理を起動
 "http://〜〜〜〜〜/s9chainlab/top/index" の場合
 
 "http://〜〜〜〜〜" -> env/myapp/myappboot.php に記載。サーバの設定。
 
 "/s9chainlab" -> env/env.php に記載。アプリケーションまでのパス。
 
 "/top/index" -> アプリケーションの実行パス。
 
   myapp/ControlU/ControlU_top.php \MyApp\ControlU\ControlU_topのaction_indexメソッドを起動
 
