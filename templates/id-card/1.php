<?php

use EduPress\EduPress;

$metadata = [];
if(!empty($data['person']['branch'])){
    $metadata[] = "Branch: " . $data['person']['branch'];
}
if(!empty($data['person']['shift'])){
    $metadata[] = "Shift: " . $data['person']['shift'];
}
if(!empty($data['person']['class'])){
    $metadata[] = "Class: " . $data['person']['class'];
}
if(!empty($data['person']['section'])){
    $metadata[] = "Section: " . $data['person']['section'];
}
if(!empty($data['person']['roll'])){
    $metadata[] = "Roll: " . $data['person']['roll'];
}
if(!empty($data['person']['mobile'])){
    $metadata[] = "Mobile: " . $data['person']['mobile'];
}

$metadata_text = '';
if(!empty($metadata)){
    $metadata_text = implode("<br>", $metadata);
}
$person_name =  ucwords(strtolower($data['person']['name']));
$qr_text = $person_name . "<br>" . $metadata_text;
$qr_text .= "<br>" . $data['institute']['title'];
$qr_text = str_replace( "<br>", "\n", $qr_text );
$qr_img_url = EduPress::createQr($qr_text, '', 1);
?>

<div class="card card-front">
    <div class="divider" style="height: 0.15in;"></div>
    <div class="institute-title"><?php echo $data['institute']['title']; ?></div>
    <div class="divider" style="height: 0.15in;"></div>
    <div class="person-photo" style="background-image: url('<?php echo $data['person']['photo']; ?>');"></div>
    <div class="divider" style="height: 0.15in;"></div>
    <div class="person-name"><?php echo $person_name; ?></div>
    <div class="divider" style="height: 0.1in;"></div>
    <?php if(!empty($data['person']['role'])): ?>
        <div class="person-role"><?php echo $data['person']['role']; ?></div>
        <div class="divider" style="height: 0.1in;"></div>
    <?php endif; ?>
    <div class="person-metadata">
        <?php echo $metadata_text; ?>
    </div>
</div>
<div class="pagebreak"></div>

<?php 
    $lost_data_text = $data['institute']['title'];
    if(!empty($data['institute']['address'])){
        $lost_data_text .= ', ' . $data['institute']['address'] . '.<br />';
    }
    if(!empty($data['institute']['phone'])){
        $lost_data_text .= 'Mobile: ' . $data['institute']['phone'] . '<br />';
    }
    if(!empty($data['institute']['website'])){
        $website = str_replace('https://', '', $data['institute']['website']);
        $website = str_replace('http://', '', $website);
        $website = str_replace('www.', '', $website);
        $website = str_replace('//', '/', $website);
        $website = str_replace('//', '/', $website);
        $lost_data_text .= 'Website: ' . $website;
    }
?>

<div class="card card-back">
    <div class="divider" style="height: 0.5in;"></div>
    <div class="qr-wrap"><img src="<?php echo $qr_img_url; ?>" alt="<?php echo $qr_text; ?>"></div>
    <div class="divider" style="height: 0.5in;"></div>
    <div class="back-instructions">
        If found, please return to <br> 
        <?php echo $lost_data_text; ?>
    </div>
</div>
<div class="pagebreak"></div>

<style>
    <?php 
        $colors = [
            'primary' => 'blue',
            'secondary' => 'red',
            'border' => '#1A3869',
            'background' => 'white',
            'text' => 'blue',
        ];
        $font_size = 14 - (count($metadata) * 0.55);
    ?>
    .divider{
        width: 100%;
        display: inline-block;
    }
    .pagebreak{
        page-break-after: always;
    }
    .card{
        height: 3.4in;
        width: 2.25in;
        overflow: hidden;
        display: inline-block;
        line-height: 0;
    }
    .card-front{
        background: url('<?php echo EDUPRESS_ID_TEMPLATES_IMG_URL . '1-front.png'; ?>') no-repeat center center;
    }
    .card-back{
        background: url('<?php echo EDUPRESS_ID_TEMPLATES_IMG_URL . '1-back.png'; ?>') no-repeat center center;
    }
    .card-front,
    .card-back{
        background-size: cover;
    }
    .institute-title{
        font-size: 12px;
        text-transform: uppercase;
        font-weight: bold;
        text-align: center;
        padding: 0 10px;
        width: 100%;
        box-sizing: border-box;
        height: 28px;
        overflow: hidden;
        line-height: 1em;
        color: <?php echo $colors['primary']; ?>;
        text-align: center;
    }
    .person-photo{
        height: 1in;
        width: 1in;
        border: 5px solid <?php echo $colors['border']; ?>;
        border-radius: 10px;
        padding: 0;
        margin: 0 auto;
        background-color: white;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
        overflow: hidden;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }
    .person-name{
        height: 28px;
        margin: 0 auto;
        padding: 0 10px;
        font-size: 14px;
        font-weight: bold;
        text-align: center;
        line-height: 1em;
        overflow: hidden;
        color: <?php echo $colors['text']; ?>;
        letter-spacing: 0;
        text-align: center;
        width: 100%;
        box-sizing: border-box;
    }
    .person-role{
        font-size: 11px;
        text-transform: capitalize;
        font-weight: 500;
        text-align: center;
        width: 100%;
        margin: 0 auto;
        padding: 0 10px;
        height: 12px;
        box-sizing: border-box;
        text-transform: uppercase;
    }
    .person-metadata{
        height: <?php echo count($metadata) * $font_size; ?>px;
        width: 100%;
        box-sizing: border-box;
        font-size: <?php echo $font_size; ?>px;
        text-align: center;
        line-height: 1em;
        padding: 0 20px;
        background-color: rgba(255, 255, 255, 0.5);
    }
    .qr-wrap{
        width: auto;
        text-align: center;
        margin: 0 auto;
    }
    .qr-wrap img{
        margin: 0 auto;
        padding: 5px;
        border: 2px solid #000;
    }
    .back-instructions{
        line-height: 1em;
        font-size: 10px;
        text-align: center;
        width: 100%;
        padding: 20px;
        box-sizing: border-box;
        background-color: rgba(255,255,255,0.5);
    }
</style>