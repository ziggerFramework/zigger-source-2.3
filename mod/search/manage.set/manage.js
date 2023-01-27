//
// 사이트맵 관리
//
var mod_searchResult = {
    'init' : function() {
        this.action();
    },
    'action' : function(functions) {
        var $wait_box = $('#searchModifyForm .search-wait').clone();
        var list_arr = new Array;
        list_arr[0] = {
            'axis' : 'y',
            'stop' : function() {
                $('#searchListForm input[name=type]').val('modify');
                list_refrs();
            }
        }
        var $list_ele = new Array;

        var get_sortable = function() {
            $list_ele[0] = $('#searchListForm .sortable');
            $list_ele[0].sortable(list_arr[0]).disableSelection();
        }

        var list_charlen = function(str) {
            if (escape(str).length < 3) {
                var min = 4 - escape(str).length;
                var output = '';
                for (var i = 0; i < min; i++) {
                    output += '0';
                }
            }
            output = output + str;
            return output;
        }

        var request_sbm = function() {
            $('#searchListForm').submit();

        }

        var list_reload = function() {
            $('#searchListForm').load(MOD_SEARCH_DIR.replace(PH_DIR, PH_DIR + '/manage') + '/result/searchList', function(){
                get_sortable();
                $('.searchbox').removeClass('with-ajax-cover');
            });
        }
        list_reload();

        var list_refrs = function() {
            var eqidx = new Array();
            var eqval = new Array();

            $('.searchbox').addClass('with-ajax-cover');
            $('#searchListForm').append('<div class="ajax-cover"></div>');

            get_sortable();

            $list_ele[0].find('input[name="caidx[]"]').each(function() {
                var $this = $(this);
                var depth = $this.data('depth');

                if ($this.data('depth') === 1) {
                    eqidx[0] = parseInt($this.index('input[name="caidx[]"][data-depth=1]')) + 1;
                    eqval[0] = list_charlen(eqidx[0]);
                    if (eqidx[0]!=0) {
                        $this.val(eqval[0]);
                    }
                }
            });

            $('input[name="idx[]"]').each(function() {
                if (!$(this).val()) {
                    $(this).addClass('search_new_added_ele');

                } else {
                    $(this).removeClass('search_new_added_ele');
                }
            });

            $('#searchListForm input[name=new_caidx]').val($('.search_new_added_ele').eq(0).next('input[name="caidx[]"]').val());
            request_sbm();
            $('#searchListForm input[name=type]').val('add');
        }

        var secc_modify = function() {
            alert('성공적으로 수정 되었습니다.');
            list_reload();
        }

        if (functions) {
            eval(functions+'()');
            return false;
        }

        $(document).on('click', '#searchListForm .add-1d', function(e) {
            e.preventDefault();
            var html = '<div class="st-1d"><h4><a href="#" class="modify-btn"><input type="hidden" name="idx[]" value="" /><input type="hidden" name="caidx[]" value="" data-depth="1" /><input type="hidden" name="org_caidx[]" value="" />새로운 통합검색 콘텐츠</a><i class="fa fa-trash-alt st-del del-1d"></i></h4></div>';
            $(html).hide().appendTo($('.sortable'));
            list_refrs();
        });

        $(document).on('click', '#searchListForm .del-1d', function(e) {
            e.preventDefault();
            if (!confirm('삭제하는 경우 복구할 수 없습니다.\n\n그래도 진행 하시겠습니까?')) {
                return false;
            }
            var $this = $(this);
            $this.parents('.st-1d').remove();
            $('#searchListForm input[name=type]').val('modify');
            $('#searchModifyForm').empty().append($wait_box);
            list_refrs();
        });

        $(document).on('click', '#searchListForm a.modify-btn', function(e) {
            e.preventDefault();
            var idx = $(this).find('input[name="idx[]"]').val();
            $('#searchModifyForm').hide().load(MOD_SEARCH_DIR.replace(PH_DIR, PH_DIR + '/manage') + '/result/searchModify?idx=' + idx).fadeIn(100);
        });
    }
}
$(function() {
    mod_searchResult.init();
});
