jQuery(document).ready(function($) {

    // Add new form
    $('.add-form').on('click', function (e) {
        e.preventDefault();
        var datas = Array(),
            template = $('#contact-form-default').html();
        datas['index'] = $('#forms .form').length+1;

        $('#forms').append(render_tpl(template, datas));
    });

    // Delete form
    $('#forms').on('click', '.delete-form', function () {
        var r = confirm('Delete this form?');
        if (r) {
            $(this).parents('.form').remove();
        }
    });

    // Autogenerate email body from form html
    $('#forms').on('click', '.autogenerate-body', function (e) {
        e.preventDefault();
        var $parent = $(this).parents('.form'),
            form = $parent.find('textarea.form-html').val(),
            emailBody = $parent.find('textarea.email-body'),
            text = auto_generate_email_body(form);
        emailBody.val(text);
    });

    // Shortcode update
    $('#forms').on('keyup', '.form-name', function (e) {
        var $shortcode = $(this).parents('.form').find('.shortcode'),
            replacement = $(this).val(),
            text = $shortcode.html();
        $shortcode.html(text.replace(/name="([^"])+"/i, 'name="' + replacement + '"'));
    });

    /* RENDER TEMPLATE REPLACING {{variable_name}} BY ASSOCIATED KEY IN DATAS */
    function render_tpl (template, datas) {
        var r, tags;
        r = /(\{\{([^}]+)\}\})/g;

        while(tags = r.exec(template)) {
            if (tags[1] && tags[2]){
                template = template.replace(tags[1], datas[tags[2]]);
            }
        }

        return template;
    }

    /* AUTO GENERATE EMAIL BODY USING FIELD NAME FROM THE FORM */
    function auto_generate_email_body (form) {
        var inputRegExp,
            field,
            text = '';

        inputRegExp = /name=["']([^'"]+)["']/g;

        while((field = inputRegExp.exec(form)) !== null) {
            var name = field[1];
            text += name.charAt(0).toUpperCase() + name.slice(1) + ': [' + name + ']\n';
        }

        return text;
    }
});