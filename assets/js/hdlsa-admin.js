jQuery(document).ready(function ($) {
    var frame;
    $(".hdlsa-upload-avatar").on("click", function (e) {
        e.preventDefault();
        if (frame) { frame.open(); return; }

        // Use localized strings if available, fallback to defaults
        var title = (typeof hdlsa_admin_params !== 'undefined') ? hdlsa_admin_params.select_avatar_title : 'Select Avatar';
        var btnText = (typeof hdlsa_admin_params !== 'undefined') ? hdlsa_admin_params.use_image_text : 'Use Image';

        frame = wp.media({
            title: title,
            button: { text: btnText },
            multiple: false
        });

        frame.on("select", function () {
            var attachment = frame.state().get("selection").first().toJSON();
            $("#hdlsa_host_avatar_id").val(attachment.id);
            $("#hdlsa-avatar-preview").html('<img src="' + attachment.sizes.thumbnail.url + '" style="max-width:100px; border-radius:50%; border:2px solid #FE2C55; padding:2px;">');
            $(".hdlsa-remove-avatar").show();
        });

        frame.open();
    });

    $(".hdlsa-remove-avatar").on("click", function (e) {
        e.preventDefault();
        $("#hdlsa_host_avatar_id").val("");
        $("#hdlsa-avatar-preview").empty();
        $(this).hide();
    });
});
