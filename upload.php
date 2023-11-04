<?php

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
    echo("got $new_w, $new_h");
    $img_sm = imagescale($image, $new_w, $new_h);
    if(!$img_sm){
        echo("error in scale");
        return;
    }
    return $img_sm;
}

function make_smaller($filename)
{
    $pivot = strrpos($filename, ".");
    $base = substr($filename, 0, $pivot);
    $suffix = substr($filename, $pivot + 1);

    if(strcasecmp($suffix, "jpg") == 0 || strcasecmp($suffix, "jpeg") == 0){
        $mode = "jpg";
        $image = imagecreatefromjpeg($filename);
    }else if(strcasecmp($suffix, "png") == 0){
        $mode = "png";
        $image = imagecreatefrompng($filename);
    }else if(strcasecmp($suffix, "webp") == 0){
        $mode = "webp";
        $image = imagecreatefromwebp($filename);
    }else if(strcasecmp($suffix, "gif") == 0){
        $mode = "gif";
        $image = imagecreatefromgif($filename);
    }

    $img_s = resize($image, 800, "s");
    $fname_s = $base . "_s." . $suffix;

    $img_t = resize($image, 400, "t");
    $fname_t = $base . "_t." . $suffix;

    if($mode == "jpg"){
        imagejpeg($img_s, $fname_s);
        imagejpeg($img_t, $fname_t);
    }else if($mode == "png"){
        imagepng($img_s, $fname_s);
        imagepng($img_t, $fname_t);
    }else if($mode == "webp"){
        imagewebp($img_s, $fname_s);
        imagewebp($img_t, $fname_t);
    }else if($mode == "gif"){
        imagegif($img_s, $fname_s);
        imagegif($img_t, $fname_t);
    }
}

header('Content-type: text/html');

make_smaller("image.jpg");

?>

<img src="image_s.jpg"/>
<img src="image_t.jpg"/>
