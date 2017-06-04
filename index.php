<?php 
/**
 * source : drag and drop script (lehollandaisvolant)
 * JHyeok (Password, expire and more)
 */

/**
 * Function to create a random file name
 */
function random($car) {
    $string = "";
    $chaine = "abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    srand((double)microtime()*1000000);
    for($i=0; $i<$car; $i++) {
        $string .= $chaine[rand()%strlen($chaine)];
    }
    return $string;
}

$nbRandomCharacters = 7;
$serverMsg = '';
$folder = 'data';
$dataMappingFile = 'dataMapping.json';
$dataMappingFilePath = $folder . '/' . $dataMappingFile;
/**
 * Upload file to server
 */
if (is_writable($folder) && isset($_FILES['myfile'])) {

    /*
     * Data mapping recuperation
     */
    if(!is_file($dataMappingFilePath)){
        $dataMapping = array();
        file_put_contents($dataMappingFilePath, json_encode($dataMapping,JSON_UNESCAPED_UNICODE));
    }else{
        $dataMapping = json_decode(file_get_contents($dataMappingFilePath), true);      
    }

    $sFileName = $_FILES['myfile']['name'];
	$md5 = md5_file($_FILES["myfile"]["tmp_name"]);
    do{
        $randomName = random(7);
    }while($randomName === $dataMappingFile || is_file($folder . '/' . $randomName));

    if(true === move_uploaded_file($_FILES['myfile']['tmp_name'], $folder . '/' . $randomName)){
        $nbAvailableCopies = 1;

        if(isset($_GET['copy']) && (int)$_GET['copy'] > 0){
            $nbAvailableCopies = $_GET['copy'];
        }

//Read file password and file storage period by GET method
$filePassword = $_GET['passphrase'];
$fileExpireTime = time() + (($_GET['minvalue'])*60) + (($_GET['hourvalue'])*60*60) + (($_GET['dayvalue'])*60*60*24); // expire
        $dataMapping[$randomName] = array('name' => $_FILES['myfile']['name'], 'nb' => $nbAvailableCopies, 'pass' => $filePassword, 'expire' => $fileExpireTime, 'md5' => $md5);
        if(false !== file_put_contents($dataMappingFilePath, json_encode($dataMapping,JSON_UNESCAPED_UNICODE))){
			echo $randomName . "&p=";
            return;
        }
    }
}

/**
 * File download 
 */
