<?php

class UnoMail
{
  // リクエストヘッダの設置
  // 何度も使いまわす文言は このようにCONSTを冒頭で定義します。
  CONST REQUEST_HEADER = 'content-type: application/json; charset=UTF-8';

  // プロパティ
  private $options = [];
  private $json = NULL;
  private $inputs = NULL;
  private $inputs_text = '';
  private $mail_to = '';
  private $mail_from_address = '';
  private $mail_from_name = '';
  private $mail_body = '';
  private $header = '';
  private $result = '';
  private $reply_mail_text = '';
  private $reply_mail_signature = '';
  private $reply_mail_to_address = '';
  private $reply_mail_to_name = '';
  private $reply_mail_to = '';
  private $reply_mail_body = '';
  private $reply_header = '';
  private $reply_result = '';

  // コンストラクタ
  public function __construct($options)
  {
      // 私はコンストラクタの中はなるべく初期化だけにします。
      $this->options = $options;
  }

  /**
   * レスポンスヘッダー生成
   * カプセル化を考える時、私は1つの処理に対して1メソッドと考えています
   *
   * @param string $domainName
   * @return void
   */
  public function createHead($domainName)
  {
    // ドメイン名は自身のドメインであれば取得可能
    // $domainName = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'];
    header("Access-Control-Allow-Origin: " . $domainName );
    header("Access-Control-Allow-Headers: Content-Type");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
  }
  
  /**
   * 入力データのチェック
   * 
   * ちなみにここに記載している文章は、php docs と言って関数の説明を冒頭に書く場所です。
   * みんな、まず最初にここを読んでからコードの読み込みに入ります。
   * 
   * vscode をお使いの場合は、左メニューの拡張機能から PHP DocBlocker をインストールしてください。
   * 関数を書き終わった後、/** と入力するだけでこれを書けます。引数なども自動的に補完してくれます。
   *
   * @return void
   */
  public function checkInputsData()
  {
    // json取得
    $this->json = file_get_contents("php://input");

    // jsonを受け取っていなければ終了
    if ( $this->json == NULL ) {
      header( self::REQUEST_HEADER );
      echo json_encode( '情報がありません', JSON_UNESCAPED_UNICODE );
      return;
    }

    // 不正データチェック
    $this->inputs = $this->checkInput( json_decode($this->json, true) );
    // 全空欄チェック
    $this->checkAllBlank( $this->inputs );
    // オブジェクトのテキスト変換
    $this->changeObjText( $this->inputs );
  }

  /**
   * フォームから持ってきたフラグによって件名等を出し分け (テンプレ化できそうだと思ったため作成)
   * 第二引数($subject)以降は オリジナルパターン時にのみ設定
   *
   * @param int $resFlag
   * @param string $subject 
   * @param string $introText 
   * @param string $autoReplySubject
   * @return void
   */
  public function setSubjectFlg( $resFlag, $subject = null, $introText = null, $autoReplySubject = null )
  {
    // 送信テンプレート1 通常運用
    if ($resFlag === 1) {
      $this->options['mail_subject_text'] = 'サイトからのお問い合わせ';
      $this->options['mail_intro_text'] = '以下の内容でお問い合わせがありました';
      // 自動返信
      $this->options['auto_reply_subject_text'] = 'お問い合わせありがとうございました';
      return;
    }

    // 送信テンプレート2 ○○ページからのお問い合わせ
    if ($resFlag === 2) {
      $this->options['mail_subject_text'] = '○○ページからのお問い合わせ';
      $this->options['mail_intro_text'] = '○○ページより、以下の内容でお問い合わせがありました';
      // 自動返信
      $this->options['auto_reply_subject_text'] = 'お問い合わせありがとうございました';
      return;
    }

    // 送信テンプレート3 ××ページからのお問い合わせ
    if ($resFlag === 3) {
      $this->options['mail_subject_text'] = '××ページからのお問い合わせ';
      $this->options['mail_intro_text'] = '××ページより、以下の内容でお問い合わせがありました';
      // 自動返信
      $this->options['auto_reply_subject_text'] = 'お問い合わせありがとうございました';
      return;
    }

    // いずれでもなければ独自パターンを返す
    $this->options['mail_subject_text'] = $subject;
    $this->options['mail_intro_text'] = $introText;
    $this->options['auto_reply_subject_text'] = $autoReplySubject;
    return;
  }  

