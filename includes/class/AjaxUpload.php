<?php
defined( 'ABSPATH' ) || die();
class AjaxUpload
{

    private static $_instance;

    /**
     * Initialize instance
     */
    public static function instance()
    {

        if ( is_null( self::$_instance ) ) self::$_instance = new self();
        return self::$_instance;

    }

    public function __construct()
    {

        add_action('wp_ajax_my_upload_action', [ $this, 'uploadCallback' ]);
        add_action('wp_ajax_nopriv_my_upload_action', [ $this, 'uploadCallback' ] );
        add_action('wp_head', [ $this, 'headHtml' ] );
        add_action('admin_enqueue_scripts', [ $this, 'my_enqueue' ] );
        wp_localize_script('jquery', 'ajax_upload', array());
    }

    public function uploadCallback() {

        // Check if the nonce is set
        if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'ajax_file_upload' ) ) {

            $uploaded_files = $_FILES['files'];
            $attachments = array();
            $ids = array();

            foreach ($uploaded_files['name'] as $key => $value) {

                if ($uploaded_files['name'][$key]) {
                    $file = array(
                        'name'     => $uploaded_files['name'][$key],
                        'type'     => $uploaded_files['type'][$key],
                        'tmp_name' => $uploaded_files['tmp_name'][$key],
                        'error'    => $uploaded_files['error'][$key],
                        'size'     => $uploaded_files['size'][$key]
                    );

                    // Set up the array of arguments for the media uploader
                    $upload_overrides = array( 'test_form' => false );
                    $movefile = wp_handle_upload( $file, $upload_overrides );

                    if ( $movefile && empty( $movefile['error'] ) ) {
                        // File is successfully uploaded
                        $attachment = array(
                            'post_mime_type' => $movefile['type'],
                            'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $movefile['file'] ) ),
                            'post_content' => '',
                            'post_status' => 'inherit'
                        );

                        // Insert the attachment into the media library
                        $attach_id = wp_insert_attachment( $attachment, $movefile['file'] );

                        // Generate attachment metadata and update the attachment
                        $attach_data = wp_generate_attachment_metadata( $attach_id, $movefile['file'] );
                        wp_update_attachment_metadata( $attach_id, $attach_data );

                        // Save attachment ID to the array
                        $attachments[] = array(
                            'id' => $attach_id,
                            'url' => wp_get_attachment_url( $attach_id )
                        );
                        $ids[] = $attach_id;
                    } else {
                        // Error handling
                        $attachments[] = 'Error uploading file: ' . $file['name'];
                    }

                }
            }

            $attachments['ids'] = implode(',', $ids );

            // Return array of attachment IDs
            return wp_send_json_success($attachments);

        } else {
            // If nonce is not set
            return wp_send_json_error('Security check failed!');
        }

    }

    public function headHtml()
    {

        ob_start();
        ?>
        <script>

            if( typeof $j === 'undefined' ) var $j = jQuery;
            ajax_upload._wpnonce = '<?php echo wp_create_nonce('ajax_file_upload'); ?>';
            jQuery(document).ready(function(){

                $j(document).on('change', '.wp_ajax_upload', function(e){
                    e.preventDefault();

                    var targetName = $j(this).data('target-name');
                    var targetClass = $j(this).data('target-class');
                    var uniqid = $j(this).data('uniqid');
                    var files_data = $j(this).prop('files');
                    var form_data = new FormData();
                    $j.each(files_data, function(i, file){
                        form_data.append('files[]', file);
                    });
                    form_data.append('action', 'my_upload_action');
                    form_data.append('_wpnonce', ajax_upload._wpnonce  );

                    $j.ajax({
                        url: edupress.ajax_url,
                        type: 'post',
                        data: form_data,
                        contentType: false,
                        processData: false,
                        dataType:'JSON',
                        beforeSend: function(){
                            console.log(form_data);
                            if(ajax_upload.loading){
                                ajax_upload.loading.show();
                            }
                        },
                        success: function(response){
                            if(ajax_upload.loading){
                                ajax_upload.loading.hide();
                            }
                            $j(`:input[name='${targetName}']`).val(response.data.ids);
                            $j(`.${targetClass}`).html('');
                            if( $j(`.${targetClass}`).length > 0 ){
                                $j.each( response.data, function( k, v) {
                                    if ( k !== 'ids' ) {
                                        $j(`.${targetClass}`).append(`<img src="${v.url}">`);
                                    }
                                })
                            }
                        }
                    });
                });
            });
        </script>
        <?php
        echo ob_get_clean();

    }

    public function my_enqueue()
    {
        wp_enqueue_media();
    }


}

AjaxUpload::instance();