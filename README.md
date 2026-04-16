# ⭐ Custom Review System — Blocksy Child Theme

> A fully custom review/comment system built on top of WordPress's default comment engine.
> Adds custom fields: Name, Mobile Number, Branch, Star Rating, and Photo Upload.

---

## 📁 File Structure

```
blocksy-child/
│
├── functions.php              ← all logic, hooks & shortcode
├── style.css                  ← child theme styles
├── screenshot.jpg             ← theme screenshot
│
└── comments/
    ├── comments.css           ← form & comment card styles
    ├── comments.js            ← stars, photo preview & char counter
    └── blank-comments.php     ← empty file to hide default WP form
```

---

## ⚙️ Setup — Step by Step

### Step 1 — Enable Comments on the Page

```
WP Admin → Pages → Edit your page
→ Screen Options (top right) → check Discussion
→ Enable: Allow comments
```

### Step 2 — Enable Manual Moderation

```
WP Admin → Settings → Discussion
✅ Comment must be manually approved
✅ Email me when a comment is held for moderation
```

### Step 3 — Add the Shortcode to Any Page

```
[review_form]
```

---

## 🧩 Code Sections in functions.php

### Section 1 — Assets

Loads the parent theme stylesheet and our custom CSS/JS files.

```php
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'comments-style', get_stylesheet_directory_uri() . '/comments/comments.css' );
    wp_enqueue_script( 'comments-js', get_stylesheet_directory_uri() . '/comments/comments.js', [], '1.0.0', true );
});
```

> We use `get_stylesheet_directory_uri()` because we are in a **child theme** — it points to `blocksy-child`, not the parent.

---

### Section 2 — Filters

#### Remove Email & Website Fields

```php
add_filter( 'comment_form_fields', function ( $fields ) {
    unset( $fields['email'] );
    unset( $fields['url'] );
    unset( $fields['cookies'] );
    return $fields;
}, 999 );
```

> Priority `999` ensures our filter runs **after** Blocksy registers its fields, so the unset actually sticks.

#### Hide the Default WordPress Form

```php
add_filter( 'comments_template', function ( $template ) {
    global $post;
    if ( $post && has_shortcode( $post->post_content, 'review_form' ) ) {
        return get_stylesheet_directory() . '/comments/blank-comments.php';
    }
    return $template;
});
```

> When WordPress tries to load its default comment form, we swap it with a blank file — but only on pages that contain our shortcode.

---

### Section 3 — Saving Data

```php
add_action( 'comment_post', function ( $comment_id ) {
    add_comment_meta( $comment_id, 'phone',  sanitize_text_field( $_POST['phone'] ) );
    add_comment_meta( $comment_id, 'branch', sanitize_text_field( $_POST['branch'] ) );
    add_comment_meta( $comment_id, 'rating', intval( $_POST['rating'] ) );
    // + photo upload to Media Library
});
```

**Database tables used:**

| Table            | Stores                       | Who saves it            |
| ---------------- | ---------------------------- | ----------------------- |
| `wp_comments`    | Name, comment text, date     | WordPress automatically |
| `wp_commentmeta` | Phone, branch, rating, photo | Our code                |

---

### Section 4 — Displaying Comments

```php
function render_review_comment( $comment, $args, $depth ) {
    $phone    = get_comment_meta( $comment->comment_ID, 'phone',    true );
    $branch   = get_comment_meta( $comment->comment_ID, 'branch',   true );
    $rating   = get_comment_meta( $comment->comment_ID, 'rating',   true );
    $photo_id = get_comment_meta( $comment->comment_ID, 'photo_id', true );
    // renders the review card HTML
}
```

This function is passed to `wp_list_comments()` as a callback — WordPress calls it once per comment and passes it the comment data.

---

### Section 5 — Custom Form

```php
function my_custom_comment_form() {
    comment_form([
        'fields' => [
            'author' => '<div>...</div>',
            'phone'  => '<div>...</div>',
            'branch' => '<div>...</div>',
            'rating' => '<div>...</div>',
            'photo'  => '<div>...</div>',
        ],
        'comment_field' => '<textarea>...</textarea>',
        'submit_button' => '<button>Submit</button>',
    ]);
}
```

