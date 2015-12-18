<?php

/**************************
  セル定義
 **************************/
define('CELL_EMPTY', 0);
define('CELL_BLACK', 1);
define('CELL_WHITE', 2);
/**************************
  描画定義
 **************************/
define('VIEW_EMPTY', '  ');
define('VIEW_BLACK', '●');
define('VIEW_WHITE', '○');

// 盤面の初期化
$board = getFirstBoard();
// 黒番から
$turn = CELL_BLACK;

// 回す
while (true) {
  // 盤面の描画
  drawBoard($board);

  // 入力を受付
  echoConsole(($turn === CELL_BLACK ? "黒番です" : "白番です")."\n");
  $input = inputLine();
  // 所定の書式でない
  if (!preg_match('`^([a-h])([1-8])$`', $input, $matches)) {
    echoConsole("無効な座標です\n");
    continue;
  }

  $x = convertX($matches[1]);
  $y = (int)$matches[2] - 1;

  // ひっくり返せる駒がない
  $turnPosArray = getTurnPos($board, $x, $y, $turn);
  if ($turnPosArray === false || empty($turnPosArray)) {
    echoConsole("ひっくり返せる駒がないので置けません\n");
    continue;
  }

  // 置く
  $board[$x][$y] = $turn;
  // ひっくり返す
  foreach ($turnPosArray as $pos) {
    list($xPos, $yPos) = $pos;
    $board[$xPos][$yPos] = $turn;
  }

  // スコア計算
  list($blackScore, $whiteScore) = getScore($board);
  // 盤面が埋まっていたら終了
  if ($blackScore + $whiteScore >= 8*8) {
    break;
  }

  // 置けるか判定
  $nextMyPuttable = isPuttable($board, $turn);
  $nextEnemyPuttable = isPuttable($board, getEnemyConst($turn));
  // 次に両方が置けなければ終了
  if (!$nextMyPuttable && !$nextEnemyPuttable) {
    break;
  }
  // 次に相手が置けなければ自分の番のまま
  elseif (!$nextEnemyPuttable) {

  }
  // 置けるなら手番変更
  else {
    $turn = getEnemyConst($turn);
  }
}

// 最終画面の描画
drawBoard($board);
echoConsole("ゲーム終了。");
if ($blackScore > $whiteScore) {
  echoConsole("黒の勝ち！\n");
}
elseif ($blackScore < $whiteScore) {
  echoConsole("白の勝ち！\n");
}
else {
  echoConsole("引き分けでした\n");
}

/**
 * 黒と白の数を取得
 * @param array $board 
 * @return array [黒, 白]
 */
function getScore($board)
{
  $black = 0;
  $white = 0;
  for ($x = 0; $x < 8; $x++) {
    for ($y = 0; $y < 8; $y++) {
      switch ($board[$x][$y]) {
        case CELL_BLACK:
          $black++;
          break;
        case CELL_WHITE:
          $white++;
          break;
      }
    }
  }

  return [$black, $white];
}


/**
 * 敵の定数を取得
 * @param type $const 
 * @return type
 */
function getEnemyConst($const)
{
  return $const === CELL_BLACK ? CELL_WHITE : CELL_BLACK;
}

/**
 * 置けるところが一箇所でもあるか
 * @param type $board 
 * @param type $turn 
 * @return boolean
 */
function isPuttable($board, $turn)
{
  for ($x = 0; $x < 8; $x++) {
    for ($y = 0; $y < 8; $y++) {
      $turnPosArray = getTurnPos($board, $x, $y, $turn);
      if (false !== $turnPosArray && !empty($turnPosArray)) {
        return true;
      }
    }
  }

  return false;
}

/**
 * ひっくり返せるマスの座標を配列で取得
 * @param type $board 
 * @param type $x 
 * @param type $y 
 * @param type $stone 
 * @return false|array そもそも置けない場合はfalse
 */
