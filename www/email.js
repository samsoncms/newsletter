/**
 * Created by onysko on 21.12.2015.
 */

s('.send-form').pageInit(function(form) {
    form.ajaxSubmit(function(response) {
        alert('Success');
    });

    s('.preview-link').click(function(link) {
        loader.show('', true);
        form.ajaxForm({
            'url' : link.a('href'),
            'handler' : function(response) {
                response = JSON.parse(response);
                s('#previewLetter').html(response.preview);
                loader.hide();
            }
        });

        return false;
    });
});

s(document).pageInit(function() {
    s('.newsletter-template-link').each(function(link) {
        link.tinyboxAjax({
            html: 'popup',
            oneClickClose: true
        });
    });
});