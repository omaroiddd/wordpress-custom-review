<?php
/**
 * Blocksy Child Theme — functions.php
 * نظام التقييمات المخصص
 *
 * @package BlocksyChild
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    die( 'Direct access forbidden.' );
}

// =============================================================================
// 1. ASSETS — تحميل الملفات
// =============================================================================

/**
 * تحميل ستايل الثيم الأب + ملفات التعليقات
 */
add_action( 'wp_enqueue_scripts', function () {

    // ستايل الثيم الأب
    wp_enqueue_style(
        'parent-style',
        get_template_directory_uri() . '/style.css'
    );

    // CSS الخاص بالتقييمات
    wp_enqueue_style(
        'comments-style',
        get_stylesheet_directory_uri() . '/comments/comments.css',
        [],
        '1.0.0'
    );

    // JS الخاص بالتقييمات (يتحمل في الـ footer)
    wp_enqueue_script(
        'comments-js',
        get_stylesheet_directory_uri() . '/comments/comments.js',
        [],
        '1.0.0',
        true
    );

});


// =============================================================================
// 2. FILTERS — تعديل الفورم الافتراضي
// =============================================================================

/**
 * حذف حقول الإيميل والويبسايت من الفورم
 * priority 999 عشان تشتغل بعد Blocksy
 */
// Remove this from your comment_form() fields array ↓
// 'author' => '...',  ← delete this line

// And add this filter instead ↓
add_filter( 'comment_form_fields', function ( $fields ) {
    $commenter = wp_get_current_commenter();

    $fields['author'] = '
        <div class="form-group">
            <label for="author">الاسم <span class="required">*</span></label>
            <input
                type="text"
                id="author"
                name="author"
                placeholder="أدخل اسمك الكامل"
                value="' . esc_attr( $commenter['comment_author'] ) . '"
                required
            />
        </div>';

    unset( $fields['email'] );
    unset( $fields['url'] );
    unset( $fields['cookies'] );

        // ← reorder: author first, then the rest
    return array_merge(
        [ 'author' => $fields['author'] ],
        array_diff_key( $fields, [ 'author' => '' ] )
    );
    
}, 9999 ); // ← 9999 runs after Blocksy
/**
 * إضافة enctype للفورم عشان رفع الصور يشتغل
 */
add_action( 'comment_form', function () {
    echo '<script>
        var f = document.getElementById("review-form");
        if(f) f.setAttribute("enctype","multipart/form-data");
    </script>';
} );

/**
 * إخفاء فورم ووردبريس الافتراضي لما يكون في الصفحة shortcode بتاعنا
 */
add_filter( 'comments_template', function ( $template ) {
    global $post;
    if ( $post && has_shortcode( $post->post_content, 'review_form' ) ) {
        return get_stylesheet_directory() . '/comments/blank-comments.php';
    }
    return $template;
} );


// =============================================================================
// 3. SAVE — حفظ بيانات التقييم
// =============================================================================

/**
 * حفظ الحقول الإضافية بعد ما ووردبريس يحفظ التعليق
 *
 * @param int $comment_id رقم التعليق الجديد
 */
