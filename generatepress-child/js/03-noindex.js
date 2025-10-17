/*
=====================
  03 NOINDEX TAGS
=====================
*/

jQuery(function($){
    $('.noindex-toggle').on('change', function(){
        const $checkbox = $(this);
        const postId = $checkbox.data('post-id');
        const value = $checkbox.is(':checked') ? 1 : 0;
        const nonce = $checkbox.data('nonce');

        $.post(gpNoindex.ajaxurl, {
            action: 'toggle_noindex',
            post_id: postId,
            value: value,
            nonce: nonce
        }, function(response){
            if(!response.success){
                alert('Failed to save No index: ' + response.data);
                $checkbox.prop('checked', !value); // revert visual state
            } else if(response.data && response.data.nonce) {
                $checkbox.data('nonce', response.data.nonce); // update nonce
            }
        });
    });
});
