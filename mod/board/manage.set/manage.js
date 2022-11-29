//
// 관리 checkbox 전체 선택
///
$(function() {
    $(document).on('click', '.cnum_allchk', function() {
        var chked = $(this).is(':checked');

        if (chked) {
            $('input[name="cnum[]"]').prop('checked', true);

        } else {
            $('input[name="cnum[]"]').prop('checked', false);
        }
    });
});

///
// 관리팝업
///
$(function() {
    var $form = '';
    var $ctrpop = '';
    var $ctrpopBG = '';

    //open
    $(document).on('click', '#list-ctr-btn', function(e) {
        e.preventDefault();

        var $form = $('#board-listForm');
        var cnum = $form.find(':checkbox[name="cnum[]"]:checked');

        if (cnum.length < 1) {
            alert('게시글을 한개 이상 선택해 주세요.');
            return false;
        }

        $('<div id="ctrpop-bg"></div>').appendTo('body');
        $('<div id="ctrpop"></div>').appendTo('body');
        $ctrpop = $('#ctrpop');
        $ctrpopBG = $('#ctrpop-bg');

        $.ajax({
            'type' : 'POST',
            'url' : MOD_BOARD_DIR + '/controller/pop/ctrl',
            'cache' : false,
            'data' : $form.serialize(),
            'dataType' : 'html',
            'success' : function(data) {
                $ctrpop.html(data).fadeIn(100);
                $ctrpopBG.fadeIn(100);
            }
        });
    });

    //close
    $(document).on('click', '#ctrpop .close', function(e) {
        e.preventDefault();
        $ctrpop.fadeOut(100);
        $ctrpopBG.fadeOut(100);
    });

    //삭제 버튼을 클릭하는 경우
    $(document).on('click', '#board_ctrpopForm #delete-btn', function(e) {
        e.preventDefault();
        if (confirm('정말로 삭제 하시겠습니까?\n\n선택된 게시물이 많은 경우 시간이 다소 소요될 수 있습니다.') === true) {
            $('#board_ctrpopForm input[name=type]').val('del');
            $('#board_ctrpopForm').submit();
        }
    });

    //복사 버튼을 클릭하는 경우
    $(document).on('click', '#board_ctrpopForm #copy-btn', function(e) {
        e.preventDefault();
        if (confirm('답글은 복사 되지 않습니다.\n계속 진행 하시겠습니까?\n\n선택된 게시물이 많은 경우 시간이 다소 소요될 수 있습니다.') === true) {
            $('#board_ctrpopForm input[name=type]').val('copy');
            $('#board_ctrpopForm').submit();
        }
    });

    //이동 버튼을 클릭하는 경우
    $(document).on('click', '#board_ctrpopForm #move-btn', function(e) {
        e.preventDefault();
        if (confirm('답글은 부모글 없이 단독으로 이동되지 않습니다.\n계속 진행 하시겠습니까?\n\n선택된 게시물이 많은 경우 시간이 다소 소요될 수 있습니다.') === true) {
            $('#board_ctrpopForm input[name=type]').val('move');
            $('#board_ctrpopForm').submit();
        }
    });
});

///
// 공지사항 옵션 체크시 답변알림 옵션 & 카테고리 숨김
///
var use_notice_opt = function($this) {
    var chked = $this.is(':checked');
    if (chked) {
        $('input[name=use_email]').next('label').hide();
        $('select[name=category]').prop('disabled', true);

    } else {
        $('input[name=use_email]').next('label').show();
        $('select[name=category]').prop('disabled', false);
    }
}
$(function() {
    var $opt = $('input[name=use_notice]');
    $opt.on({
        'click' : function() {
            use_notice_opt($opt);
        }
    });
    use_notice_opt($opt);
});

///
// 글 삭제
///
$(function() {
    $(document).on('click', '#del-btn', function(e) {
        e.preventDefault();
        var thisuri = $('#board-readForm input[name=thisuri]').val();
        if (confirm("이 글을 삭제 하시겠습니까?")) {
            $('#board-readForm').attr({
                'method' : 'POST',
                'action' : PH_DOMAIN + thisuri + '?mode=delete'
            }).submit();
        }
    });
});


///
// Comment 로드
///
cmt_stat_mdf = false;

