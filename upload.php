<?php
/*  Simple image uploader script.
 *
 *  Copyright (C) 2024  Andrew Eikum
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/* TODO: fill in URL */
$MY_URL = "https://your_host/dir_containing_this_file/";

header('Content-type: text/html');
header('X-Robots-Tag: noindex');
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
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
        $img_sm = imagescale($image, $new_w, $new_h/*, default scaling */);
        if(!$img_sm){
            echo("Error in imagescale!\n");
            return;
        }
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
    return null;
}

function load_pel()
{
    set_include_path("./pel/" . PATH_SEPARATOR . get_include_path());
    require_once "Pel.php";
    require_once "PelException.php";
    require_once "PelDataWindowOffsetException.php";
    require_once "PelDataWindowWindowException.php";
    require_once "PelIfdException.php";
    require_once "PelIllegalFormatException.php";
    require_once "PelInvalidArgumentException.php";
    require_once "PelMakerNotesMalformedException.php";
    require_once "PelOverflowException.php";
    require_once "PelEntryException.php";
    require_once "PelUnexpectedFormatException.php";
    require_once "PelWrongComponentCountException.php";
    require_once "PelConvert.php";
    require_once "PelDataWindow.php";
    require_once "PelEntry.php";
    require_once "PelEntryNumber.php";
    require_once "PelEntryAscii.php";
    require_once "PelEntryByte.php";
    require_once "PelEntryCopyright.php";
    require_once "PelEntryLong.php";
    require_once "PelEntryRational.php";
    require_once "PelEntrySByte.php";
    require_once "PelEntryShort.php";
    require_once "PelEntrySLong.php";
    require_once "PelEntrySRational.php";
    require_once "PelEntrySShort.php";
    require_once "PelEntryTime.php";
    require_once "PelEntryUndefined.php";
    require_once "PelEntryUserComment.php";
    require_once "PelEntryVersion.php";
    require_once "PelEntryWindowsString.php";
    require_once "PelJpegContent.php";
    require_once "PelFormat.php";
    require_once "PelTag.php";
    require_once "PelInvalidDataException.php";
    require_once "PelMakerNotes.php";
    require_once "PelCanonMakerNotes.php";
    require_once "PelIfd.php";
    require_once "PelTiff.php";
    require_once "PelExif.php";
    require_once "PelJpegComment.php";
    require_once "PelJpegInvalidMarkerException.php";
    require_once "PelJpegMarker.php";
    require_once "PelJpeg.php";
}

function copy_ifd($into_ifd, $from_ifd)
{
    $forbidden_tags = [
        lsolesen\pel\PelTag::PIXEL_X_DIMENSION,
        lsolesen\pel\PelTag::PIXEL_Y_DIMENSION,
        lsolesen\pel\PelTag::X_RESOLUTION,
        lsolesen\pel\PelTag::Y_RESOLUTION,
        lsolesen\pel\PelTag::RESOLUTION_UNIT,
        lsolesen\pel\PelTag::ORIENTATION, //accounted for during resize
    ];

    foreach($from_ifd->getEntries() as $tag => $v){
        if(!in_array($tag, $forbidden_tags)){
            //echo("copying $tag -> $v\n");
            $into_ifd->addEntry($v);
        }else{
            //$tagname = lsolesen\pel\PelTag::getName($v->getIfdType(), $v->getTag());
            //echo("NOT copying " . $tagname . "\n");
        }
    }

    foreach($from_ifd->getSubIfds() as $key => $from_subifd){
        $into_subifd = new lsolesen\pel\PelIfd($key);
        $into_ifd->addSubIfd($into_subifd);
        copy_ifd($into_subifd, $from_subifd);
    }

    $from_nextifd = $from_ifd->getNextIfd();
    if($from_nextifd){
        $into_nextifd = new lsolesen\pel\PelIfd($from_nextifd->getType());
        $into_ifd->setNextIfd($into_nextifd);
        copy_ifd($into_nextifd, $from_nextifd);
    }
}