  /**
   * メール生成処理 送信の可否を返す
   * returnがboolean型の時は 何の結果を返すか書いてあげると親切かもしれません。
   *
   * @return boolean
   */
  public function createMail()
  {
    // お問い合わせ内容取得
    $this->mail_body = $this->options['mail_intro_text'] . "\n\n" . $this->inputs_text;
    // お問い合わせ先の設定
    $this->mail_to = mb_encode_mimeheader( $this->options['mail_to_name'] ) . "<" . $this->options['mail_to_address'] . ">";

    // 送信元の設定
    $this->mail_from_name = $this->deleteLf( $this->h( $this->inputs[ $this->options['mail_from_name_id'] ][ 'value'] ) );
    $this->mail_from_address = $this->deleteLf( $this->h( $this->inputs[ $this->options['mail_from_address_id'] ][ 'value'] ) );
  
    mb_language( 'ja' );
    mb_internal_encoding( 'UTF-8' );

    // ヘッダーの設定
    $this->header = "From: " . mb_encode_mimeheader( $this->mail_from_name ) . "<" . $this->mail_from_address . ">\n";
    // 送信
    $this->result = mb_send_mail( $this->mail_to, $this->options['mail_subject_text'], $this->mail_body, $this->header, '-f'. $this->options['mail_to_address'] );
    
    return $this->result;
  }

  /**
   * 自動返信生成処理 送信の可否を返す
   * 後半はsendMailと内容似ているので、共通関数にした方がスマートかも...
   *
   * @return boolean
   */
  public function createAutoReplay()
  {
    // 定型文の設置
    $this->reply_mail_text = file_get_contents( __DIR__ . '/' . $this->options[ 'auto_reply_text_file' ] );
    $this->reply_mail_signature = file_get_contents( __DIR__ . '/' . $this->options[ 'auto_reply_signature_file' ] );

    // 自動返信メールの設定
    $this->reply_mail_body = $this->reply_mail_text . "\n\n" . $this->inputs_text . "\n\n" . $this->reply_mail_signature;
    $this->reply_mail_to_name = $this->mail_from_name;
    $this->reply_mail_to_address = $this->mail_from_address;
    $this->reply_mail_to = mb_encode_mimeheader( $this->reply_mail_to_name ) . "<" . $this->reply_mail_to_address . ">";
  
    mb_language( 'ja' );
    mb_internal_encoding( 'UTF-8' );

    // ヘッダーの設定
    $this->reply_header = "From: " . mb_encode_mimeheader( $this->options['mail_to_name'] ) . "<" . $this->options['mail_to_address'] . ">\n";
    // 送信
    $this->reply_result = mb_send_mail( $this->reply_mail_to, $this->options['auto_reply_subject_text'], $this->reply_mail_body, $this->reply_header, '-f'. $this->options['mail_to_address'] );
    
    return $this->reply_result;
  }

  /**
   * メール送信の最終チェック
   * OKなら送信!!
   *
   * @param boolean $result
   * @param boolean $replyResult
   * @return json (本来は返り値にjsonなんて型はありませんが、分かりやすいのでこう書いてます。)
   */
  public function execSendMail($result, $replyResult)
  {
    // 送信チェック resultとreply_result またはresultのみがOKなら complete!
    if( ( $result && $replyResult ) || ( $result ) ) {
      header( self::REQUEST_HEADER );
      echo json_encode("complete!");
      return;
    }

    // 私はなるべくネストが深くならない書き方を意識しています。
    // https://qiita.com/DeployCat/items/1ec901864d4ab11c8d6f
    // 
    header( self::REQUEST_HEADER );
    echo json_encode("fail");
    return;
  }