function getTurnPos($board, $x, $y, $stone)
{
  // 座標外
  if (!array_key_exists($x, $board) || !array_key_exists($y, $board[$x])) {
    return false;
  }
  // 既に石がある
  if ($board[$x][$y] !== CELL_EMPTY) {
    return false;
  }

  // 相手の石を設定
  $enemyStone = getEnemyConst($stone);

  // ひっくり返せる座標
  $turnArray = [];
  // 走査
  for ($xPlus = -1; $xPlus <= 1; $xPlus++) {
    for ($yPlus = -1; $yPlus <= 1; $yPlus++) {
      // 同座標は無視
      if ($xPlus == 0 && $yPlus == 0) continue;

      // この方向でひっくり返せる座標
      $tempTurnArray = [];
      $xTemp = $x;
      $yTemp = $y;
      while (true) {
        $xTemp += $xPlus;
        $yTemp += $yPlus;
        // 盤の外に出たら終了
        if ($xTemp < 0 || 8 <= $xTemp || $yTemp < 0 || 8 <= $yTemp) break;
        // 空のセルに出たら終了
        elseif ($board[$xTemp][$yTemp] == CELL_EMPTY) break;
        // 相手の石だったらひっくり返し候補
        elseif ($board[$xTemp][$yTemp] == $enemyStone) {
          $tempTurnArray[] = [$xTemp, $yTemp];
        }
        // 自分の石だったらひっくり返し候補を精算して終了
        elseif ($board[$xTemp][$yTemp] == $stone) {
          $turnArray = array_merge($turnArray, $tempTurnArray);
          break;
        }
      } 
    }
  }

  // ひっくり返せる座標のリストを返す

  return $turnArray;
}

/**
 * a～hの座標文字を数値に変換
 * @param type $x 
 * @return type
 */
function convertX($x)
{
  switch ($x) {
    case "a":
      return 0;
    case "b":
      return 1;
    case "c":
      return 2;
    case "d":
      return 3;
    case "e":
      return 4;
    case "f":
      return 5;
    case "g":
      return 6;
    case "h":
      return 7;
    default:
      return false;
  }
}

/**
 * 成績を描画
 * @param array $board 
 * @return void
 */
function drawScore($board)
{
  list($blackScore, $whiteScore) = getScore($board);
  echoConsole("黒：".$blackScore."　　白：".$whiteScore."\n");
}

/**
 * 盤面を描画
 * @param array $board 
 * @return void
 */
function drawBoard($board)
{
  echoConsole("   a b c d e f g h\n");
  echoConsole("  ----------------\n");
  for ($y = 0; $y < 8; $y++) {
    echoConsole(($y+1)."|");
    for ($x = 0; $x < 8; $x++) {
      switch ($board[$x][$y]) {
        case CELL_BLACK:
          echoConsole(VIEW_BLACK);
          break;
        case CELL_WHITE:
          echoConsole(VIEW_WHITE);
          break;
        default:
          echoConsole(VIEW_EMPTY);
          break;
      }
    }
    echoConsole("|\n");
  }
  echoConsole("  ----------------\n");
  drawScore($board);
}

/**
 * 初期盤面を取得
 * @return type
 */
function getFirstBoard()
{
  $board = [];
  for ($x = 0; $x < 8; $x++) {
    $board[$x] = [];
    for ($y = 0; $y < 8; $y++) {
      $board[$x][$y] = CELL_EMPTY;
    }
  }

  $board[3][3] = CELL_WHITE;
  $board[4][4] = CELL_WHITE;
  $board[3][4] = CELL_BLACK;
  $board[4][3] = CELL_BLACK;

  return $board;
}

/**
 * Windowsのコンソールに文字を書く
 * @param type $string 
 * @return type
 */
function echoConsole($string)
{
  //echo $string;
  echo mb_convert_encoding($string, "Shift_JIS", "UTF-8");
}

/**
 * 標準入力から一行読み込み
 * @return type
 */
function inputLine()
{
  return mb_convert_encoding(trim(fgets(STDIN)), "UTF-8", "Shift_JIS");
}