function view_cmt_load() {
    var comment_board_id = $('#board-readForm input[name=board_id]').val();
    var comment_read = $('#board-readForm input[name=read]').val();
    var comment_thisuri = $('#board-readForm input[name=thisuri]').val();
    $('#board-comment').load(MOD_BOARD_DIR + '/controller/comment/load?board_id=' + comment_board_id + '&request=manage&read=' + comment_read + '&thisuri=' + comment_thisuri,function() {
        if ($('.g-recaptcha').length < 1) {
            return false;
        }
        var comment_timer;
        var comment_load = function(){
            if (g_recaptcha_captcha_act > 0) {
                g_recaptcha_captcha(1);
            } else {
                if (comment_timer) {
                    clearTimeout(comment_timer);
                }
                comment_timer = setTimeout(comment_load, 200);
            }
        }
        comment_load();
    });
}
$(function() {
    view_cmt_load();
});

///
// Comment 작성
///
$(function() {
    $(document).on('click', '#commentForm .sbm', function(e) {
        e.preventDefault();
        $('#commentForm input[name=mode]').val('write');
        $('#commentForm input[name=cidx]').val('');
        $('#commentForm').submit();
    });
});

///
// Comment 삭제
///
$(function() {
    $(document).on('click', '#cmt-delete',function(e) {
        e.preventDefault();
        if (confirm("댓글을 삭제 하시겠습니까?") === true) {
            var cidx = $(this).data('cmt-delete');
            $('#commentForm input[name=mode]').val('delete');
            $('#commentForm input[name=cidx]').val(cidx);
            $("#commentForm").submit();
        }
    });
});

///
// Comment 답글 작성
///
$(function() {
    var comm_re_form_idx = 0;
    var $comm_re_form;

    $(document).on('click',' #cmt-reply', function(e) {
        e.preventDefault();

        if (cmt_stat_mdf) {
            $('li.comm-list-li .comment > p').show();
            $('#comm-re-form textarea[name=re_comment]').val('');
            cmt_stat_mdf = false;
        }

        var vis = $('> #comm-re-form', $(this).parents('li.comm-list-li')).is(':visible');

        if (comm_re_form_idx === 0) {
            $comm_re_form = $('#comm-re-form').html();
            comm_re_form_idx++;
        }

        if (!vis) {
            $('#comm-re-form').remove();
            $('#commentForm input[name=cidx]').val($(this).data("cmt-reply"));
            $(this).parents('li.comm-list-li').append('<div id="comm-re-form">' + $comm_re_form + '</div>');
            $(this).parents('li.comm-list-li').find('#comm-re-form').show();

            if ($('.g-recaptcha').length > 0) {
                g_recaptcha_re_captcha(1);
            }
            cmt_stat_val = 'reply';

        } else {
            $('#comm-re-form').hide();
        }
    });
});

///
// Comment 수정
///
$(function() {
    $(document).on('click', '#cmt-modify', function(e) {
        e.preventDefault();

        if (cmt_stat_mdf) {
            $('li.comm-list-li').find('.comment').find('p').show();
            $('#comm-re-form textarea[name=re_comment]').val('');
            cmt_stat_mdf = false;
        }

        var vis = $('.comment #comm-re-form',$(this).parents('li.comm-list-li')).is(':visible');
        var comment = $('.comment > p', $(this).parents('li.comm-list-li')).text();

        if (!vis) {
            $comm_re_form = $('#comm-re-form').clone();
            $('#comm-re-form').remove();
            $('#commentForm input[name=cidx]').val($(this).data("cmt-modify"));
            $('.comment > p',$(this).parents('li.comm-list-li')).hide();
            $('.comment',$(this).parents('li.comm-list-li')).append($comm_re_form);
            $('#comm-re-form',$(this).parents('li.comm-list-li')).show();
            $('#comm-re-form textarea[name=re_comment]').val(comment);
            cmt_stat_mdf = true;
            cmt_stat_val = 'modify';

        } else {
            $('#comm-re-form').hide();
            $('.comment > p',$(this).parents('li.comm-list-li')).show();
            cmt_stat_mdf = false;
        }
    });
});

///
// Comment 답글 & 수정 Submit
///
$(document).on('click', '#comm-re-form .re_sbm, #commentForm .re_sbm', function(e) {
    e.preventDefault();
    $('#commentForm input[name=mode]').val(cmt_stat_val);
    $('#commentForm').submit();
});
