<?php
/* TODO: fill in URL */
$MY_URL = "https://your_host/dir_containing_this_file/";

header('Content-type: text/html');
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
label {margin-top: 10px; }
input {margin-top: 10px; }
button {margin-top: 10px; }
pre {background: #DDD; }
</style>
<script>
function copy(name) {
  // Get the text field
  var copyText = document.getElementById(name);

  // Select the text field
  copyText.select();
  copyText.setSelectionRange(0, 99999); // For mobile devices

  // Copy the text inside the text field
  navigator.clipboard.writeText(copyText.value);
}
</script>
</head>
<body>

<pre><?php

function is_checked($id)
{
    return isset($_POST[$id]) && $_POST[$id] == "on";
}

function get_sequence()
{
    if(isset($_POST["sequence"]) && $_POST["sequence"] != ""){
        return intval(trim($_POST["sequence"]));
    }
    return -1;
}

function get_dims($w, $h, $max_w)
{
    if ($max_w >= $w) {
        return array($w, $h);
    }
    $scale = ((float)$max_w) / ((float)$w);
    $new_w = $max_w;
    $new_h = (int)floor(((float)$h) * $scale);
    return array($new_w, $new_h);
}

function resize($image, $max_w, $suffix)
{
    list($new_w, $new_h) = get_dims(imagesx($image), imagesy($image), $max_w);
    $img_sm = imagescale($image, $new_w, $new_h, IMG_BICUBIC);
    if(!$img_sm){
        echo("error in scale");
        return;
    }
    return array($img_sm, $new_w, $new_h);
}

function clean_extension($suffix)
{
    if(strcasecmp($suffix, "jpg") == 0 || strcasecmp($suffix, "jpeg") == 0){
        return "jpg";
    }
    if(strcasecmp($suffix, "png") == 0){
        return "png";
    }
    if(strcasecmp($suffix, "webp") == 0){
        return "webp";
    }
    if(strcasecmp($suffix, "gif") == 0){
        return "gif";
    }
    return "";
}

function make_smaller($filename)
{
    $pivot = strrpos($filename, ".");
    $base = substr($filename, 0, $pivot);
    $suffix = substr($filename, $pivot + 1);

    $ext = clean_extension($suffix);
    if($ext == "jpg"){
        $image = imagecreatefromjpeg($filename);
    }else if($ext == "png"){
        $image = imagecreatefrompng($filename);
    }else if($ext == "webp"){
        $image = imagecreatefromwebp($filename);
    }else if($ext == "gif"){
        $image = imagecreatefromgif($filename);
    }

    list($img_s, $w, $h) = resize($image, 800, "s");
    $fname_s = $base . "_s." . $ext;
    echo("Saving $w" . "x$h image at \"$fname_s\".\n");

    list($img_t, $w, $h) = resize($image, 400, "t");
    $fname_t = $base . "_t." . $ext;
    echo("Saving $w" . "x$h image at \"$fname_t\".\n");

    if($ext == "jpg"){
        imagejpeg($img_s, $fname_s);
        imagejpeg($img_t, $fname_t);
    }else if($ext == "png"){
        imagepng($img_s, $fname_s);
        imagepng($img_t, $fname_t);
    }else if($ext == "webp"){
        imagewebp($img_s, $fname_s);
        imagewebp($img_t, $fname_t);
    }else if($ext == "gif"){
        imagegif($img_s, $fname_s);
        imagegif($img_t, $fname_t);
    }

    return array(["s", $fname_s], ["t", $fname_t]);
}

function do_upload()
{
    echo("Doing upload...\n");

    $upload_info = $_FILES["img"];

    $filename = $upload_info["name"];

    $pivot = strrpos($filename, ".");
    $base = substr($filename, 0, $pivot);
    $suffix = substr($filename, $pivot + 1);
    $ext = clean_extension($suffix);
    if($ext == ""){
        echo("Unsupported filetype: $suffix\n");
        return;
    }

    $rename = trim($_POST["rename"]);
    if($rename){
        $base = $rename;
    }

    if(is_checked("add_date")){
        $base = $base . "_" . date('Ymd', time());
    }

    $seq = get_sequence();
    if($seq >= 0){
        $base = $base . "_" . $seq;
    }

    $filename = $base . "." . $ext;

    if(file_exists($filename)){
        echo("File \"$filename\" already exists!\n");
        if(!is_checked("overwrite")){
            echo("Exiting.\n");
            return;
        }
        echo("Continuing anyway.\n");
    }

    echo("Placing uploaded file at \"$filename\".\n");
    if($ext == "jpg"){
        /* rewrite to strip EXIF data */
        $image = imagecreatefromjpeg($upload_info["tmp_name"]);
        imagejpeg($image, $filename);
    }else{
        move_uploaded_file($upload_info["tmp_name"], $filename);
    }

    echo("Beginning resize...\n");
    $filenames = make_smaller($filename);
    echo("Resize done.\n");

    global $filenames_to_copy;
    $filenames_to_copy = array_merge(array(["full", $filename]), $filenames);

    echo("Upload complete.\n");
}

function valid($pw1, $pw2)
{
    /* TODO: implement your clever auth idea here */
    return false;
}

//print_r($_POST);
//print_r($_FILES);
$passwd1_default = "";
$passwd2_default = "";
$rename_default = "";
$sequence_default = "";
$overwrite_default = "";
$add_date_default = " checked";
$do_list = false;

if(isset($_POST["passwd1"])){
    $passwd1 = trim($_POST["passwd1"]);
    $passwd2 = trim($_POST["passwd2"]);

    if(valid($passwd1, $passwd2)){
        $do_list = true;
        $seq = get_sequence();

        if(isset($_FILES["img"]) &&
                $_FILES["img"]["size"] > 0){
            do_upload();

            if($seq >= 0){
                $seq++;
            }
        }

        $passwd1_default = " value=\"$passwd1\"";
        $passwd2_default = " value=\"$passwd2\"";
        $rename_default = " value=\"" . trim($_POST["rename"]) . "\"";
        $overwrite_default = is_checked("overwrite") ? " checked" : "";
        $add_date_default = is_checked("add_date") ? " checked" : "";

        if($seq >= 0){
            $sequence_default = " value=\"" . $seq . "\"";
        }
    }else{
        echo("Invalid pw.");
    }
}
?></pre>

<?php

if(isset($filenames_to_copy)){
    $full_fname = $filenames_to_copy[0][1];
    $full_url = $MY_URL . rawurlencode($filenames_to_copy[0][1]);
?>
    <label><a href="<?php echo($full_url); ?>"><?php echo($full_fname); ?></a></label><br>
    <label>URL:</label>
<?php
    foreach($filenames_to_copy as $fname){
        $display = $fname[0];
        $name = $MY_URL . rawurlencode($fname[1]);
?>
        <input style="display:none;" type="text" readonly id="<?php echo($display); ?>url" value="<?php echo($name); ?>">
        <button onclick="copy('<?php echo($display); ?>url')"><?php echo($display); ?></button>
<?php
    }
?>

    <br><label>[img]:</label>
<?php
    foreach($filenames_to_copy as $fname){
        $display = $fname[0];
        $name = "[img]" . $MY_URL . rawurlencode($fname[1]) . "[/img]";
?>
        <input style="display:none;" type="text" readonly id="<?php echo($display); ?>phpbb" value="<?php echo($name); ?>">
        <button onclick="copy('<?php echo($display); ?>phpbb')"><?php echo($display); ?></button>
<?php
    }
?>
<hr>
<?php
}
?>

<form method="post" enctype="multipart/form-data">
<label>Secret</label><br>
<input type="text" <?php echo($passwd1_default); ?> id="passwd1" name="passwd1"><br>
<input type="text"<?php echo($passwd2_default); ?> id="passwd2" name="passwd2"><br>

<label for="rename">Rename?</label><br>
<input type="text"<?php echo($rename_default); ?> id="rename" name="rename" autocapitalize="off"><br>

<label for="sequence">Sequence?</label><br>
<input type="number"<?php echo($sequence_default); ?> id="sequence" name="sequence"><br>

<label for="overwrite">Overwrite?</label>
<input type="checkbox" id="overwrite" name="overwrite"<?php echo($overwrite_default); ?>><br>

<label for="add_date">Date?</label>
<input type="checkbox" id="add_date" name="add_date"<?php echo($add_date_default); ?>><br>

<input type="file" id="img" name="img" accept="image/png,image/jpeg,image/webp,image/gif" /><br>

<input type="submit" value="Submit" />

</form>

<?php
if($do_list){
?>
<hr>
<?php
    $idx = 0;
    foreach(glob("*.{jpg,png,webp,gif}", GLOB_BRACE) as $filename){
        $url = $MY_URL . rawurlencode($filename);
        $phpbb = "[img]" . $url . "[/img]";
        $id = "btn" . $idx;
        $idx++;
?>
    <label><a href="<?php echo($url); ?>"><?php echo($filename); ?></a></label>
    <button onclick="copy('<?php echo($id); ?>url')">URL</button>
    <button onclick="copy('<?php echo($id); ?>phpbb')">[img]</button>
    <input style="display:none;" type="text" readonly id="<?php echo($id); ?>url" value="<?php echo($url); ?>">
    <input style="display:none;" type="text" readonly id="<?php echo($id); ?>phpbb" value="<?php echo($phpbb); ?>"><br>

<?php
    }
}
?>

</body>
</html>
