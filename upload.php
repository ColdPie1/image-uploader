<?php
/* TODO: fill in URL */
$MY_URL = "https://your_host/dir_containing_this_file/";

header('Content-type: text/html');
?>

<!DOCTYPE html>
<html>
<head>
<style>
label {margin: 15px; }
input {margin: 15px; }
button {margin: 15px; }
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
    $img_sm = imagescale($image, $new_w, $new_h);
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

    if(!isset($_FILES["img"]) ||
            $_FILES["img"]["size"] <= 0){
        echo("Nothing to upload!\n");
        return;
    }

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

    $rename = $_POST["rename"];
    if($rename){
        $base = $rename;
    }
    $filename = $base . "." . $ext;

    if(file_exists($filename)){
        echo("File \"$filename\" already exists!\n");
        $overwrite = isset($_POST["overwrite"]) && $_POST["overwrite"] == "on";
        if(!$overwrite){
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

if(isset($_POST["passwd1"])){
    $passwd1 = $_POST["passwd1"];
    $passwd2 = $_POST["passwd2"];

    if(valid($passwd1, $passwd2)){
        do_upload();
    }else{
        echo("Invalid pw.");
    }
}
?></pre>

<?php

if(isset($filenames_to_copy)){
?>
    <label>URL:</label>
<?php
    foreach($filenames_to_copy as $fname){
        $display = $fname[0];
        $name = $MY_URL . $fname[1];
?>
        <input style="display:none;" type="text" readonly id="<?php echo($display); ?>" value="<?php echo($name); ?>">
        <button onclick="copy('<?php echo($display); ?>')"><?php echo($display); ?></button>
<?php
    }
?>

    <br><label>PHPBB:</label>
<?php
    foreach($filenames_to_copy as $fname){
        $display = $fname[0];
        $name = "[img]" . $MY_URL . $fname[1] . "[/img]";
?>
        <input style="display:none;" type="text" readonly id="<?php echo($display); ?>" value="<?php echo($name); ?>">
        <button onclick="copy('<?php echo($display); ?>')"><?php echo($display); ?></button>
<?php
    }
?>
<hr>
<?php
}
?>

<form method="post" enctype="multipart/form-data">
<label for="Secret">Secret</label>
<input type="text" id="passwd1" name="passwd1">
<input type="text" id="passwd2" name="passwd2"><br>

<label for="rename">Rename?</label>
<input type="text" id="rename" name="rename">
<label for="overwrite">OW?</label>
<input type="checkbox" id="overwrite" name="overwrite"><br>

<label for="img">Image</label>
<input type="file" id="img" name="img" accept="image/png,image/jpeg,image/webp,image/gif" /><br>

<input type="submit" value="Submit" />

</form>

</body>
</html>
