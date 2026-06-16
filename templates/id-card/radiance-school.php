<?php 
        ob_start(); 
        ?> 
        <html>
            <head>
                <link rel="preconnect" href="https://fonts.googleapis.com">
                <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
            </head>
            <body class="print-card">
                <style>
                    body.print-card{
                        width: 290px; /* 54.61mm; */
                        height: 458px; /* 86.36mm; */
                        margin: 0;
                        padding: 0;
                        background-color: rgba(0, 255, 255. 0.2);
                        -webkit-print-color-adjust: exact !important;
                        color-adjust: exact !important;
                        print-color-adjust: exact !important;
                        box-sizing: border-box;

                    }
                    .page-content{
                        position: relative !important;
                        width: 100% !important;
                        height: 100% !important;
                        background-color: #aaa;
                    }
                    .id-card-holder{
                        width: 290px; /* 54.61mm; */
                        height: 458px; /* 86.36mm; */
                        background-color: #fff;
                        border: none;
                        background-size: 100% 100%;
                        background-position: center;
                        background-repeat: no-repeat;
                        position: relative;
                    }
                    .id-card-inner{
                        width: 100%;
                        height: 100%;
                        background-color: transparent;
                        border: none;
                        padding: 5mm;
                        box-sizing: border-box;
                        /* top: 85mm;
                        left: 12mm; */
                        position: relative;
                        line-height: 1;
                        z-index: 9999;
                    }
                    .pagebreak{
                        page-break-after: always;
                    }
                    .id-thumb-wrap{
                        text-align: center;
                        margin-top: 125px;
                    }
                    .id-thumb{
                        border-radius: 100%;
                        width: 125px;
                        height: auto;
                        text-align: center;
                        margin: 0 auto;
                        border: 3px solid #fff;
                    }
                    .details-wrap{
                        width: 175px;
                        margin-top: 10px;
                    }
                    .details-wrap{
                        font-family: 'Poppins', sans-serif;
                        color: #fff;
                        font-weight: bold;
                    }
                    .name{
                        font-size: 20px;
                        font-weight: bold; 
                        color: #fff;
                        line-height: 1;
                        margin-bottom: 5px;
                    }
                    span.key{
                        color: #ffde59;
                        font-size: 14px;
                        line-height: 1.2;
                    }
                    span.value{
                        color: white;
                        font-size: 14px;
                        line-height: 1.2;
                    }
                    .mobile{
                        margin-top: 7px;
                        padding: 5px 7px;
                        background-color: #fff;
                        border-radius: 50px;
                        display: inline-block;
                        color: #409346;
                        line-height: 1
                        height: 20px;
                        vertical-align: middle;
                    }
                    .transparent-bg{
                        opacity: 0;
                    }
                </style>
                <?php 
                    $users = User::getAll(['role'=>'student', 'number'=>2000, 'orderby'=>'ID','order'=>'DESC']);
                    if(empty($users)){
                        echo "<div class='no-users'>No users found to print</div>";
                        return ob_get_clean();
                    }
                    foreach($users as $user):
                        $section_id = get_user_meta($user->ID,'section_id', true);
                        
                        $metadata = get_metadata('user', $user->ID);
                        $data = [];
                        $class_id = $metadata['class_id'][0] ?? '';
                        $class = get_the_title($class_id);
                        $section_id = $metadata['section_id'][0] ?? '';
                        $section = get_the_title($section_id);
                        $section_title = "Section";
                        $attendance_id = (int) $metadata['attendance_id'][0] ?? '';

                        $skip_ids = [4952, 4968, 5951, 5148];
                        if(in_array($attendance_id, $skip_ids)) continue;

                        $name = isset($metadata['first_name']) ? $metadata['first_name'][0] : '';
                        $name_ids = [5143, 5946];
                        if(!in_array($attendance_id, $name_ids)) $name = ucwords(strtolower($name));



                        // check if a eitehr play, kg, nursery, nine
                        $preprimary = ['play', 'kg', 'nursery'];
                        foreach($preprimary as $pp){
                            if(str_contains(strtolower($class), $pp)){
                                $section_title = "Shift";
                                break;
                            }
                        }
                        if(str_contains(strtolower($class), 'nine')){
                            $section_title = "Group";
                        }

                        $roll = $metadata['roll'][0] ?? '';
                        $mobile = $metadata['mobile'][0] ?? '';
                        $mobile = str_replace('+88', '', $mobile);
                        $mobile = str_replace(' ', '', $mobile);
                        $mobile = str_replace('-', '', $mobile);

                        $data['attendance'] = ['name' => "ID No", 'value' => $attendance_id ?? ''];
                        $data['class'] = ['name' => "Class", 'value' =>  $class_id ? $class : '' ];
                        $data['section'] = ['name' => $section_title, 'value' => $section ? $section : '' ];
                        $data['roll'] = ['name' => "Roll", 'value' =>  $roll ? $roll : '' ];
                        $avatar_id = get_user_meta($user->ID, 'avatar_id', true);
                        // $photo = $avatar_id ? wp_get_attachment_image_url($avatar_id, 'full') : '';
                        // $photo_url = $photo;
                    ?>
                    <div class="id-card-holder">
                        <img src="<?php echo EDUPRESS_IMG_URL; ?>id-cards/radiance-front-bg.png" style="position: absolute; left: 0; top: 0; z-index: 1; width: 100%; height: 100%;">
                        <div class="id-card-inner">
                            <div class="id-thumb-wrap">
                            <?php 
                                if($avatar_id > 0) : 
                                    echo wp_get_attachment_image($avatar_id, 'thumbnail', null, ['class' => 'id-thumb']);
                                else:
                                    $img_url = EDUPRESS_IMG_URL . 'id-cards/white-bg.png';
                                    echo "<img src='{$img_url}' class='id-thumb transparent-bg'>";
                                endif; 
                                ?>
                            </div>
                            <div class="details-wrap">
                                <div class="name"><?php echo $name; ?></div>
                                <?php 
                                    foreach($data as $k=>$v){
                                        if(empty($v['value'])) continue;
                                        echo "<div>
                                            <span class='key'>{$v['name']}</span><span class='value'>: {$v['value']}</span>
                                        </div>";
                                    }
                                ?>
                                <?php if($mobile): ?>
                                    <div class="mobile">
                                        <img src="<?php echo EDUPRESS_IMG_URL; ?>id-cards/mobile-icon.png" style="width: 15px; height: 15px; vertical-align: middle;">
                                        <?php echo $mobile; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="pagebreak"></div>
                <?php endforeach; ?>
            </body>
        </html>
        <?php 
        $html = ob_get_clean();
        $settings = [
            'page_width' => '54.61mm',
            'page_height' => '86.36mm',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ];
        $response = wp_remote_post('http://pdf.edupressbd.com/', [
            'method' => 'POST',
            'timeout' => 30,
            'body' => ['html' => $html, 'settings' => $settings],
        ]);
        if(is_wp_error($response)){
            var_dump(['status' => 0, 'data' => $response->get_error_message()]);
        }
        $data = json_decode($response['body'], true);
        var_dump($data);
        echo "<br><br>";
        echo $data['pdf'];
        echo "<br><br>";