  /**
   * レスポンスエラー
   *
   * @return json
   */
  public function responceError()
  {
    header( self::REQUEST_HEADER );
    echo ( json_encode( '不正なアクセスです', JSON_UNESCAPED_UNICODE ) );
    return;
  }

  /** ***********************************
   * internal method
   * 
   * 私はpublicとprivateの境界に、このような記述をします
   * なくても構いませんが、私はこれを目印にしています。
  *********************************** */

  /**
   * エスケープ処理
   *
   * @param string $var
   * @return string
   */
  private function h( $var ) {

    if ( is_array( $var ) ) {

      return array_map( array( $this, 'h' ), $var );
    }
    else {

      return htmlspecialchars( $var, ENT_QUOTES, 'UTF-8' );
    }
  }

  /**
   * 不正データチェック
   *
   * @param string $var
   * @return string
   */
  private function checkInput( $var ) {

    if( is_array( $var ) ) {

      return array_map( array( $this, 'checkInput' ), $var );
    }

    if ( preg_match( '/\0/', $var ) ) {

      die( json_encode( '不正な入力です', JSON_UNESCAPED_UNICODE ) );
    }
      
    if ( !mb_check_encoding( $var, 'UTF-8' ) ) {

      die( json_encode( '不正な入力です', JSON_UNESCAPED_UNICODE ) );
    }

    if ( preg_match( '/\A[\r\n\t[:^cntrl:]]*\z/u', $var ) === 0 ) {

      die( json_encode( '不正な入力です。制御文字は使用できません', JSON_UNESCAPED_UNICODE ) );
    }

    return $var;
  }

  /**
   * 改行コード削除
   *
   * @param string $var
   * @return string
   */
  private function deleteLf( $var ) {

    $plain = str_replace( PHP_EOL, '', $var );

    return str_replace( array( '\r\n', '\r', '\n' ), '', $plain );
  }

  // 個別空欄チェック（0は除外）
  private function is_nullorempty( $var ) {

    if ( $var === 0 || $var === "0" ) {

      return false;
    }

    return empty( $var );
  }

  /**
   * 個別空欄チェック（ホワイトスペースも対象）
   *
   * @param string $var
   * @return boolean
   */
  private function is_nullorwhitespace( $var ) {

    if ( $this->is_nullorempty( $var ) === true ) {

      return true;
    }
    if ( is_string( $var ) && mb_ereg_match( '^(\s| )+$', $var ) ) {

      return true;
    }
    return false;
  }

  /**
   * 全空欄チェック
   *
   * @param string $var
   * @return json
   */
  private function checkAllBlank( $var ) {

    if ( is_array( $var ) ) {

      foreach ( $var as $content ) {

        if ( $this->is_nullorwhitespace( $content[ 'value' ] ) === false ) {

          return;
        }
      }
      die( json_encode( '全て空欄です', JSON_UNESCAPED_UNICODE ) );
    }

    die( json_encode( 'データが正しい形式ではありません', JSON_UNESCAPED_UNICODE ) );
  }

  /**
   * オブジェクトのテキスト変換
   *
   * @param object $obj
   * @return string
   */
  private function changeObjText( $obj ) {

    foreach ( $obj as $item ) {

      $this->inputs_text .= $this->h( $item[ 'label' ] ). ': ';

      if ( is_array( $item[ 'value' ] ) ) {

        foreach ( $item[ 'value' ] as $value_item ) {

          $this->inputs_text .= $this->h( $value_item ). ',';
        }

        $this->inputs_text .= "\n";
      }
      else {

        $this->inputs_text .= $this->h( $item[ 'value' ] ). "\n";
      }
    }
  }                                     
}

?>