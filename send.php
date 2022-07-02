<?php
// send.php は UnoMail.php の実行処理です。
// ↓UnoMail.php の中だけでも処理を完結できますが、classの定義処理と実行処理は別物と考えるべきだと思います
require dirname(__FILE__) . '/libs/UnoMail.php';

$unoMailConditions = array(
  // サイト情報によって変動
  'domain' => 'ドメイン名',
  'mail_to_address' => '設置者のメールアドレス',
  'mail_to_name' => '差出人の名前（サイト名など）',
  // フラグの出し分けをしない方が良さそうな場合は、そのまま以下で使ってください。
  // 'subjectFlg' => 0,
  // 'mail_subject_text' => 'サイトからのお問い合わせ',
  // 'mail_intro_text' => '以下の内容でお問い合わせがありました',
  // 'auto_reply_subject_text' => 'お問い合わせありがとうございました',
  // お問い合わせフォームのHTML構成によって変動
  'mail_from_address_id' => 'email',
  'mail_from_name_id' => 'name',
  // 自動返信の有無・確認ファイルの名前によって変動
  'auto_reply' => true,
  'auto_reply_text_file' => 'autoreply.txt',
  'auto_reply_signature_file' => 'signature.txt'
);

// インスタンス生成
$unoMail = new UnoMail( $unoMailConditions );

// 私は UnoMail.php を書く前にここを先に書きます。
// 大枠として、ここで何を実行しなければならないか処理の流れを落とし込んでから、関数の中身を書くように心掛けています。

// header生成
$unoMail->createHead( $unoMailConditions['domain'] );
// お問い合わせ内容のチェック
$unoMail->checkInputsData();
// POSTされていなければエラー
if ( !$_SERVER['REQUEST_METHOD'] === 'POST' ) { 
  $unoMail->responceError();
  return;
}
// フォームから送られてきた値により、何のフォームか判定して件名テンプレートを出し分け
$unoMail->setSubjectFlg( $unoMailConditions['subjectFlg'] );
// 運営者へのメール送信準備
$result = $unoMail->createMail();
// フラグによって自動返信
if ( !$unoMailConditions['auto_reply'] ) {
  $replyResult = false;
  return;
}
// 自動返信準備
$replyResult = $unoMail->createAutoReplay();

// メール送信の実行
$unoMail->execSendMail($result, $replyResult);

?>