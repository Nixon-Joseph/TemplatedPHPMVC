$(function() {
    $.ajaxSetup({
        beforeSend: function(xhr, settings) {
            settings.url = $('head base').attr('href') + settings.url.replace(/^\//,'')
        }
    });
});