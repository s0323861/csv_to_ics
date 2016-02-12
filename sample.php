<?php

if (is_uploaded_file($_FILES['upfile']['tmp_name'])) {

  $finfo = new finfo(FILEINFO_MIME_TYPE);

  if (!isset($_FILES['upfile']['error']) || !is_int($_FILES['upfile']['error'])) {
    $error = "パラメータが不正です (parameter is incorrect.)";
  }elseif ($_FILES['upfile']['size'] > 1000000) {
    $error = "ファイルサイズが大きすぎます (file size is too large.)";
  }elseif (!$ext = array_search(
    $finfo->file($_FILES['upfile']['tmp_name']),
    array('csv' => 'text/plain'), true)) {
    $error = "ファイル形式が不正です (file format is incorrect.)";
  }

  if(empty($error)){

    // prevent garbling
    $fname = $_FILES['upfile']['name'];
    $fname = mb_convert_encoding($fname, 'UTF-8', 'auto');

    if (move_uploaded_file($_FILES['upfile']['tmp_name'], "tmp/" . $fname)) {
      chmod("tmp/" . $fname, 0644);

      $filepath = "./tmp/" . $fname;

      $fp = fopen($filepath, 'r');

      $i = 0;

      if ($fp){
        if (flock($fp, LOCK_SH)){
          while (!feof($fp)) {

            $line = fgets($fp);
            $line = trim($line);
            $line = mb_convert_encoding($line, 'UTF-8', 'auto');
            $line = str_replace(PHP_EOL, '', $line);
            if($i == 0){
              // header of csv file
              $header = $line;
            }else{
              $data[] = $line;
            }
            $i++;
          }
          flock($fp, LOCK_UN);
        }
      }

      fclose($fp);

      unlink($filepath);

      $header_item = explode(",", $header);

      // designate file path
      $file_name = "./tmp/" . rand() . ".ics";

      // open file
      $fp = fopen($file_name, 'w');

      // number of data
      $i = $i - 2;

      // write header part
      $common = "BEGIN:VCALENDAR" . "\n";
      fputs($fp, $common);
      $common = "VERSION:2.0" . "\n";
      fputs($fp, $common);
      $common = "PRODID:-//hacksw/handcal//NONSGML v1.0//EN" . "\n";
      fputs($fp, $common);
      $common = "CALSCALE:GREGORIAN" . "\n";
      fputs($fp, $common);
      $common = "BEGIN:VEVENT" . "\n";
      fputs($fp, $common);

      // write data
      for($count = 0; $count < $i; $count++){
        for($j = 0; $j < count($header_item); $j++){
          $data_item = explode(",", $data[$count]);
          $item = $header_item[$j] . ":" . $data_item[$j] . "\n";
          fputs($fp, $item);
        }
      }

      // write footer part
      $common = "END:VCALENDAR" . "\n";
      fputs($fp, $common);

      fclose($fp);

      // download ics file
      $fname = "event.ics";
      header('Content-Type: application/force-download');
      header('Content-Length: ' . filesize($file_name));
      header('Content-disposition: attachment; filename="' . $fname . '"');
      readfile($file_name);

      // delete file
      unlink($file_name);

    }

  }

}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="">
<meta name="keywords" content="csv,ics,converter">
<meta name="author" content="Akira Mukai">
<title>CSV to ICS Converter</title>
  <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
  <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
  <style type="text/css">
  body { padding-top: 80px; }
  @media ( min-width: 768px ) {
    #banner {
      min-height: 300px;
      border-bottom: none;
    }
    .bs-docs-section {
      margin-top: 8em;
    }
    .bs-component {
      position: relative;
    }
    .bs-component .modal {
      position: relative;
      top: auto;
      right: auto;
      left: auto;
      bottom: auto;
      z-index: 1;
      display: block;
    }
    .bs-component .modal-dialog {
      width: 90%;
    }
    .bs-component .popover {
      position: relative;
      display: inline-block;
      width: 220px;
      margin: 20px;
    }
    .nav-tabs {
      margin-bottom: 15px;
    }
    .progress {
      margin-bottom: 10px;
    }
  }
  </style>

  <!--[if lt IE 9]>
    <script src="//oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="//oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->

</head>
<body>