add_action( 'comment_post', function ( $comment_id ) {

    // رقم الموبايل
    if ( ! empty( $_POST['phone'] ) ) {
        add_comment_meta(
            $comment_id,
            'phone',
            sanitize_text_field( $_POST['phone'] )
        );
    }

    // الفرع
    if ( ! empty( $_POST['branch'] ) ) {
        add_comment_meta(
            $comment_id,
            'branch',
            sanitize_text_field( $_POST['branch'] )
        );
    }

    // التقييم (1 - 5)
    if ( ! empty( $_POST['rating'] ) ) {
        $rating = intval( $_POST['rating'] );
        if ( $rating >= 1 && $rating <= 5 ) {
            add_comment_meta( $comment_id, 'rating', $rating );
        }
    }

    // الصورة
    if ( ! empty( $_FILES['photo']['name'] ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $attachment_id = media_handle_upload( 'photo', get_the_ID() );

        if ( ! is_wp_error( $attachment_id ) ) {
            add_comment_meta( $comment_id, 'photo_id', $attachment_id );
        }
    }

} );


// =============================================================================
// 4. DISPLAY — عرض التعليقات
// =============================================================================

/**
 * رسم كارد التعليق الواحد
 * بيتاستخدم كـ callback جوه wp_list_comments()
 *
 * @param WP_Comment $comment بيانات التعليق
 * @param array      $args    إعدادات wp_list_comments
 * @param int        $depth   مستوى التداخل
 */
function render_review_comment( $comment, $args, $depth ) {

    // جيب البيانات الإضافية
    $phone    = get_comment_meta( $comment->comment_ID, 'phone',    true );
    $branch   = get_comment_meta( $comment->comment_ID, 'branch',   true );
    $rating   = get_comment_meta( $comment->comment_ID, 'rating',   true );
    $photo_id = get_comment_meta( $comment->comment_ID, 'photo_id', true );

    // ارسم النجوم
    $stars = $rating
        ? str_repeat( '★', (int) $rating ) . str_repeat( '☆', 5 - (int) $rating )
        : '';

    ?>
    <li <?php comment_class( 'review-item' ); ?> id="comment-<?php comment_ID(); ?>" dir="rtl">
        <div class="review-card">

            <!-- Header: صورة + اسم + فرع | نجوم -->
            <div class="review-card__header">
                <div class="review-card__author">
                    <?php echo get_avatar( $comment, 48 ); ?>
                    <div>
                        <strong><?php comment_author(); ?></strong>
                        <?php if ( $branch ) : ?>
                            <span class="review-branch">
                                📍 <?php echo esc_html( $branch ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ( $stars ) : ?>
                    <div class="review-stars"><?php echo esc_html( $stars ); ?></div>
                <?php endif; ?>
            </div>

            <!-- Body: نص التعليق -->
            <div class="review-card__body">
                <?php comment_text(); ?>
            </div>

            <!-- Photo: الصورة لو اتحملت -->
            <?php if ( $photo_id ) : ?>
                <div class="review-card__photo">
                    <?php echo wp_get_attachment_image( $photo_id, 'medium' ); ?>
                </div>
            <?php endif; ?>

            <!-- Footer: التاريخ -->
            <div class="review-card__footer">
                <?php comment_date( 'd M Y' ); ?>
            </div>

        </div>
    </li>
    <?php
}


// =============================================================================
// 5. FORM — فورم التقييم المخصص
// =============================================================================

/**
 * رسم فورم التقييم الكامل بكل حقوله
 * بيتاستخدم جوه الـ shortcode
 */
function my_custom_comment_form() {
    global $post;

    // لو التعليقات مقفولة على الصفحة دي — ما تعرضش الفورم
    if ( ! comments_open( $post->ID ) ) {
        return;
    }

    $commenter = wp_get_current_commenter();

    ?>
    <div class="review-form-wrapper" dir="rtl">
        <?php
        comment_form( [
            'id_form'              => 'review-form',
            'class_form'           => 'review-form',
            'title_reply'          => 'أضف تقييمك',
            'title_reply_before'   => '<h2 class="review-form__title">',
            'title_reply_after'    => '</h2>',
            'label_submit'         => 'إرسال التقييم',
            'comment_notes_before' => '',
            'comment_notes_after'  => '',

            'fields' => [
                // رقم الموبايل
                'phone' => '
                    <div class="form-group">
                        <label for="phone">رقم الموبايل <span class="required">*</span></label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            placeholder="05XXXXXXXX"
                            pattern="^05[0-9]{8}$"
                            required
                        />
                    </div>',

                // الفرع
                'branch' => '
                    <div class="form-group">
                        <label for="branch">الفرع <span class="required">*</span></label>
                        <select id="branch" name="branch" required>
                            <option value="" disabled selected>اختر الفرع</option>
                            <option value="الريان">فرع الريان طريق خريص</option>
                            <option value="المروج">فرع المروج طريق الامام سعود</option>
                        </select>
                    </div>',

                // التقييم بالنجوم
                'rating' => '
                    <div class="form-group">
                        <label>التقييم <span class="required">*</span></label>
                        <div class="stars-wrapper">
                            <input type="radio" name="rating" id="star5" value="5" />
                            <label for="star5" title="ممتاز">★</label>
                            <input type="radio" name="rating" id="star4" value="4" />
                            <label for="star4" title="جيد جداً">★</label>
                            <input type="radio" name="rating" id="star3" value="3" />
                            <label for="star3" title="جيد">★</label>
                            <input type="radio" name="rating" id="star2" value="2" />
                            <label for="star2" title="مقبول">★</label>
                            <input type="radio" name="rating" id="star1" value="1" />
                            <label for="star1" title="ضعيف">★</label>
                        </div>
                        <span class="rating-label" id="rating-label">اختر تقييمك</span>
                    </div>',

                // رفع صورة
                'photo' => '
                    <div class="form-group form-group--full">
                        <label for="photo">أضف صورة (اختياري)</label>
                        <div class="photo-upload-wrapper">
                            <input
                                type="file"
                                id="photo"
                                name="photo"
                                accept="image/jpeg, image/png, image/webp"
                            />
                            <div class="photo-upload-ui" id="photo-drop-zone">
                                <span class="upload-icon">📷</span>
                                <span class="upload-text">اسحب صورة أو اضغط للرفع</span>
                                <span class="upload-hint">JPG, PNG — حجم أقصى 2MB</span>
                            </div>
                            <div class="photo-preview" id="photo-preview" style="display:none;">
                                <img id="preview-img" src="" alt="معاينة الصورة" />
                                <button type="button" class="remove-photo" id="remove-photo">✕</button>
                            </div>
                        </div>
                    </div>',
            ],

            // حقل التعليق النصي
            'comment_field' => '
                <div class="form-group form-group--full">
                    <label for="comment">تعليقك <span class="required">*</span></label>
                    <textarea
                        id="comment"
                        name="comment"
                        rows="4"
                        placeholder="اكتب تجربتك مع الفرع..."
                        maxlength="500"
                        required
                    ></textarea>
                    <span class="char-count">
                        <span id="char-num">0</span> / 500
                    </span>
                </div>',

            'submit_button' => '
                <button type="submit" name="%1$s" id="%2$s" class="btn-submit">
                    إرسال التقييم <span class="btn-arrow">←</span>
                </button>',

            'submit_field' => '<div class="form-submit-wrapper">%1$s %2$s</div>',
        ] );
        ?>
    </div>
    <?php
}


// =============================================================================
// 6. SHORTCODE — [review_form]
// =============================================================================

/**
 * الـ shortcode اللي بيتحط في أي صفحة ووردبريس
 * بيعرض التعليقات المعتمدة + فورم التقييم
 *
 * الاستخدام: [review_form]
 */
add_shortcode( 'review_form', function () {
    global $post;

    // جيب التعليقات المعتمدة للصفحة دي
    $comments = get_comments( [
        'post_id' => $post->ID,
        'status'  => 'approve',
        'order'   => 'DESC',
    ] );

    ob_start();

    // عرض التعليقات
    if ( $comments ) {
        echo '<ul class="review-list">';
        wp_list_comments( [
            'callback' => 'render_review_comment',
            'per_page' => 10,
        ], $comments );
        echo '</ul>';
    } else {
        echo '<p class="no-reviews" dir="rtl">لا توجد تقييمات بعد. كن أول من يقيّم!</p>';
    }

    // عرض الفورم
    my_custom_comment_form();

    return ob_get_clean();
} );


// =============================================================================
// 7. ADMIN — إضافة بيانات التقييم في لوحة التحكم
// =============================================================================

/**
 * إضافة أعمدة (موبايل، فرع، تقييم) في قايمة التعليقات
 */
add_filter( 'manage_edit-comments_columns', function ( $columns ) {
    $columns['phone']  = 'الموبايل';
    $columns['branch'] = 'الفرع';
    $columns['rating'] = 'التقييم';
    return $columns;
} );

add_action( 'manage_comments_custom_column', function ( $column, $comment_id ) {
    switch ( $column ) {
        case 'phone':
            $val = get_comment_meta( $comment_id, 'phone', true );
            echo $val ? esc_html( $val ) : '—';
            break;
        case 'branch':
            $val = get_comment_meta( $comment_id, 'branch', true );
            echo $val ? esc_html( $val ) : '—';
            break;
        case 'rating':
            $val = get_comment_meta( $comment_id, 'rating', true );
            echo $val
                ? esc_html( str_repeat( '★', (int) $val ) . str_repeat( '☆', 5 - (int) $val ) )
                : '—';
            break;
    }
}, 10, 2 );

/**
 * إضافة meta box في صفحة تعديل التعليق الكاملة
 */
add_action( 'add_meta_boxes_comment', function () {
    add_meta_box(
        'review_extra_info',
        'بيانات التقييم',
        function ( $comment ) {
            $phone    = get_comment_meta( $comment->comment_ID, 'phone',    true );
            $branch   = get_comment_meta( $comment->comment_ID, 'branch',   true );
            $rating   = get_comment_meta( $comment->comment_ID, 'rating',   true );
            $photo_id = get_comment_meta( $comment->comment_ID, 'photo_id', true );
            ?>
            <table class="form-table">
                <tr>
                    <th>رقم الموبايل</th>
                    <td><?php echo $phone  ? esc_html( $phone )  : '—'; ?></td>
                </tr>
                <tr>
                    <th>الفرع</th>
                    <td><?php echo $branch ? esc_html( $branch ) : '—'; ?></td>
                </tr>
                <tr>
                    <th>التقييم</th>
                    <td>
                        <?php echo $rating
                            ? esc_html( str_repeat( '★', (int) $rating ) . str_repeat( '☆', 5 - (int) $rating ) )
                            : '—';
                        ?>
                    </td>
                </tr>
                <?php if ( $photo_id ) : ?>
                <tr>
                    <th>الصورة</th>
                    <td><?php echo wp_get_attachment_image( $photo_id, 'thumbnail' ); ?></td>
                </tr>
                <?php endif; ?>
            </table>
            <?php
        },
        'comment',
        'normal'
    );
} );