function merge_metadata($filename_into, $filename_from)
{
    echo("Merging JPEG metadata.\n");
    load_pel();

    $from_jpeg = new lsolesen\pel\PelJpeg($filename_from);

    /* copy exif stuff */
    $from_exif = $from_jpeg->getExif();
    if($from_exif){
        $from_ifd = $from_exif->getTiff()->getIfd();

        $into_jpeg = new lsolesen\pel\PelJpeg($filename_into);
        $into_exif = $into_jpeg->getExif();
        if(!$into_exif){
            $into_exif = new lsolesen\pel\PelExif();
            $into_tiff = new lsolesen\pel\PelTiff();
            $into_ifd = new lsolesen\pel\PelIfd(lsolesen\pel\PelIfd::IFD0);
            $into_tiff->setIfd($into_ifd);
            $into_exif->setTiff($into_tiff);
            $into_jpeg->setExif($into_exif);
        }else{
            $into_ifd = $into_exif->getTiff()->getIfd();
        }

//        echo("------------------------------------------------------ FROM: ----------------------------------------------\n$from_ifd\n");
//        echo("------------------------------------------------------ INTO BEFORE: ---------------------------------------\n$into_ifd\n");

        copy_ifd($into_ifd, $from_ifd);

//        echo("------------------------------------------------------ INTO AFTER: ----------------------------------------\n$into_ifd\n");
    }

    /* copy icc data */
    $from_icc = $from_jpeg->getICC();
    if($from_icc){
        $into_jpeg->setICC($from_icc);
    }

    $into_jpeg->saveFile($filename_into);
}

function make_smaller($filename, $orientation)
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

    switch($orientation){
        case 1:
            break;
        case 2:
            imageflip($image, IMG_FLIP_HORIZONTAL);
            break;
        case 3:
            $image = imagerotate($image, 180, 0);
            break;
        case 4:
            imageflip($image, IMG_FLIP_VERTICAL);
            break;
        case 5:
            $image = imagerotate($image, -90, 0);
            imageflip($image, IMG_FLIP_HORIZONTAL);
            break;
        case 6:
            $image = imagerotate($image, -90, 0);
            break;
        case 7:
            $image = imagerotate($image, 90, 0);
            imageflip($image, IMG_FLIP_HORIZONTAL);
            break;
        case 8:
            $image = imagerotate($image, 90, 0);
            break;
        default:
            echo("Invalid orientation! Continuing with resize anyway.");
            break;
    }

    list($img_s, $w, $h) = resize($image, 800, "s");
    if(!$img_s){
        return;
    }
    $fname_s = $base . "_s." . $ext;
    echo("Saving $w" . "x$h image at \"$fname_s\".\n");

    list($img_t, $w, $h) = resize($image, 400, "t");
    if(!$img_s){
        return;
    }
    $fname_t = $base . "_t." . $ext;
    echo("Saving $w" . "x$h image at \"$fname_t\".\n");

    if($ext == "jpg"){
        imagejpeg($img_s, $fname_s);
        merge_metadata($fname_s, $filename);
        imagejpeg($img_t, $fname_t);
        merge_metadata($fname_t, $filename);
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

function strip_exif($filename_from, $filename_to)
{
    echo("Cleaning JPEG metadata.\n");
    load_pel();

    $orientation = 1; /* no change needed */

    $jpeg = new lsolesen\pel\PelJpeg($filename_from);
    $exif = $jpeg->getExif();

    if(!$exif){
        /* nothing to strip, just move it directly */
        move_uploaded_file($upload_info["tmp_name"], $filename);
        return $orientation;
    }

    $ifd = $exif->getTiff()->getIfd();
    //echo("$ifd");
    while($ifd != null){
        /* remove GPS subifd from each ifd */
        if($ifd->getSubIfd(lsolesen\pel\PelIfd::GPS)){
            echo("Removing GPS data.\n");
            $ifd->removeSubIfd(lsolesen\pel\PelIfd::GPS);
        }

        /* search for orientation tag */
        if(isset($ifd[lsolesen\pel\PelTag::ORIENTATION])){
            $v = $ifd[lsolesen\pel\PelTag::ORIENTATION];
            echo("Detected orientation: " . $v->getValue() . " (" . $v->getText() . ").\n");
            $orientation = $v->getValue();
        }

        $ifd = $ifd->getNextIfd();
    }

    $jpeg->saveFile($filename_to);

    return $orientation;
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
    if(!$ext){
        echo("Unsupported filetype: $suffix!\n");
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
        echo("File \"$filename\" already exists!");
        if(!is_checked("overwrite")){
            echo(" Exiting.\n");
            return;
        }
        echo(" Overwrite set, so continuing anyway.\n");
    }

    echo("Placing uploaded file at \"$filename\".\n");
    if($ext == "jpg"){
        $orientation = strip_exif($upload_info["tmp_name"], $filename);
    }else{
        move_uploaded_file($upload_info["tmp_name"], $filename);
        $orientation = 1; /* no change needed */
    }

    echo("Beginning resize...\n");
    $filenames = make_smaller($filename, $orientation);
    if(!$filenames){
        return;
    }
    echo("Resize done.\n");

    global $filenames_to_copy;
    $filenames_to_copy = array_merge(array(["full", $filename]), $filenames);

    echo("Upload complete.\n");
}

function secret_valid($pw1, $pw2)
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

    if(secret_valid($passwd1, $passwd2)){
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
        echo("Invalid secret.");
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
