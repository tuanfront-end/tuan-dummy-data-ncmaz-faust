<?php
/*
Plugin Name: Tuan Dmmy Data
Plugin URI: http://wordpress.org/ 
Description:  This plugin will insert dummy data to your wordpress site from json file. 
Author:  Eden Tuan (ChisNghiax)
Version: 1.0.0
Author URI: https://www.facebook.com/chisnghiax
*/

defined('ABSPATH') || exit;

define('TUAN_DUMMY_DATA_VERSION', '1.0.0');

// require the tuan-content.php
require_once plugin_dir_path(__FILE__) . 'tuan-content-demo.php';


// ===========================================================================

// Viết hàm tạo 1 theme-option page trên menu dashboard 
add_action('admin_menu', 'tuan_dummy_data_menu');
function tuan_dummy_data_menu()
{
    add_menu_page(
        'Tuan Dummy Data',
        'Tuan Dummy Data',
        'manage_options',
        'tuan-dummy-data',
        'tuan_dummy_data_page',
        'dashicons-admin-generic',
        6
    );
}


// Viết hàm tạo nội dung cho theme-option page
function tuan_dummy_data_page()
{  ?>
    <h1>Tuan Dummy Data For Ncmaz Faustjs</h1>
    <p>Click button to insert dummy data to your wordpress site. Plugin này yêu cầu đi cùng plugin ncmaz-faust-core và acf!</p>

    <!-- // Tạo form để submit dữ liệu -->
    <form method="post" action="#">
        <input type="hidden" name="tuan_insert_posts" value="tuan_dummy_data">
        <!-- checkbox is check json file -->
        <input type="checkbox" id="tuan_only_check_json" name="tuan_only_check_json" value="tuan_only_check_json">
        <label for="tuan_only_check_json"> I only want check json files</label><br>

        <!-- checkbox is check json file -->
        <input type="checkbox" id="tuan_ok_for_insert" name="tuan_ok_for_insert" value="tuan_ok_for_insert">
        <label for="tuan_ok_for_insert"> OK! now I want inset posts! </label><br>

        <br>
        <input type="submit" value="Insert Dummy Data - Posts" class="button-primary">
    </form>

<?php
}

