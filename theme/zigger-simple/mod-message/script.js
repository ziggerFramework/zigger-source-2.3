//
// 새로운 메시지 발송
//
$(function() {

    var $form = '';
    var $mbpop = '';
    var $mbpopBG = '';

    //open
    $(document).on('click', '*[data-message-send]', function(e) {
        e.preventDefault();

        var to_mb_id = $(this).data('message-send');
        var reply_parent_idx = $(this).data('message-send-reply');

        $('<div id="message-send-bg" data-no-tab-index></div>').appendTo('body');
        $('<div id="message-send" data-no-tab-index></div>').appendTo('body');
        $mbpop = $('#message-send');
        $mbpopBG = $('#message-send-bg');

        $.ajax({
            'type' : 'GET',
            'url' : MOD_MESSAGE_DIR + '/controller/pop/message-send',
            'cache' : false,
            'data' : {
                'to_mb_id' : to_mb_id,
                'reply_parent_idx' : reply_parent_idx
            },
            'dataType' : 'html',
            'success' : function(data) {
                $mbpop.html(data).fadeIn(100);
                $mbpopBG.fadeIn(100);

                //접근성 위해 layer로 focus 이동.
                $mbpop.find('.close').focus();
            }
        });
    });

    //close
    $(document).on('click', '#message-send .close', function(e) {
        e.preventDefault();

        //접근성 위해 layer띄운 요소로 focus 이동.
        $('*[data-tab-index='+PH_NOW_TABINDEX+']').focus();

        $mbpop.remove();
        $mbpopBG.remove();
    });

});