> The order of keys inside the `fields` array controls the order fields appear on screen.

---

### Section 6 — Shortcode `[review_form]`

```php
add_shortcode( 'review_form', function () {
    global $post;
    $comments = get_comments(['post_id' => $post->ID, 'status' => 'approve']);

    ob_start();
        wp_list_comments(['callback' => 'render_review_comment'], $comments);
        my_custom_comment_form();
    return ob_get_clean();
});
```

> `ob_start()` and `ob_get_clean()` capture the HTML output so the shortcode can return it as a string instead of echoing it directly.

---

### Section 7 — WP Admin Columns

Adds Phone, Branch, and Rating columns to the Comments list so you can see all data at a glance.

```php
add_filter( 'manage_edit-comments_columns', function ( $columns ) {
    $columns['phone']  = 'Mobile';
    $columns['branch'] = 'Branch';
    $columns['rating'] = 'Rating';
    return $columns;
});
```

---

## 🔄 Comment Lifecycle

```
1. User fills out the form and submits
         ↓
2. WordPress saves name & comment → wp_comments table
         ↓
3. comment_post hook fires → our code saves
   phone, branch, rating & photo → wp_commentmeta table
         ↓
4. Comment is saved as "Pending"
         ↓
5. Admin receives email notification
         ↓
6. Admin goes to WP Admin → Comments → Approves it
         ↓
7. Comment appears on the page via [review_form]
```

---

## 🎨 Styling with CSS

Every card element has its own CSS class:

```
.review-list           the full comments list
.review-item           single comment <li>
.review-card           the white card
.review-card__header   avatar + name + stars row
.review-card__author   avatar and name
.review-branch         branch name label
.review-stars          star rating
.review-card__body     comment text
.review-card__photo    uploaded photo
.review-card__footer   date
```

**Example — change card style:**

```css
.review-card {
  background: #f9fafb;
  border-radius: 20px;
  border: none;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
}
```

**Example — change star color:**

```css
.review-stars {
  color: #f59e0b;
  font-size: 1.4rem;
}
```

---

## 📝 Adding a New Branch

In `functions.php` inside `my_custom_comment_form()`:

```php
'branch' => '
    <select id="branch" name="branch" required>
        <option value="" disabled selected>Select Branch</option>
        <option value="branch-1">Branch 1 - Location</option>
        <option value="branch-2">Branch 2 - Location</option>
        <option value="branch-3">Branch 3 - Location</option>
    </select>',
```

---

## ❓ Troubleshooting

| Problem                              | Fix                                                         |
| ------------------------------------ | ----------------------------------------------------------- |
| Comments show without approval       | `Settings → Discussion` → enable manual approval            |
| Photo not saving                     | Make sure the enctype script is in `functions.php`          |
| Email & website fields still showing | Make sure `comment_form_fields` filter has priority `999`   |
| Two forms showing on the page        | Make sure `blank-comments.php` exists in `comments/` folder |
| Form not showing at all              | Check that comments are enabled on the page                 |

---

## 👥 File Responsibilities

| File                          | Responsibility                           |
| ----------------------------- | ---------------------------------------- |
| `functions.php`               | All logic, hooks, filters, and shortcode |
| `comments/comments.css`       | All styling                              |
| `comments/comments.js`        | All interactions                         |
| `comments/blank-comments.php` | Hides the default WordPress form         |

---

## 🛠️ Built With

- WordPress Comments API
- `comment_form()` with custom args
- `wp_commentmeta` table for extra fields
- WordPress hooks: `comment_post`, `wp_enqueue_scripts`
- WordPress filters: `comment_form_fields`, `comments_template`
- Blocksy Child Theme



<img width="367" height="526" style="border-radius: 5px;" alt="customcomments" src="https://github.com/user-attachments/assets/fab4e30a-b1eb-4424-ae13-3c6cd2ce04a3" />