// Viết hàm xử lý khi click vào button insert dummy data (insert posts)
add_action('admin_init', 'tuan_dummy_data_handle_insert_posts');
function tuan_dummy_data_handle_insert_posts()
{

    // Kiểm tra xem có phải là form insert posts hay không
    if (($_POST['tuan_insert_posts'] ?? "") !== 'tuan_dummy_data') {
        return;
    }

    if (!function_exists('update_field')) {
        echo "Co ve plugin ACF chua duoc kich hoat. Vui long kiem tra lai!";
        return;
    }

    // Đọc nội dung của tệp JSON
    $jsonData1 = file_get_contents(plugin_dir_path(__FILE__) . 'jsons/__posts.json');
    $jsonDataNews = file_get_contents(plugin_dir_path(__FILE__) . 'jsons/__posts_news.json');
    $jsonDataAudio = file_get_contents(plugin_dir_path(__FILE__) . 'jsons/__postsAudio.json');
    $jsonDataVideo = file_get_contents(plugin_dir_path(__FILE__) . 'jsons/__postsVideo.json');
    $jsonDataGallery = file_get_contents(plugin_dir_path(__FILE__) . 'jsons/__postsGallery.json');

    // Chuyển đổi dữ liệu JSON thành mảng PHP
    $dataArray1 = json_decode($jsonData1, true);
    $dataArrayNews = json_decode($jsonDataNews, true);
    $dataArrayAudio = json_decode($jsonDataAudio, true);
    $dataArrayVideo = json_decode($jsonDataVideo, true);
    $dataArrayGallery = json_decode($jsonDataGallery, true);


    // Kiểm tra xem chuyển đổi có thành công không
    if (!$dataArray1 || !$dataArrayNews || !$dataArrayAudio || !$dataArrayVideo || !$dataArrayGallery) {
        echo "Không thể chuyển đổi dữ liệu JSON / Không tìm thấy file json.";
        return;
    } else {

        $allDataArray = array_merge($dataArray1, $dataArrayNews, $dataArrayAudio, $dataArrayVideo, $dataArrayGallery);

        if (($_POST['tuan_only_check_json'] ?? "") === 'tuan_only_check_json') {
            echo '<h1>Check json file success!</h1>';
            echo '<pre>';
            print_r($allDataArray);
            echo '</pre>';
            return;
        }

        if (($_POST['tuan_ok_for_insert'] ?? "") !== 'tuan_ok_for_insert') {
            return;
        }


        // insert post to wp from this array data 
        foreach ($allDataArray as $item) {
            $new_post = array(
                'post_title'    => wp_strip_all_tags($item['title']),
                'post_content'  => TUAN_DUMMY_DATA_DEMO_CONTENT_HTML_OF_POST,
                'post_status'   => 'publish',
                'post_author'   => 1,
                'post_category' => [],
                'tags_input'     =>  ['Ncmaz', 'ChisNghiax', 'Blog', 'Magazine', 'News', 'Nextjs', 'Faustjs', 'WordPress headless CMS', 'Headless WordPress'],
            );
            // Tạo bài viết mới
            $post_id = wp_insert_post($new_post);

            if (!$post_id) {

                // Tạo thumbnail cho bài viết
                $image_url = $item['featuredImage'];
                $image_id = media_sideload_image($image_url, $post_id,  $item['title'], 'id');

                if (!is_wp_error($image_id)) {
                    set_post_thumbnail($post_id, $image_id);
                }

                // Tạo meta data cho bài viết Audio
                if ($item['postType'] === 'audio') {
                    set_post_format($post_id, $item['postType']);
                    update_field('audio_url', $item['audioUrl'], $post_id);
                }

                // Tạo meta data cho bài viết Video
                if ($item['postType'] === 'video') {
                    set_post_format($post_id, $item['postType']);
                    update_field('video_url', $item['videoUrl'], $post_id);
                }

                // Tạo meta data cho bài viết Gallery
                if ($item['postType'] === 'gallery') {
                    set_post_format($post_id, $item['postType']);

                    // update image 1 for gallery 
                    if (!empty($item["galleryImgs"][0])) :
                        $image_1 = media_sideload_image($item["galleryImgs"][0], $post_id, "Image description", 'id');
                        if (!is_wp_error($image_1)) {
                            update_field('image_1', $image_1, $post_id);
                        }
                    endif;

                    // update image 2 for gallery
                    if (!empty($item["galleryImgs"][1])) :
                        $image_2 = media_sideload_image($item["galleryImgs"][1], $post_id, "Image description", 'id');
                        if (!is_wp_error($image_2)) {
                            update_field('image_2', $image_2, $post_id);
                        }
                    endif;

                    // update image 3 for gallery
                    if (!empty($item["galleryImgs"][2])) :
                        $image_3 = media_sideload_image($item["galleryImgs"][2], $post_id, "Image description", 'id');
                        if (!is_wp_error($image_3)) {
                            update_field('image_3', $image_3, $post_id);
                        }
                    endif;

                    // update image 4 for gallery
                    if (!empty($item["galleryImgs"][3])) :
                        $image_4 = media_sideload_image($item["galleryImgs"][3], $post_id, "Image description", 'id');
                        if (!is_wp_error($image_4)) {
                            update_field('image_4', $image_4, $post_id);
                        }
                    endif;

                    // update image 5 for gallery
                    if (!empty($item["galleryImgs"][5])) :
                        $image_5 = media_sideload_image($item["galleryImgs"][4], $post_id, "Image description", 'id');
                        if (!is_wp_error($image_5)) {
                            update_field('image_5', $image_5, $post_id);
                        }
                    endif;
                }
            }
        }

        // Sau khi xử lý, chuyển hướng đến trang edit.php
        wp_redirect(admin_url('edit.php'));
        exit; // Đảm bảo không có mã HTML/PHP tiếp theo sau redirect
    }
}