if(isset($_GET['f']) && strlen($_GET['f']) == $nbRandomCharacters){ 
    if(is_file($folder . '/' . $_GET['f'])){
        /*
         * Data mapping recuperation
        */
		
        if(is_file($dataMappingFilePath)){
            $dataMapping = json_decode(file_get_contents($dataMappingFilePath), true); 
            if(isset($dataMapping[$_GET['f']]) && $dataMapping[$_GET['f']]['nb'] > 0 && $dataMapping[$_GET['f']]['expire'] > time()){ // Check here if file isn't expired.
                if (isset($_GET['p']) && $dataMapping[$_GET['f']]['pass'] === $_GET['p'] ) {
                    // I got a password on $_GET['p'], and it's correct for this file.
                    $file = $folder . '/' . $_GET['f'];
                    header('Content-type:application/octet-stream');
                    $size = filesize("./" . $file);
                    header("Content-Type: application/force-download; name=\"" . $dataMapping[$_GET['f']]['name'] . "\"");
                    header("Content-Transfer-Encoding: binary");
                    header("Content-Length: $size");
					header("Content-Disposition: attachment; filename=\"" . $dataMapping[$_GET['f']]['name'] . "\"");
                    header("Expires: 0");
                    header("Cache-Control: no-cache, must-revalidate");
                    header("Pragma: no-cache");
                    readfile("./" . $file);

                    $dataMapping[$_GET['f']]['nb']--; 
                    if($dataMapping[$_GET['f']]['nb'] == 0){
                        // Remove the file && the mapping
                        // the @s doesn't corrupt data output if error
                        @unlink($file); 
                        unset($dataMapping[$_GET['f']]);
                    }
                    @file_put_contents($dataMappingFilePath, json_encode($dataMapping, JSON_UNESCAPED_UNICODE));
                    return;
                } else {
					echo '<!doctype html>
			<head>
			<meta charset="UTF-8">
			<meta id="viewport" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
			<title>Simple and Powerful File upload</title>
			<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
			<script src="//code.jquery.com/jquery-1.12.1.min.js"></script>
			<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
			<script src="//cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.5.5/clipboard.min.js"></script>
			</head>
			<body>
			<div class="container">
			<div class="col-xs-12">
			<div style="height: 15px;"></div>
			<label>' . htmlspecialchars($dataMapping[$_GET['f']]['name']) . ' If the passwords of the files match, they are automatically downloaded.</label><br>
			<label>( file hash : ' . htmlspecialchars($dataMapping[$_GET['f']]['md5']) . ')</label><br>
			<label>If your password is incorrect, you will need to re-enter it. </label><br>
			<label>password : <input id="upass" type="password" maxlength="32" size="32" /> <input type="button" class="btn btn-success" onclick="pcheck();" value="Done"></label><br>
			<label>' . htmlspecialchars($dataMapping[$_GET['f']]['name']) . ' has a password, so you need to enter the password</label><br>
			<br><input type="button" class="btn btn-primary" onClick="gohome();" value="Upload"><br>
			</body>
			<script>
			var link = location.href
			link = link.slice(0,47)
			function pcheck() {
				var pw = document.getElementById("upass").value
				top.location=link+pw;
			}
			function gohome() { location.href="index.php"; }
			</script>
			</div>
			</div>
			</html>';
					// slice 0,53 <-- You need to modify it to suit your domain
                    // I haven't a password, or its incorrect.
                    //$serverMsg = 'Invalid file password, or password not provided';
					$serverMsg ='  ';
                }
            }else if (isset($dataMapping[$_GET['f']]) && $dataMapping[$_GET['f']]['expire'] < time()){ //대소문자만 달라져도 파일딜리트 되는거 수정함
                //If file have expired, delete it.
                $file = $folder . '/' . $_GET['f'];
                @unlink($file);
                unset($dataMapping[$_GET['f']]);
                $serverMsg = 'Time Over file delete';
            }else{
                $serverMsg = 'File not available'; 
            }
        }
    }else{ 
        $serverMsg = 'File not found';
    }
}


if(!(is_dir($folder) && is_writable($folder))){
    $serverMsg = 'Data folder is not writable';
}

/*
 * Get max upload size
 */
$max_upload = (int)(ini_get('upload_max_filesize'));
$max_post = (int)(ini_get('post_max_size'));
$memory_limit = (int)(ini_get('memory_limit'));
$upload_mb = min($max_upload, $max_post, $memory_limit);
$upload_b = $upload_mb * 1024 * 1024;

?>
<!DOCTYPE html><html lang="ko">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Simple and Powerful File upload</title>
	<meta charset="utf-8">
	</head>
    <body>
        <div id="content">
            <section id="midle">
                <div class="upload_form_cont">
                    <?php if(!empty($serverMsg)){?>
                        <div class="error"><?php echo $serverMsg;?></div>
                    <?php }else{?>
                        <div class="info">
                            <div id="upload-zone">Drag and drop files here</div>
                        </div>
						<br/><br/>
						passwrod (select option) : <input type="password" id="upload-pw" maxlength="32" size="32" pattern="^\w*$"><br/>
						<br/>
                        <div>Downloadable Numbers : <input type="text" id="upload-copy" value="50"></input></div><br>					
						<p>file storage time</p>
						<div id="time-input">
						<input type="number" id="time-day" value="0" min="0" max="30" style="border: none; border-bottom: 2px solid red; background-color: transparent; text-align: center; width:15%;" onClick="this.select();">day
						<input type="number" id="time-hour" value="24" min="0" max="23" style="border: none; border-bottom: 2px solid red; background-color: transparent; text-align: center; width:15%;" onClick="this.select();">hour
						<input type="number" id="time-min" value="0" min="0" max="59" style="border: none; border-bottom: 2px solid red; background-color: transparent; text-align: center; width:15%;" onClick="this.select();">min
						</div>
						<div>Max upload size : <?php echo $upload_mb;?> Mb</div>
                    <?php }?>
							<div id="progress"></div>
						</div>
					</section>
			<div class="progress"></div>
	<script type='text/javascript'>var maxUpload = <?php echo $upload_b; ?>;</script>
	<script src="assets/js/upload.js"></script>
    </body>
</html>