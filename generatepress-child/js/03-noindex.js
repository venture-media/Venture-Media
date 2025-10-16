jQuery(function($){
    const $wp_inline_edit = inlineEditPost.edit;

    inlineEditPost.edit = function(id) {
        $wp_inline_edit.apply(this, arguments);

        let post_id = 0;
        if (typeof(id) === 'object')
            post_id = parseInt(this.getId(id));

        if (post_id > 0) {
            const $edit_row = $('#edit-' + post_id);
            const $post_row = $('#post-' + post_id);
            const noindex = $post_row.attr('data-noindex');
            $edit_row.find('input[name="noindex"]').prop('checked', noindex === '1');
        }
    };
});
