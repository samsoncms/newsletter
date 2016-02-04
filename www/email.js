/**
 * Created by onysko on 21.12.2015.
 */

s('#emailClientSelect').pageInit(function(select) {
    select.selectify();

    s('._sjsselect_dropdown li', select.prev()).each(function(li) {
        if (!li.hasClass('selectify-loaded')) {
            li.click(function(li) {
                s('.entity-count-recipient').html(s('._sjsselect.clearfix li', select.prev()).length - 1);
                li.addClass('selectify-loaded');
                initLinks(select.prev());
            });
        }
    });

    function initLinks(block) {
        s('._sjsselect ._sjsselect_delete', block).each(function(link) {
            if (!link.hasClass('selectify-loaded')) {
                link.click(function(link) {
                    s('.entity-count-recipient').html(s('._sjsselect.clearfix li', block).length - 1);
                    link.addClass('selectify-loaded');
                });
            }
        });
    }

    s('.all-recipients-chb').change(function(chb) {
        if (chb.a('checked')) {
            s('.entity-count-recipient').html(chb.a('data-custom'));
        } else {
            s('.entity-count-recipient').html(s('._sjsselect.clearfix li', select.prev()).length - 1);
        }
    });
});
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