<header>
  <div class="navbar navbar-default navbar-fixed-top">
    <div class="container">
      <div class="navbar-header">
        <a href="./" class="navbar-brand"><i class="fa fa-flask"></i> Tools</a>
        <button class="navbar-toggle" type="button" data-toggle="collapse" data-target="#navbar-main">
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
      </div>
    </div>
  </div>
</header>

<div class="container">

  <div class="row">

    <!-- Blog Entries Column -->
    <div class="col-lg-12">

    <h1 class="page-header">
    <i class="fa fa-wrench"></i> CSV to ICS Converter<br>
    <small>CSV形式からICS形式へファイルを変換します。</small>
    </h1>

    </div>

  </div>

  <!-- Forms
  ================================================== -->
  <div class="row">

    <div class="col-sm-12">

<?php
  if(!empty($error)){
    echo <<<EOM
      <div class="bs-component">
        <div class="alert alert-dismissible alert-danger">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <strong>エラー！ (error)</strong> {$error}
        </div>
      </div>
EOM;
  }
?>
      <div class="well bs-component">

        <form action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" enctype="multipart/form-data" method="post">
        <fieldset>

        <legend><i class="fa fa-cloud-upload"></i></legend>

        <div class="form-group">
          <label for="inputSubject" class="control-label">ファイルを選択 (select .csv file)</label>
          <input type="file" name="upfile" class="form-control" id="inputSubject" placeholder="Select ics file" value="<?php echo $_POST["upfile"]; ?>" required>
        </div>

        <div class="form-group">
          <button type="submit" class="btn btn-primary"><i class="fa fa-wrench"></i> 変換する (Convert)</button>
        </div>

        </fieldset>
        </form>

      </div>

    </div>

  </div>

  <div class="row">

    <div class="col-sm-12">

    <h2>Sample CSV File</h2>
      <div class="bs-component">
        <p class="text-success">Here is an example of a CSV file:</p>
        <div class="alert alert-dismissible alert-info">
        <p>
DTSTART,DTEND,DTSTAMP,UID,DESCRIPTION,LAST-MODIFIED,LOCATION,SEQUENCE,STATUS,SUMMARY<br>
20150325,20150326,20140401T060631Z,c695jtap6pa4q8v3s0redru5mk@google.com,,20140401T060147Z,,0,CONFIRMED,卒業式<br>
20150312,20150313,20140401T060631Z,spk3ifbeiitv3od8ramb7nih44@google.com,,20140401T060136Z,,0,CONFIRMED,個別学力検査（後期日程）<br>
20150227,20150228,20140401T060631Z,e7fbc1m84eqijdd7tirds43a5s@google.com,,20140401T060054Z,,0,CONFIRMED,再試験成績入力締切<br>
20150225,20150227,20140401T060631Z,srvlhof67qbvm0ghlinu6nfjbo@google.com,,20140401T060020Z,,0,CONFIRMED,個別学力検査（前期日程）<br>
20150223,20150224,20140401T060631Z,b51l5hak83ggfku5dl4f907ab0@google.com,,20140401T055942Z,,0,CONFIRMED,1～3年次成績入力締切<br>
20150218,20150219,20140401T060631Z,qv875ojca7gct92btvkjkqdsag@google.com,,20140401T055906Z,,0,CONFIRMED,4年次成績入力締切<br>
20150117,20150119,20140401T060631Z,nl52u8j05h10suhav4etobf4ac@google.com,,20140401T055738Z,,0,CONFIRMED,大学入試センター試験<br>
20150116,20150117,20140401T060631Z,snkvcj0f5ld63urcjtvpmk3jq8@google.com,,20140401T055711Z,,0,CONFIRMED,休講（大学入試センター試験試験場設営・下見）<br>
       </p>
       </div>
     </div>

    <h2>Explanation</h2>
      <div class="bs-component">
        <div class="alert alert-dismissible alert-warning">
          <p>Note that the first line of the file lists the field names.<br>
          A CSV file must be encoded in UTF-8 and have "*.csv" extension.</p>
        </div>
      </div>

    </div>

  </div>

  <hr>

  <!-- Footer -->
  <footer>
  <div class="row">
    <div class="col-lg-12">
    <p>Copyright (C) 2016 <a href="http://tsukuba42195.top/">Akira Mukai</a></p>
    </div>
    <!-- /.col-lg-12 -->
  </div>
  <!-- /.row -->
  </footer>

</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>

</body>
</html>
